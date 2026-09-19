<?php

namespace App\Services;

use App\Exceptions\GeminiQuotaExceededException;
use App\Exceptions\GeminiUnavailableException;
use App\Models\Judgment;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Imports public Sri Lankan case-law datasets into the Judgment Library and
 * embeds them with Gemini so related-judgment / library search keeps working.
 *
 * Sources:
 * - navodPeiris/sri-lanka-case-law (CLR, CLW, NLR, SLR summaries)
 * - nuuuwan Court of Appeal + Supreme Court Hugging Face docs
 */
class JudgmentDatasetImportService
{
    public const SOURCE_NAVOD = 'navod';

    public const SOURCE_APPEAL = 'appeal';

    public const SOURCE_SUPREME = 'supreme';

    public const SOURCES = [
        self::SOURCE_NAVOD,
        self::SOURCE_APPEAL,
        self::SOURCE_SUPREME,
    ];

    private const OFFSET_CACHE = 'judgment_dataset_import_offsets';

    private const DATASETS = [
        self::SOURCE_NAVOD => [
            'dataset' => 'navodPeiris/sri-lanka-case-law',
            'split' => 'combined',
            'label' => 'navod_sri_lanka_case_law',
        ],
        self::SOURCE_APPEAL => [
            'dataset' => 'nuuuwan/lk-appeal-court-judgements-docs',
            'split' => 'train',
            'label' => 'nuuuwan_appeal_court',
        ],
        self::SOURCE_SUPREME => [
            'dataset' => 'nuuuwan/lk-supreme-court-judgements-docs',
            'split' => 'train',
            'label' => 'nuuuwan_supreme_court',
        ],
    ];

    public function __construct(
        private readonly HuggingFaceDatasetClient $huggingface,
        private readonly GeminiEmbeddingService $embeddings,
    ) {}

    /**
     * @param  list<string>  $sources
     * @return array{imported: int, skipped: int, embedded: int, paused: bool, message: string, offsets: array<string, int>}
     */
    public function import(
        User $uploader,
        array $sources = self::SOURCES,
        int $limit = 100,
        bool $embed = true,
    ): array {
        $sources = array_values(array_intersect(self::SOURCES, $sources));
        if ($sources === []) {
            $sources = self::SOURCES;
        }

        $imported = 0;
        $skipped = 0;
        $embedded = 0;
        $offsets = $this->offsets();
        $warnings = [];

        foreach ($sources as $source) {
            $pageOffset = (int) ($offsets[$source] ?? 0);
            $meta = self::DATASETS[$source];

            while ($imported < $limit) {
                $remaining = $limit - $imported;

                try {
                    $page = $this->huggingface->page(
                        $meta['dataset'],
                        $meta['split'],
                        $pageOffset,
                        min(HuggingFaceDatasetClient::PAGE_SIZE, $remaining),
                    );
                } catch (RuntimeException $e) {
                    $warnings[] = $e->getMessage();

                    break;
                }

                if ($page['rows'] === []) {
                    $pageOffset = max($pageOffset, $page['total']);
                    $offsets[$source] = $pageOffset;
                    $this->storeOffsets($offsets);

                    break;
                }

                foreach ($page['rows'] as $row) {
                    if ($imported >= $limit) {
                        break;
                    }

                    $mapped = $source === self::SOURCE_NAVOD
                        ? $this->mapNavod($row)
                        : $this->mapNuuuwan($row, $meta['label']);

                    if ($mapped === null) {
                        $skipped++;
                        $pageOffset++;

                        continue;
                    }

                    $existing = Judgment::query()->where('external_id', $mapped['external_id'])->first();
                    if ($existing) {
                        $skipped++;
                        $pageOffset++;

                        if ($embed && empty($existing->embedding)) {
                            try {
                                $this->embedJudgment($existing, $mapped['embed_text']);
                                $embedded++;
                            } catch (GeminiQuotaExceededException|GeminiUnavailableException $e) {
                                $offsets[$source] = $pageOffset;
                                $this->storeOffsets($offsets);

                                return $this->result($imported, $skipped, $embedded, true, $e->getMessage(), $offsets);
                            }
                        }

                        continue;
                    }

                    $judgment = Judgment::create([
                        'title' => $mapped['title'],
                        'category' => Judgment::CATEGORIES[0],
                        'court' => $mapped['court'],
                        'decided_date' => $mapped['decided_date'],
                        'pdf_path' => '',
                        'summary' => $mapped['summary'],
                        'cited_acts' => $mapped['cited_acts'],
                        'uploaded_by' => $uploader->id,
                        'source_hash' => hash('sha256', $mapped['external_id']),
                        'source_start_page' => 1,
                        'source_end_page' => 1,
                        'external_id' => $mapped['external_id'],
                        'source_dataset' => $mapped['source_dataset'],
                        'source_url' => $mapped['source_url'],
                    ]);
                    $imported++;
                    $pageOffset++;

                    if ($embed) {
                        try {
                            $this->embedJudgment($judgment, $mapped['embed_text']);
                            $embedded++;
                        } catch (GeminiQuotaExceededException|GeminiUnavailableException $e) {
                            $offsets[$source] = $pageOffset;
                            $this->storeOffsets($offsets);

                            return $this->result($imported, $skipped, $embedded, true, $e->getMessage(), $offsets);
                        }
                    }
                }

                $offsets[$source] = $pageOffset;
                $this->storeOffsets($offsets);

                if ($pageOffset >= $page['total']) {
                    break;
                }
            }
        }

        $this->storeOffsets($offsets);

        $message = "Imported {$imported}, skipped {$skipped}, embedded {$embedded}.";
        if ($warnings !== []) {
            $message .= ' '.implode(' ', array_unique($warnings));
        }

        return $this->result($imported, $skipped, $embedded, false, $message, $offsets);
    }

