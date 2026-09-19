<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Paginates Hugging Face Dataset Server rows without pulling parquet locally.
 *
 * @see https://huggingface.co/docs/datasets-server
 */
class HuggingFaceDatasetClient
{
    public const PAGE_SIZE = 100;

    private const BASE = 'https://datasets-server.huggingface.co/rows';

    private const MAX_ATTEMPTS = 4;

    private const RETRY_STATUSES = [429, 502, 503, 504];

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function page(string $dataset, string $split, int $offset, int $length = self::PAGE_SIZE, string $config = 'default'): array
    {
        $length = max(1, min(self::PAGE_SIZE, $length));
        $response = null;

        for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
            try {
                $response = Http::timeout(60)->connectTimeout(20)->get(self::BASE, [
                    'dataset' => $dataset,
                    'config' => $config,
                    'split' => $split,
                    'offset' => $offset,
                    'length' => $length,
                ]);
            } catch (ConnectionException $e) {
                if ($attempt >= self::MAX_ATTEMPTS) {
                    throw new RuntimeException(
                        "Hugging Face dataset request failed ({$dataset} / {$split}): ".$e->getMessage()
                    );
                }
                $this->backoff($attempt);

                continue;
            }

            if ($response->successful()) {
                break;
            }

            if (in_array($response->status(), self::RETRY_STATUSES, true) && $attempt < self::MAX_ATTEMPTS) {
                $this->backoff($attempt);

                continue;
            }

            throw new RuntimeException($this->failureMessage($dataset, $split, $response->status(), $response->body()));
        }

        $rows = [];
        foreach ($response->json('rows') ?? [] as $entry) {
            $row = $entry['row'] ?? null;
            if (is_array($row)) {
                $rows[] = $row;
            }
        }

        return [
            'rows' => $rows,
            'total' => (int) ($response->json('num_rows_total') ?? count($rows)),
        ];
    }

    private function backoff(int $attempt): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        sleep(min(16, 2 ** $attempt));
    }

    private function failureMessage(string $dataset, string $split, int $status, string $body): string
    {
        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($body)) ?? '');
        $detail = $plain !== '' ? Str::limit($plain, 160) : 'no response body';

        return "Hugging Face dataset request failed ({$dataset} / {$split}): HTTP {$status} {$detail}";
    }
}
