<?php

namespace App\Services;

use App\Exceptions\GeminiQuotaExceededException;
use App\Exceptions\GeminiUnavailableException;
use App\Models\Judgment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class JudgmentIngestionService
{
    /** Detection + summary — flash-latest (separate free-tier pool from gemini-3.6-flash). */
    private const DETECTION_MODEL = 'gemini-flash-latest';

    private const SUMMARY_MODEL = 'gemini-flash-latest';

    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models';

    /** Prefer Files API once base64 inline would balloon the request. */
    private const INLINE_MAX_BYTES = 3_500_000;

    /** How many cases to summarise per Gemini call when batching a compilation. */
    public const SUMMARY_BATCH_SIZE = 5;

    /** Soft word cap per case when building summarisation prompts (keeps batches light). */
    private const SUMMARY_TEXT_WORD_CAP = 6000;

    /** Retries for transient 5xx / connection failures (not for 429 quota). */
    private const MAX_TRANSIENT_RETRIES = 3;

    public function __construct(
        private readonly PdfTextExtractor $textExtractor,
        private readonly PdfPageSlicer $pageSlicer,
        private readonly GeminiEmbeddingService $embeddings,
        private readonly GeminiKeyPool $keyPool,
        private readonly GeminiFileService $files,
    ) {}

    /**
     * Detect whether a PDF is a multi-judgment compilation.
     *
     * @return array{
     *     is_compilation: bool,
     *     cases: list<array{
     *         citation: ?string,
     *         court: ?string,
     *         decided_date: ?string,
     *         start_page: int,
     *         end_page: int,
     *         cited_acts: list<string>
     *     }>
     * }
     */
    public function detectCompilation(string $pdfAbsolutePath): array
    {
        $schema = [
            'type' => 'object',
            'properties' => [
                'is_compilation' => ['type' => 'boolean'],
                'cases' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'citation' => ['type' => 'string', 'nullable' => true],
                            'court' => ['type' => 'string', 'nullable' => true],
                            'decided_date' => ['type' => 'string', 'nullable' => true],
                            'start_page' => ['type' => 'integer'],
                            'end_page' => ['type' => 'integer'],
                            'cited_acts' => [
                                'type' => 'array',
                                'items' => ['type' => 'string'],
                            ],
                        ],
                        'required' => [
                            'citation',
                            'court',
                            'decided_date',
                            'start_page',
                            'end_page',
                            'cited_acts',
                        ],
                    ],
                ],
            ],
            'required' => ['is_compilation', 'cases'],
        ];

        $prompt = <<<'PROMPT'
Analyse this Sri Lankan legal PDF.
Determine whether it is a compilation/digest/law-report volume containing multiple distinct judgments (typically a table of contents listing several cases with page ranges, then each case as its own section).

IMPORTANT for speed and accuracy on large volumes:
- Prefer the table of contents / index at the front when present.
- Return page ranges and citations from the TOC; do NOT deeply read every judgment body.
- Leave cited_acts as an empty array for now (acts are extracted later per case).
- Still return is_compilation=true when more than one distinct judgment is present.