    /**
     * Embed library rows that came from datasets but still lack a Gemini vector.
     *
     * @return array{embedded: int, remaining: int, paused: bool, message: string}
     */
    public function embedPending(int $limit = 50): array
    {
        $pending = Judgment::query()
            ->whereNotNull('external_id')
            ->whereNull('embedding')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $embedded = 0;

        foreach ($pending as $judgment) {
            try {
                $this->embedJudgment($judgment, $this->embedTextFromJudgment($judgment));
                $embedded++;
            } catch (GeminiQuotaExceededException|GeminiUnavailableException $e) {
                $remaining = Judgment::query()->whereNotNull('external_id')->whereNull('embedding')->count();

                return [
                    'embedded' => $embedded,
                    'remaining' => $remaining,
                    'paused' => true,
                    'message' => $e->getMessage(),
                ];
            }
        }

        $remaining = Judgment::query()->whereNotNull('external_id')->whereNull('embedding')->count();

        return [
            'embedded' => $embedded,
            'remaining' => $remaining,
            'paused' => false,
            'message' => "Embedded {$embedded} judgment(s). {$remaining} still waiting for Gemini.",
        ];
    }

    /**
     * @return array<string, int>
     */
    public function offsets(): array
    {
        $stored = Cache::get(self::OFFSET_CACHE, []);

        return is_array($stored) ? $stored : [];
    }

    public function resetProgress(): void
    {
        Cache::forget(self::OFFSET_CACHE);
    }

    /**
     * @return array{imported: int, skipped: int, pending_embeddings: int, offsets: array<string, int>}
     */
    public function status(): array
    {
        return [
            'imported' => Judgment::query()->whereNotNull('external_id')->count(),
            'skipped' => 0,
            'pending_embeddings' => Judgment::query()->whereNotNull('external_id')->whereNull('embedding')->count(),
            'offsets' => $this->offsets(),
        ];
    }

    private function embedJudgment(Judgment $judgment, string $text): void
    {
        $vector = $this->embeddings->embedDocument($judgment->title, $text);
        $judgment->update(['embedding' => $vector]);
    }

