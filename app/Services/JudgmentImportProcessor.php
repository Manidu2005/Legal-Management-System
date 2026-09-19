<?php

namespace App\Services;

use App\Exceptions\GeminiQuotaExceededException;
use App\Exceptions\GeminiUnavailableException;
use App\Models\Judgment;
use App\Models\JudgmentImportBatch;
use App\Models\JudgmentImportItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Throwable;

class JudgmentImportProcessor
{
    public function __construct(
        private readonly JudgmentIngestionService $ingestion,
        private readonly PdfTextExtractor $textExtractor,
    ) {}

    /**
     * Process included pending/failed items until done or quota pause.
     *
     * Cases are grouped into small batches and summarised with ONE Gemini
     * call per batch (instead of one call per case) to make far better use
     * of a limited free-tier request quota. Embedding is still one call per
     * case (Gemini meters embeddings per-text either way, so batching that
     * would not save quota).
     *
     * @return array{processed: int, skipped: int, failed: int, paused: bool, message: string}
     */
    public function process(JudgmentImportBatch $batch): array
    {
        // Recover items left in "processing" after a crash, timeout, or DB disconnect.
        $batch->items()
            ->where('status', JudgmentImportItem::STATUS_PROCESSING)
            ->update(['status' => JudgmentImportItem::STATUS_PENDING]);

        $batch->update([
            'status' => JudgmentImportBatch::STATUS_PROCESSING,
            'pause_reason' => null,
        ]);

        $absoluteSource = Storage::disk('public')->path($batch->source_pdf_path);

        if (! is_file($absoluteSource)) {
            $batch->update([
                'status' => JudgmentImportBatch::STATUS_PAUSED,
                'pause_reason' => 'Source PDF is missing from storage. Re-upload the same file to resume.',
            ]);

            return [
                'processed' => 0,
                'skipped' => 0,
                'failed' => 0,
                'paused' => true,
                'message' => $batch->pause_reason,
            ];
        }

        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $items = $batch->items()
            ->where('include', true)
            ->whereIn('status', [
                JudgmentImportItem::STATUS_PENDING,
                JudgmentImportItem::STATUS_FAILED,
            ])
            ->orderBy('position')
            ->get();

        foreach ($items->chunk(JudgmentIngestionService::SUMMARY_BATCH_SIZE) as $chunk) {
            // Re-affirm unlimited execution time each chunk — quota-retry waits
            // (see withQuotaRetry()) can add up to several minutes per chunk,
            // and a hardcoded cap here would cut the script off mid-wait.
            set_time_limit(0);

            // Phase 1 — cheap, no Gemini calls: dedupe, slice the PDF, extract text,
            // create the Judgment row. Collect everything that still needs indexing.
            $prepared = [];

            foreach ($chunk as $item) {
                try {
                    $data = $this->prepareItem($batch, $item, $absoluteSource);
                } catch (Throwable $e) {
                    $item->update([
                        'status' => JudgmentImportItem::STATUS_FAILED,
                        'last_error' => $e->getMessage(),
                    ]);
                    $failed++;

                    continue;
                }

                if ($data === null) {
                    $skipped++;

                    continue;
                }

                $prepared[$item->id] = $data;
            }

            if ($prepared === []) {
                continue;
            }

            // Phase 2 — ONE Gemini call summarising every case in this batch.
            // GeminiKeyPool already waits out short rate limits on the current
            // key and rotates through every configured key before giving up —
            // no need to compound that with another retry loop here.
            $summaries = [];

            try {
                $summaries = $this->ingestion->summarizeBatch(
                    collect($prepared)
                        ->mapWithKeys(fn (array $data, int $itemId) => [(string) $itemId => $data['text']])
                        ->all()
                );
            } catch (GeminiQuotaExceededException|GeminiUnavailableException $e) {
                $this->rollbackPrepared(collect($prepared));

                return $this->pause($batch, $processed, $skipped, $failed, $e->getMessage());
            } catch (Throwable) {
                // Non-quota batch failure (bad JSON, single bad case, etc.) — fall back
                // to summarising each case individually below rather than losing the batch.
                $summaries = [];
            }

            // Phase 3 — per case: embed (unavoidable, 1 call each) + apply metadata.
            foreach ($prepared as $itemId => $data) {
                /** @var JudgmentImportItem $item */
                $item = $data['item'];
                /** @var Judgment $judgment */
                $judgment = $data['judgment'];
                $text = $data['text'];

                try {
                    $meta = $summaries[(string) $itemId] ?? $this->ingestion->summarizeText($text);

                    $this->ingestion->applyIndexing(
                        $judgment,
                        $text,
                        $meta,
                        is_array($item->cited_acts) ? $item->cited_acts : [],
                        $item->citation,
                        $item->court,
                        $item->decided_date?->toDateString(),
                    );

                    $item->update([
                        'status' => JudgmentImportItem::STATUS_COMPLETED,
                        'judgment_id' => $judgment->id,
                        'last_error' => null,
                    ]);
                    $processed++;
                } catch (GeminiQuotaExceededException|GeminiUnavailableException $e) {
                    $this->rollbackPreparedItem($data, $e->getMessage());

                    return $this->pause($batch, $processed, $skipped, $failed, $e->getMessage());
                } catch (Throwable $e) {
                    $this->rollbackPreparedItem($data, $e->getMessage(), markFailed: true);
                    $failed++;
                }
            }
        }

        $stillPending = $batch->pendingItemsCount();

        if ($stillPending === 0) {
            Storage::disk('public')->delete($batch->source_pdf_path);
            $batch->update([
                'status' => JudgmentImportBatch::STATUS_COMPLETED,
                'pause_reason' => null,
            ]);

            return [
                'processed' => $processed,
                'skipped' => $skipped,
                'failed' => $failed,
                'paused' => false,
                'message' => "Import complete. Indexed {$processed}, skipped {$skipped}, failed {$failed}.",
            ];
        }

        $batch->update([
            'status' => JudgmentImportBatch::STATUS_PAUSED,
            'pause_reason' => "Stopped with {$stillPending} case(s) still pending.",
        ]);

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'failed' => $failed,
            'paused' => true,
            'message' => "Indexed {$processed}, skipped {$skipped}, failed {$failed}. {$stillPending} still pending.",
        ];
    }

    /**
     * Dedupe, slice, and extract text for one item. Returns null when the case
     * was already indexed (marked skipped in place). Throws on real failures.
     *
     * @return array{item: JudgmentImportItem, judgment: Judgment, text: string}|null
     */
    private function prepareItem(
        JudgmentImportBatch $batch,
        JudgmentImportItem $item,
        string $absoluteSource,
    ): ?array {
        $item->update([
            'status' => JudgmentImportItem::STATUS_PROCESSING,
            'last_error' => null,
        ]);

        $existing = Judgment::query()
            ->where('source_hash', $batch->source_hash)
            ->where('source_start_page', $item->start_page)
            ->where('source_end_page', $item->end_page)
            ->first();

        if ($existing) {
            $item->update([
                'status' => JudgmentImportItem::STATUS_SKIPPED,
                'judgment_id' => $existing->id,
                'last_error' => null,
            ]);

            return null;
        }

        $slicePath = $this->ingestion->storeSlice(
            $absoluteSource,
            (int) $item->start_page,
            (int) $item->end_page,
        );

        $citedActs = is_array($item->cited_acts) ? $item->cited_acts : [];

        try {
            $judgment = Judgment::create([
                'title' => $item->citation ?: null,
                'category' => $batch->category,
                'court' => $item->court ?: null,
                'decided_date' => $item->decided_date,
                'pdf_path' => $slicePath,
                'cited_acts' => $citedActs,
                'uploaded_by' => $batch->uploaded_by,
                'source_hash' => $batch->source_hash,
                'source_start_page' => $item->start_page,
                'source_end_page' => $item->end_page,
            ]);
        } catch (Throwable $e) {
            Storage::disk('public')->delete($slicePath);
            $existing = Judgment::query()
                ->where('source_hash', $batch->source_hash)
                ->where('source_start_page', $item->start_page)
                ->where('source_end_page', $item->end_page)
                ->first();

            if ($existing) {
                $item->update([
                    'status' => JudgmentImportItem::STATUS_SKIPPED,
                    'judgment_id' => $existing->id,
                    'last_error' => null,
                ]);

                return null;
            }

            throw $e;
        }

        $absoluteSlicePath = Storage::disk('public')->path($slicePath);
        $text = $this->textExtractor->extractFromPath($absoluteSlicePath);

        return ['item' => $item, 'judgment' => $judgment, 'text' => $text];
    }

    /**
     * @param  Collection<int, array{item: JudgmentImportItem, judgment: Judgment, text: string}>  $prepared
     */
    private function rollbackPrepared(Collection $prepared): void
    {
        foreach ($prepared as $data) {
            $this->rollbackPreparedItem($data);
        }
    }

    /**
     * @param  array{item: JudgmentImportItem, judgment: Judgment, text: string}  $data
     */
    private function rollbackPreparedItem(array $data, ?string $errorMessage = null, bool $markFailed = false): void
    {
        /** @var JudgmentImportItem $item */
        $item = $data['item'];
        /** @var Judgment $judgment */
        $judgment = $data['judgment'];

        Storage::disk('public')->delete($judgment->pdf_path);
        $judgment->delete();

        $item->update([
            'status' => $markFailed ? JudgmentImportItem::STATUS_FAILED : JudgmentImportItem::STATUS_PENDING,
            'last_error' => $errorMessage,
        ]);
    }

    /**
     * @return array{processed: int, skipped: int, failed: int, paused: bool, message: string}
     */
    private function pause(JudgmentImportBatch $batch, int $processed, int $skipped, int $failed, string $reason): array
    {
        $batch->update([
            'status' => JudgmentImportBatch::STATUS_PAUSED,
            'pause_reason' => $reason,
        ]);

        $remaining = $batch->pendingItemsCount();

        return [
            'processed' => $processed,
            'skipped' => $skipped,
            'failed' => $failed,
            'paused' => true,
            'message' => "Paused after indexing {$processed} case(s). {$remaining} remaining. {$reason}",
        ];
    }
}