If it is a single judgment, return is_compilation=false and a cases array with exactly one entry covering the whole document.
PROMPT;

        $response = $this->generateWithPdf(
            self::DETECTION_MODEL,
            $pdfAbsolutePath,
            $prompt,
            [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
            600,
        );

        $text = $response->json('candidates.0.content.parts.0.text');
        $decoded = is_string($text) ? json_decode($text, true) : null;

        if (! is_array($decoded)) {
            throw new RuntimeException('Gemini compilation detection returned invalid JSON.');
        }

        return $this->normalizeDetection($decoded);
    }

    /**
     * Index a single judgment PDF already stored on the public disk:
     * extract text → embed (RETRIEVAL_DOCUMENT prefix) → summarize.
     *
     * @param  list<string>|null  $citedActsHint  From detection, when available
     */
    public function ingest(
        Judgment $judgment,
        ?array $citedActsHint = null,
        ?string $citationHint = null,
        ?string $courtHint = null,
        ?string $decidedDateHint = null,
    ): void {
        $absolutePath = Storage::disk('public')->path($judgment->pdf_path);

        if (! is_file($absolutePath)) {
            throw new RuntimeException('Judgment PDF could not be read from storage.');
        }

        $plainText = $this->textExtractor->extractFromPath($absolutePath);
        // Summarise from the already-extracted plain text instead of re-sending the
        // PDF file to Gemini. This is far cheaper (no multimodal/document tokens,
        // no Files API round trip) and much less likely to hit a 503 "overloaded".
        $summaryMeta = $this->summarizeText($plainText);

        $this->applyIndexing(
            $judgment,
            $plainText,
            $summaryMeta,
            $citedActsHint,
            $citationHint,
            $courtHint,
            $decidedDateHint,
        );
    }

    /**
     * Embed already-extracted text and merge summary metadata onto a judgment.
     * Shared by the single-upload flow ({@see ingest()}) and the batched
     * multi-case import processor (which summarises many cases per Gemini call
     * but still embeds — and applies — each one individually).
     *
     * @param  array{citation: ?string, court: ?string, decided_date: ?string, summary: ?string, cited_acts: list<string>}  $summaryMeta
     * @param  list<string>|null  $citedActsHint
     */
    public function applyIndexing(
        Judgment $judgment,
        string $plainText,
        array $summaryMeta,
        ?array $citedActsHint = null,
        ?string $citationHint = null,
        ?string $courtHint = null,
        ?string $decidedDateHint = null,
    ): void {
        $titleForEmbed = $judgment->title
            ?: $citationHint
            ?: 'none';

        $embedding = $this->embeddings->embedDocument($titleForEmbed, $plainText);

        $updates = [
            'embedding' => $embedding,
            'summary' => $summaryMeta['summary'] ?? null,
            'cited_acts' => (is_array($citedActsHint) && $citedActsHint !== [])
                ? $citedActsHint
                : ($summaryMeta['cited_acts'] ?? []),
        ];

        if (blank($judgment->title)) {
            $updates['title'] = $citationHint
                ?? $summaryMeta['citation']
                ?? $judgment->title;
        }

        if (blank($judgment->court)) {
            $updates['court'] = $courtHint
                ?? $summaryMeta['court']
                ?? $judgment->court;
        }

        if (blank($judgment->decided_date) && filled($decidedDateHint)) {
            $updates['decided_date'] = $decidedDateHint;
        } elseif (blank($judgment->decided_date) && filled($summaryMeta['decided_date'] ?? null)) {
            $updates['decided_date'] = $summaryMeta['decided_date'];
        }

        $judgment->update($updates);
    }

    /**
     * Create a sliced PDF on the public disk and return its relative path.
     */
    public function storeSlice(
        string $sourceAbsolutePath,
        int $startPage,
        int $endPage,
        string $directory = 'judgments',
    ): string {
        $bytes = $this->pageSlicer->slice($sourceAbsolutePath, $startPage, $endPage);
        $relative = $directory.'/'.uniqid('slice_', true).'.pdf';

        Storage::disk('public')->put($relative, $bytes);

        return $relative;
    }

    /**
     * Summarise a single judgment from already-extracted plain text (no PDF file sent).
     *
     * @return array{citation: ?string, court: ?string, decided_date: ?string, summary: ?string, cited_acts: list<string>}
     */
    public function summarizeText(string $text): array
    {
        $prompt = $this->caseSummaryInstructions()
            ."\n\n=== JUDGMENT TEXT ===\n".$this->truncateForPrompt($text);

        $response = $this->generateWithText(
            self::SUMMARY_MODEL,
            $prompt,
            [
                'responseMimeType' => 'application/json',
                'responseSchema' => $this->singleSummarySchema(),
            ],
            180,
        );

        $text = $response->json('candidates.0.content.parts.0.text');
        $decoded = is_string($text) ? json_decode($text, true) : null;

        if (! is_array($decoded)) {
            return [
                'citation' => null,
                'court' => null,
                'decided_date' => null,
                'summary' => is_string($text) ? trim($text) : null,
                'cited_acts' => [],
            ];
        }

        return $this->normalizeSummaryRow($decoded);
    }

    /**
     * Summarise several judgments in ONE Gemini call to save free-tier request quota.
     * Pass extracted plain text keyed by an arbitrary string (e.g. import item id).
     *
     * @param  array<string, string>  $textsByKey
     * @return array<string, array{citation: ?string, court: ?string, decided_date: ?string, summary: ?string, cited_acts: list<string>}>
     */
    public function summarizeBatch(array $textsByKey): array
    {
        if ($textsByKey === []) {
            return [];
        }

        $schema = [
            'type' => 'object',
            'properties' => [
                'results' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => array_merge(
                            ['key' => ['type' => 'string']],
                            $this->singleSummarySchema()['properties'],
                        ),
                        'required' => array_merge(['key'], $this->singleSummarySchema()['required']),
                    ],
                ],
            ],
            'required' => ['results'],
        ];

        $sections = [];
        foreach ($textsByKey as $key => $text) {
            $sections[] = "===== CASE KEY: {$key} =====\n".$this->truncateForPrompt($text);
        }

        $prompt = <<<'PROMPT'