    private function embedTextFromJudgment(Judgment $judgment): string
    {
        $parts = array_filter([
            $judgment->title,
            $judgment->court,
            $judgment->summary,
            is_array($judgment->cited_acts) ? implode('; ', $judgment->cited_acts) : null,
        ]);

        return implode("\n", $parts);
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{external_id: string, source_dataset: string, title: string, court: ?string, decided_date: ?string, summary: ?string, cited_acts: list<string>, source_url: ?string, embed_text: string}|null
     */
    private function mapNavod(array $row): ?array
    {
        $title = trim((string) ($row['case_title'] ?? ''));
        $caseNo = trim((string) ($row['case_no'] ?? ''));
        if ($title === '' && $caseNo === '') {
            return null;
        }

        $source = trim((string) ($row['source'] ?? 'unknown'));
        $externalId = $this->clip('navod:'.$source.':'.($caseNo !== '' ? $caseNo : md5($title)), 255);
        $summary = $this->firstFilled($row['full_summary'] ?? null, $row['short_summary'] ?? null);
        $cited = [];
        foreach ($row['laws_referred'] ?? [] as $law) {
            if (! is_array($law)) {
                continue;
            }
            $label = trim(trim((string) ($law['law'] ?? '')).' '.trim((string) ($law['chapter_or_section'] ?? '')));
            if ($label !== '') {
                $cited[] = $label;
            }
        }

        $court = $this->firstFilled($row['court'] ?? null, $this->navodCourtLabel($source));
        $displayTitle = $title !== '' ? $title : $caseNo;
        $embed = implode("\n", array_filter([
            $displayTitle,
            $caseNo,
            trim((string) ($row['court_citation'] ?? '')),
            $court,
            trim((string) ($row['judges'] ?? '')),
            $summary,
            implode('; ', $cited),
            $this->namedListText($row['citations'] ?? []),
            $this->namedListText($row['arguments'] ?? []),
            $this->namedListText($row['judgements'] ?? []),
        ]));

        return [
            'external_id' => $externalId,
            'source_dataset' => 'navod_sri_lanka_case_law',
            'title' => $this->clip($displayTitle, 250),
            'court' => $this->clipNullable($court),
            'decided_date' => $this->parseDate($row['decided_date'] ?? null),
            'summary' => $summary,
            'cited_acts' => array_values(array_unique($cited)),
            'source_url' => 'https://huggingface.co/datasets/navodPeiris/sri-lanka-case-law',
            'embed_text' => $embed,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array{external_id: string, source_dataset: string, title: string, court: ?string, decided_date: ?string, summary: ?string, cited_acts: list<string>, source_url: ?string, embed_text: string}|null
     */
    private function mapNuuuwan(array $row, string $label): ?array
    {
        $docId = trim((string) ($row['doc_id'] ?? ''));
        $num = trim((string) ($row['num'] ?? ''));
        $parties = trim((string) ($row['parties'] ?? ''));
        if ($docId === '' && $num === '' && $parties === '') {
            return null;
        }

        $shortParties = $this->shortLine($parties);
        $title = match (true) {
            $num !== '' && $shortParties !== null => $num.' — '.$shortParties,
            $num !== '' => $num,
            $shortParties !== null => $shortParties,
            $docId !== '' => $docId,
            default => 'Untitled judgment',
        };
        $cited = [];
        $legislation = trim((string) ($row['legistation'] ?? ''));
        if ($legislation !== '') {
            foreach (preg_split('/[;|]+/', $legislation) ?: [] as $piece) {
                $piece = trim($piece);
                if ($piece !== '') {
                    $cited[] = $piece;
                }
            }
        }

        $court = str_contains($label, 'supreme')
            ? 'Supreme Court of Sri Lanka'
            : 'Court of Appeal of Sri Lanka';

        $bodyText = $this->firstFilled(
            $row['text'] ?? null,
            $row['chunk_text'] ?? null,
            $row['body'] ?? null,
        );

        $summaryParts = array_filter([
            trim((string) ($row['description'] ?? '')),
            $parties,
            trim((string) ($row['judgement_by'] ?? '')),
            trim((string) ($row['keywords'] ?? '')),
            $bodyText,
        ]);

        $embed = implode("\n", array_filter([
            $title,
            $court,
            implode("\n", $summaryParts),
            implode('; ', $cited),
        ]));

        return [
            'external_id' => $this->clip('nuuuwan:'.$label.':'.($docId !== '' ? $docId : md5($title)), 255),
            'source_dataset' => $label,
            'title' => $this->clip($title, 250),
            'court' => $court,
            'decided_date' => $this->parseDate($row['date_str'] ?? null),
            'summary' => implode("\n", $summaryParts) ?: null,
            'cited_acts' => $cited,
            'source_url' => $this->firstFilled($row['url_pdf'] ?? null, $row['url_metadata'] ?? null),
            'embed_text' => $embed,
        ];
    }

    private function navodCourtLabel(string $source): string
    {
        return match (strtoupper($source)) {
            'CLR' => 'Ceylon Law Reports',
            'CLW' => 'Ceylon Law Weekly',
            'NLR' => 'New Law Reports',
            'SLR' => 'Sri Lanka Law Reports',
            default => 'Sri Lanka case law',
        };
    }

    private function parseDate(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = substr(trim($value), 0, 10);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }

    private function firstFilled(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param  mixed  $items
     */
    private function namedListText(mixed $items): string
    {
        if (! is_array($items)) {
            return '';
        }

        $parts = [];
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }
            $piece = trim(trim((string) ($item['title'] ?? '')).' '.trim((string) ($item['description'] ?? $item['reason_cited'] ?? '')));
            if ($piece !== '') {
                $parts[] = $piece;
            }
        }

        return implode("\n", $parts);
    }

    private function shortLine(?string $value, int $max = 80): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
        if ($value === '') {
            return null;
        }

        return $this->clip($value, $max);
    }

    private function clip(string $value, int $max = 255): string
    {
        $value = trim($value);
        if (mb_strlen($value) <= $max) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $max - 1)).'…';
    }

    private function clipNullable(?string $value, int $max = 255): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return $this->clip($value, $max);
    }

    /**
     * @param  array<string, int>  $offsets
     */
    private function storeOffsets(array $offsets): void
    {
        Cache::forever(self::OFFSET_CACHE, $offsets);
    }

    /**
     * @param  array<string, int>  $offsets
     * @return array{imported: int, skipped: int, embedded: int, paused: bool, message: string, offsets: array<string, int>}
     */
    private function result(int $imported, int $skipped, int $embedded, bool $paused, string $message, array $offsets): array
    {
        return compact('imported', 'skipped', 'embedded', 'paused', 'message', 'offsets');
    }
}
