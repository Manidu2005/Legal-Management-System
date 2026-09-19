<?php

namespace App\Services;

use App\Exceptions\GeminiQuotaExceededException;
use App\Exceptions\GeminiUnavailableException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Shared Gemini text embedding helpers for judgment indexing and search.
 *
 * Model string confirmed as stable `gemini-embedding-2` from the Gemini API
 * models catalog (https://ai.google.dev/gemini-api/docs/models/gemini-embedding-2,
 * GA / Stable as of April 2026). The older `gemini-embedding-2-preview` id is
 * the preview alias only — do not use it for production calls.
 *
 * Task prefixes are prompt-based (gemini-embedding-2 has no task_type param).
 * Document / query formats taken from:
 * https://ai.google.dev/gemini-api/docs/embeddings (Task types with Embeddings 2).
 */
class GeminiEmbeddingService
{
    // Confirmed stable model code — see class docblock.
    public const MODEL = 'gemini-embedding-2';

    public const OUTPUT_DIMENSIONALITY = 768;

    private const API_BASE = 'https://generativelanguage.googleapis.com/v1beta/models';

    /** Soft word budget per chunk so each request stays under the 8,192-token limit. */
    private const CHUNK_WORDS = 2500;

    private const CHUNK_OVERLAP_WORDS = 200;

    /** Retries for transient 5xx / connection failures (not for 429 quota). */
    private const MAX_TRANSIENT_RETRIES = 3;

    public function __construct(
        private readonly GeminiKeyPool $keyPool,
    ) {}

    /**
     * Embed a document for retrieval: `title: {title} | text: {content}`.
     *
     * @return list<float>
     */
    public function embedDocument(?string $title, string $text): array
    {
        $title = filled($title) ? $title : 'none';
        $chunks = $this->chunkWords($text);

        if ($chunks === []) {
            $chunks = [''];
        }

        $vectors = [];

        foreach ($chunks as $chunk) {
            $payload = "title: {$title} | text: {$chunk}";
            $vectors[] = $this->embedRaw($payload);
        }

        if (count($vectors) === 1) {
            return $this->normalize($vectors[0]);
        }

        return $this->averageAndNormalize($vectors);
    }

    /**
     * Embed a search query: `task: search result | query: {content}`.
     *
     * @return list<float>
     */
    public function embedQuery(string $query): array
    {
        return $this->normalize(
            $this->embedRaw('task: search result | query: '.$query)
        );
    }

    /**
     * @return list<float>
     */
    public function embedRaw(string $text): array
    {
        return $this->keyPool->run(fn (string $apiKey) => $this->embedRawWithKey($text, $apiKey));
    }

    /**
     * @return list<float>
     */
    private function embedRawWithKey(string $text, string $apiKey): array
    {
        $attempt = 0;
        $response = null;

        while (true) {
            $attempt++;

            try {
                $response = Http::timeout(120)->connectTimeout(30)->withHeaders([
                    'x-goog-api-key' => $apiKey,
                    'Content-Type' => 'application/json',
                ])->post(self::API_BASE.'/'.self::MODEL.':embedContent', [
                    'content' => [
                        'parts' => [
                            ['text' => $text],
                        ],
                    ],
                    'output_dimensionality' => self::OUTPUT_DIMENSIONALITY,
                ]);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                if ($attempt >= self::MAX_TRANSIENT_RETRIES) {
                    throw new GeminiUnavailableException(
                        'Cannot reach Gemini embeddings after '.$attempt.' attempt(s): '.$e->getMessage()
                        .' Extra API keys will not help until this machine can resolve generativelanguage.googleapis.com. Check internet/DNS, then Resume.'
                    );
                }
                sleep(min(20, 3 * $attempt));

                continue;
            }

            if ($response->successful()) {
                break;
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
                'Gemini embedding request failed: '.$response->body()
            );
        }

        $values = $response->json('embedding.values')
            ?? $response->json('embeddings.0.values');

        if (! is_array($values) || $values === []) {
            throw new RuntimeException('Gemini embedding response did not include vector values.');
        }

        return array_map('floatval', $values);
    }

    /**
     * @return list<string>
     */
    public function chunkWords(string $text): array
    {
        $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return [];
        }

        if (count($words) <= self::CHUNK_WORDS) {
            return [implode(' ', $words)];
        }

        $chunks = [];
        $step = max(1, self::CHUNK_WORDS - self::CHUNK_OVERLAP_WORDS);

        for ($offset = 0; $offset < count($words); $offset += $step) {
            $slice = array_slice($words, $offset, self::CHUNK_WORDS);
            if ($slice === []) {
                break;
            }
            $chunks[] = implode(' ', $slice);
            if ($offset + self::CHUNK_WORDS >= count($words)) {
                break;
            }
        }

        return $chunks;
    }

    /**
     * @param  list<list<float>>  $vectors
     * @return list<float>
     */
    public function averageAndNormalize(array $vectors): array
    {
        $dims = count($vectors[0]);
        $avg = array_fill(0, $dims, 0.0);

        foreach ($vectors as $vector) {
            $normalized = $this->normalize($vector);
            for ($i = 0; $i < $dims; $i++) {
                $avg[$i] += $normalized[$i] ?? 0.0;
            }
        }

        $count = count($vectors);
        for ($i = 0; $i < $dims; $i++) {
            $avg[$i] /= $count;
        }

        return $this->normalize($avg);
    }

    /**
     * @param  list<float>  $vector
     * @return list<float>
     */
    public function normalize(array $vector): array
    {
        $norm = 0.0;
        foreach ($vector as $value) {
            $norm += $value * $value;
        }
        $norm = sqrt($norm);

        if ($norm <= 0.0) {
            return $vector;
        }

        return array_map(fn (float $v) => $v / $norm, $vector);
    }

}