You are analysing several independent Sri Lankan legal judgments below, each preceded by "===== CASE KEY: <key> =====".
For EVERY case key given, return exactly one object in the "results" array with that same "key", plus citation, court, decided_date (ISO or null), a 2–3 sentence summary of the facts and holding, and cited_acts (act/ordinance names and sections — do not bury acts only inside the summary).
Do not skip, merge, or invent case keys.
PROMPT;

        $response = $this->generateWithText(
            self::SUMMARY_MODEL,
            $prompt."\n\n".implode("\n\n", $sections),
            [
                'responseMimeType' => 'application/json',
                'responseSchema' => $schema,
            ],
            max(180, count($textsByKey) * 60),
        );

        $raw = $response->json('candidates.0.content.parts.0.text');
        $decoded = is_string($raw) ? json_decode($raw, true) : null;

        $results = [];
        foreach (is_array($decoded) ? ($decoded['results'] ?? []) : [] as $row) {
            if (! is_array($row) || ! isset($row['key'])) {
                continue;
            }
            $results[(string) $row['key']] = $this->normalizeSummaryRow($row);
        }

        return $results;
    }

    private function caseSummaryInstructions(): string
    {
        return <<<'PROMPT'
You are analysing a Sri Lankan legal judgment (text already extracted from a PDF).
Return citation, court, decided_date (ISO or null), a 2–3 sentence summary of the facts and holding, and cited_acts as an array of act/ordinance names and sections (do not bury acts only inside the summary).
PROMPT;
    }

    /**
     * @return array{type: string, properties: array<string, mixed>, required: list<string>}
     */
    private function singleSummarySchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'citation' => ['type' => 'string', 'nullable' => true],
                'court' => ['type' => 'string', 'nullable' => true],
                'decided_date' => ['type' => 'string', 'nullable' => true],
                'summary' => ['type' => 'string', 'nullable' => true],
                'cited_acts' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
            ],
            'required' => ['citation', 'court', 'decided_date', 'summary', 'cited_acts'],
        ];
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array{citation: ?string, court: ?string, decided_date: ?string, summary: ?string, cited_acts: list<string>}
     */
    private function normalizeSummaryRow(array $decoded): array
    {
        $acts = $decoded['cited_acts'] ?? [];
        if (! is_array($acts)) {
            $acts = [];
        }

        return [
            'citation' => isset($decoded['citation']) && $decoded['citation'] !== null
                ? (string) $decoded['citation']
                : null,
            'court' => isset($decoded['court']) && $decoded['court'] !== null
                ? (string) $decoded['court']
                : null,
            'decided_date' => isset($decoded['decided_date']) && $decoded['decided_date'] !== null
                ? (string) $decoded['decided_date']
                : null,
            'summary' => isset($decoded['summary']) ? (string) $decoded['summary'] : null,
            'cited_acts' => array_values(array_map('strval', $acts)),
        ];
    }

    private function truncateForPrompt(string $text, int $maxWords = self::SUMMARY_TEXT_WORD_CAP): string
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) <= $maxWords) {
            return implode(' ', $words);
        }

        return implode(' ', array_slice($words, 0, $maxWords))."\n[...truncated for length...]";
    }

    /**
     * @param  array<string, mixed>  $generationConfig
     */
    private function generateWithPdf(
        string $model,
        string $absolutePath,
        string $prompt,
        array $generationConfig,
        int $timeoutSeconds,
    ): \Illuminate\Http\Client\Response {
        // The whole upload+generate attempt runs under ONE key at a time — a
        // file uploaded with key A belongs to key A's project, so if we need
        // to rotate to key B on quota exhaustion we must re-upload with B too.
        return $this->keyPool->run(
            fn (string $apiKey) => $this->generateWithPdfUsingKey(
                $model,
                $absolutePath,
                $prompt,
                $generationConfig,
                $timeoutSeconds,
                $apiKey,
            )
        );
    }

    /**
     * @param  array<string, mixed>  $generationConfig
     */
    private function generateWithPdfUsingKey(
        string $model,
        string $absolutePath,
        string $prompt,
        array $generationConfig,
        int $timeoutSeconds,
        string $apiKey,
    ): \Illuminate\Http\Client\Response {
        $size = filesize($absolutePath) ?: 0;
        $uploadedName = null;

        try {
            if ($size > self::INLINE_MAX_BYTES) {
                $uploaded = $this->files->uploadAndWait(
                    $absolutePath,
                    $apiKey,
                    'application/pdf',
                    basename($absolutePath),
                );
                $uploadedName = $uploaded['name'];
                $pdfPart = [
                    'file_data' => [
                        'mime_type' => 'application/pdf',
                        'file_uri' => $uploaded['uri'],
                    ],
                ];
            } else {
                $pdfBytes = file_get_contents($absolutePath);
                if ($pdfBytes === false) {
                    throw new RuntimeException('Judgment PDF could not be read from disk.');
                }
                $pdfPart = [
                    'inline_data' => [
                        'mime_type' => 'application/pdf',
                        'data' => base64_encode($pdfBytes),
                    ],
                ];
            }

            return $this->postGenerateWithKey($model, [
                'contents' => [
                    [
                        'parts' => [
                            $pdfPart,
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ], $timeoutSeconds, $apiKey);
        } finally {
            if (is_string($uploadedName) && $uploadedName !== '') {
                try {
                    $this->files->delete($uploadedName, $apiKey);
                } catch (\Throwable) {
                    // Best-effort cleanup; files auto-expire on Gemini's side.
                }
            }
        }
    }

    /**
     * Plain-text generateContent call — no PDF file, no Files API round trip.
     * Far cheaper per request than {@see generateWithPdf} and much less likely to 503.
     *
     * @param  array<string, mixed>  $generationConfig
     */
    private function generateWithText(
        string $model,
        string $prompt,
        array $generationConfig,
        int $timeoutSeconds,
    ): \Illuminate\Http\Client\Response {
        return $this->keyPool->run(
            fn (string $apiKey) => $this->postGenerateWithKey($model, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
                'generationConfig' => $generationConfig,
            ], $timeoutSeconds, $apiKey)
        );
    }

    /**
     * POST to generateContent with retry-with-backoff on transient failures
     * (503 "model overloaded" and connection errors). 429 quota errors are
     * never retried here — they bubble up so {@see GeminiKeyPool} can rotate
     * to the next key (or the import can pause cleanly if none are left).
     *
     * @param  array<string, mixed>  $body
     */
    private function postGenerateWithKey(
        string $model,
        array $body,
        int $timeoutSeconds,
        string $apiKey,
    ): \Illuminate\Http\Client\Response {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                $response = Http::timeout($timeoutSeconds)->connectTimeout(30)->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->post(self::API_BASE.'/'.$model.':generateContent', $body);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                if ($attempt >= self::MAX_TRANSIENT_RETRIES) {
                    throw new GeminiUnavailableException(
                        "Cannot reach Gemini ({$model}) after {$attempt} attempt(s): ".$e->getMessage()
                        .' Extra API keys will not help until this machine can resolve generativelanguage.googleapis.com. Check internet/DNS, then Resume.'
                    );
                }
                sleep(min(20, 3 * $attempt));

                continue;
            }

            if ($response->successful()) {
                return $response;
            }

            $quota = GeminiQuotaExceededException::fromApiBody($response->body());
            if ($quota) {
                throw $quota;
            }

            if ($response->status() >= 500 && $attempt < self::MAX_TRANSIENT_RETRIES) {
                sleep(min(20, 3 * $attempt));

                continue;
            }

            throw new RuntimeException(
                "Gemini {$model} request failed: ".$response->body()
            );
        }
    }

    /**
     * @param  array<string, mixed>  $decoded
     * @return array{is_compilation: bool, cases: list<array<string, mixed>>}
     */
    private function normalizeDetection(array $decoded): array
    {
        $cases = [];

        foreach ($decoded['cases'] ?? [] as $case) {
            if (! is_array($case)) {
                continue;
            }

            $acts = $case['cited_acts'] ?? [];
            if (! is_array($acts)) {
                $acts = [];
            }

            $cases[] = [
                'citation' => isset($case['citation']) ? (string) $case['citation'] : null,
                'court' => isset($case['court']) && $case['court'] !== null
                    ? (string) $case['court']
                    : null,
                'decided_date' => isset($case['decided_date']) && $case['decided_date'] !== null
                    ? (string) $case['decided_date']
                    : null,
                'start_page' => (int) ($case['start_page'] ?? 1),
                'end_page' => (int) ($case['end_page'] ?? 1),
                'cited_acts' => array_values(array_map('strval', $acts)),
            ];
        }

        return [
            'is_compilation' => (bool) ($decoded['is_compilation'] ?? false),
            'cases' => $cases,
        ];
    }

}
