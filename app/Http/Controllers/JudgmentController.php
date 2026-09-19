<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmJudgmentSplitRequest;
use App\Http\Requests\StoreJudgmentRequest;
use App\Models\Judgment;
use App\Models\JudgmentImportBatch;
use App\Models\JudgmentImportItem;
use App\Services\GeminiKeyPool;
use App\Services\JudgmentDatasetImportService;
use App\Services\JudgmentImportProcessor;
use App\Services\JudgmentIngestionService;
use App\Services\JudgmentSimilaritySearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class JudgmentController extends Controller
{
    /**
     * Firm-wide judgment library — open to any authenticated role.
     */
    public function index(Request $request, GeminiKeyPool $keyPool, JudgmentDatasetImportService $datasets, JudgmentSimilaritySearchService $search): View
    {
        $query = Judgment::with('uploader')->latest();

        if ($request->filled('category')) {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->input('q'));
            $semanticIds = [];

            try {
                $semanticIds = $search->search($term, 30)
                    ->pluck('judgment.id')
                    ->filter()
                    ->values()
                    ->all();
            } catch (\Throwable) {
                $semanticIds = [];
            }

            $query->where(function ($builder) use ($term, $semanticIds) {
                $builder->where('title', 'like', '%'.$term.'%')
                    ->orWhere('summary', 'like', '%'.$term.'%')
                    ->orWhere('court', 'like', '%'.$term.'%')
                    ->orWhere('cited_acts', 'like', '%'.$term.'%');

                if ($semanticIds !== []) {
                    $builder->orWhereIn('id', $semanticIds);
                }
            });
        }

        $judgments = $query->paginate(15)->withQueryString();

        $openBatches = JudgmentImportBatch::query()
            ->withCount([
                'items as pending_count' => fn ($q) => $q->where('include', true)->whereIn('status', [
                    JudgmentImportItem::STATUS_PENDING,
                    JudgmentImportItem::STATUS_FAILED,
                ]),
                'items as done_count' => fn ($q) => $q->whereIn('status', [
                    JudgmentImportItem::STATUS_COMPLETED,
                    JudgmentImportItem::STATUS_SKIPPED,
                ]),
            ])
            ->whereIn('status', [
                JudgmentImportBatch::STATUS_AWAITING_REVIEW,
                JudgmentImportBatch::STATUS_PAUSED,
                JudgmentImportBatch::STATUS_PROCESSING,
            ])
            ->latest()
            ->get();

        return view('judgments.index', [
            'judgments' => $judgments,
            'categories' => Judgment::CATEGORIES,
            'selectedCategory' => $request->input('category'),
            'searchQuery' => $request->input('q'),
            'openBatches' => $openBatches,
            'geminiKeyCount' => $keyPool->count(),
            'geminiKeyStatus' => $keyPool->count() > 0 ? $keyPool->status() : null,
            'datasetStatus' => $datasets->status(),
        ]);
    }

    /**
     * Store a judgment PDF, detect multi-case compilations, then ingest or review.
     */
    public function store(
        StoreJudgmentRequest $request,
        JudgmentIngestionService $ingestion,
        GeminiKeyPool $keyPool,
    ): RedirectResponse {
        set_time_limit(0);
        ignore_user_abort(true);

        if ($keyPool->count() === 0) {
            return redirect()
                ->route('judgments.index')
                ->with('error', 'GEMINI_API_KEY is not set. Add it to your .env file, then run: php artisan config:clear');
        }

        $uploaded = $request->file('pdf');
        $sourceHash = hash_file('sha256', $uploaded->getRealPath());

        // Same file already mid-import → resume instead of re-detecting (saves quota).
        $existingBatch = JudgmentImportBatch::query()
            ->where('source_hash', $sourceHash)
            ->whereIn('status', [
                JudgmentImportBatch::STATUS_AWAITING_REVIEW,
                JudgmentImportBatch::STATUS_PAUSED,
                JudgmentImportBatch::STATUS_PROCESSING,
            ])
            ->latest()
            ->first();

        if ($existingBatch) {
            $path = Storage::disk('public')->putFile('judgments/imports', $uploaded);
            Storage::disk('public')->delete($existingBatch->source_pdf_path);
            $existingBatch->update([
                'source_pdf_path' => $path,
                'original_filename' => $uploaded->getClientOriginalName(),
                'category' => $request->input('category') ?: $existingBatch->category,
            ]);

            if ($existingBatch->status === JudgmentImportBatch::STATUS_AWAITING_REVIEW) {
                return redirect()
                    ->route('judgments.imports.review', $existingBatch)
                    ->with('info', 'Resumed the open review for this same PDF (no duplicate detection call).');
            }

            return redirect()
                ->route('judgments.index')
                ->with('info', 'This PDF matches a paused import. Use Resume to continue without re-indexing finished cases.');
        }

        $path = Storage::disk('public')->putFile('judgments/imports', $uploaded);
        $absolutePath = Storage::disk('public')->path($path);

        try {
            $detection = $ingestion->detectCompilation($absolutePath);
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($path);

            return redirect()
                ->route('judgments.index')
                ->with('error', 'Judgment detection failed: '.$e->getMessage());
        }

        if ($detection['is_compilation'] && count($detection['cases']) > 1) {
            $batch = JudgmentImportBatch::create([
                'source_hash' => $sourceHash,
                'source_pdf_path' => $path,
                'original_filename' => $uploaded->getClientOriginalName(),
                'category' => $request->input('category'),
                'status' => JudgmentImportBatch::STATUS_AWAITING_REVIEW,
                'uploaded_by' => $request->user()->id,
            ]);

            foreach ($detection['cases'] as $index => $case) {
                $already = Judgment::query()
                    ->where('source_hash', $sourceHash)
                    ->where('source_start_page', (int) $case['start_page'])
                    ->where('source_end_page', (int) $case['end_page'])
                    ->first();

                JudgmentImportItem::create([
                    'batch_id' => $batch->id,
                    'position' => $index,
                    'citation' => $case['citation'] ?? null,
                    'court' => $case['court'] ?? null,
                    'decided_date' => $case['decided_date'] ?? null,
                    'start_page' => (int) $case['start_page'],
                    'end_page' => (int) $case['end_page'],
                    'cited_acts' => $case['cited_acts'] ?? [],
                    'include' => true,
                    'status' => $already
                        ? JudgmentImportItem::STATUS_SKIPPED
                        : JudgmentImportItem::STATUS_PENDING,
                    'judgment_id' => $already?->id,
                ]);
            }

            return redirect()
                ->route('judgments.imports.review', $batch)
                ->with('info', 'Multi-judgment volume detected. Review page ranges, then confirm. Already-indexed pages are marked skipped.');
        }

        // Single judgment flow
        $caseMeta = $detection['cases'][0] ?? null;
        $startPage = (int) ($caseMeta['start_page'] ?? 1);
        $endPage = (int) ($caseMeta['end_page'] ?? $startPage);

        $duplicate = Judgment::query()
            ->where('source_hash', $sourceHash)
            ->where('source_start_page', $startPage)
            ->where('source_end_page', $endPage)
            ->first();

        if ($duplicate) {
            Storage::disk('public')->delete($path);

            return redirect()
                ->route('judgments.index')
                ->with('info', 'This PDF was already indexed as “'.($duplicate->title ?: 'Untitled').'”. No duplicate created.');
        }

        // Move from imports/ to judgments/ for singles
        $finalPath = 'judgments/'.basename($path);
        Storage::disk('public')->move($path, $finalPath);

        $judgment = Judgment::create([
            'title' => $request->input('title') ?: ($caseMeta['citation'] ?? null),
            'category' => $request->input('category'),
            'court' => $request->input('court') ?: ($caseMeta['court'] ?? null),
            'decided_date' => $request->input('decided_date') ?: ($caseMeta['decided_date'] ?? null),
            'pdf_path' => $finalPath,
            'cited_acts' => $caseMeta['cited_acts'] ?? [],
            'uploaded_by' => $request->user()->id,
            'source_hash' => $sourceHash,
            'source_start_page' => $startPage,
            'source_end_page' => $endPage,
        ]);

        try {
            $ingestion->ingest(
                $judgment,
                $caseMeta['cited_acts'] ?? null,
                $caseMeta['citation'] ?? null,
                $caseMeta['court'] ?? null,
                $caseMeta['decided_date'] ?? null,
            );
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($finalPath);
            $judgment->delete();

            return redirect()
                ->route('judgments.index')
                ->with('error', 'Judgment indexing failed: '.$e->getMessage());
        }

        return redirect()
            ->route('judgments.index')
            ->with('success', 'Judgment uploaded and indexed successfully.');
    }

    public function reviewImport(JudgmentImportBatch $batch): View|RedirectResponse
    {
        if ($batch->status === JudgmentImportBatch::STATUS_CANCELLED) {
            return redirect()->route('judgments.index')->with('error', 'That import was cancelled.');
        }

        $batch->load('items');

        return view('judgments.review-split', [
            'batch' => $batch,
        ]);
    }

    public function confirmImport(
        ConfirmJudgmentSplitRequest $request,
        JudgmentImportBatch $batch,
        JudgmentImportProcessor $processor,
    ): RedirectResponse {
        set_time_limit(0);
        ignore_user_abort(true);

        if (! $batch->isOpen() && $batch->status !== JudgmentImportBatch::STATUS_AWAITING_REVIEW) {
            return redirect()
                ->route('judgments.index')
                ->with('error', 'This import can no longer be confirmed.');
        }

        foreach ($request->validated('cases') as $index => $case) {
            $item = $batch->items()->where('position', $index)->first();
            if (! $item) {
                continue;
            }

            // Already linked to a judgment for this fingerprint — never re-queue.
            if ($item->judgment_id) {
                $item->update([
                    'citation' => $case['citation'] ?: $item->citation,
                    'court' => $case['court'] ?: $item->court,
                    'include' => false,
                    'status' => JudgmentImportItem::STATUS_SKIPPED,
                ]);
                continue;
            }

            $include = (bool) ($case['include'] ?? false);
            $citedActs = $this->parseCitedActsInput($case['cited_acts'] ?? null);

            $item->update([
                'citation' => $case['citation'] ?: null,
                'court' => $case['court'] ?: null,
                'decided_date' => $case['decided_date'] ?: null,
                'start_page' => (int) $case['start_page'],
                'end_page' => (int) $case['end_page'],
                'cited_acts' => $citedActs,
                'include' => $include,
                'status' => $include
                    ? JudgmentImportItem::STATUS_PENDING
                    : JudgmentImportItem::STATUS_SKIPPED,
            ]);
        }

        $result = $processor->process($batch->fresh());

        return redirect()
            ->route('judgments.index')
            ->with($result['paused'] ? 'error' : 'success', $result['message']);
    }

    public function resumeImport(
        JudgmentImportBatch $batch,
        JudgmentImportProcessor $processor,
    ): RedirectResponse {
        set_time_limit(0);
        ignore_user_abort(true);

        if (! in_array($batch->status, [
            JudgmentImportBatch::STATUS_PAUSED,
            JudgmentImportBatch::STATUS_PROCESSING,
        ], true)) {
            return redirect()
                ->route('judgments.index')
                ->with('error', 'Only paused imports can be resumed.');
        }

        if (! Storage::disk('public')->exists($batch->source_pdf_path)) {
            return redirect()
                ->route('judgments.index')
                ->with('error', 'Source PDF missing. Re-upload the same file — it will attach to this paused import.');
        }

        $result = $processor->process($batch);

        return redirect()
            ->route('judgments.index')
            ->with($result['paused'] ? 'error' : 'success', $result['message']);
    }

    public function importDatasets(Request $request, JudgmentDatasetImportService $importer): RedirectResponse
    {
        Gate::authorize('manage-taxonomy');
        set_time_limit(0);
        ignore_user_abort(true);

        $result = $importer->import(
            $request->user(),
            JudgmentDatasetImportService::SOURCES,
            40,
            true,
        );

        return redirect()
            ->route('judgments.index')
            ->with($result['paused'] ? 'error' : 'success', $result['message']);
    }

    public function embedDatasets(JudgmentDatasetImportService $importer): RedirectResponse
    {
        Gate::authorize('manage-taxonomy');
        set_time_limit(0);
        ignore_user_abort(true);

        $result = $importer->embedPending(30);

        return redirect()
            ->route('judgments.index')
            ->with($result['paused'] ? 'error' : 'success', $result['message']);
    }

    public function cancelImport(JudgmentImportBatch $batch): RedirectResponse
    {
        Storage::disk('public')->delete($batch->source_pdf_path);

        $batch->items()
            ->whereIn('status', [
                JudgmentImportItem::STATUS_PENDING,
                JudgmentImportItem::STATUS_FAILED,
                JudgmentImportItem::STATUS_PROCESSING,
            ])
            ->update(['status' => JudgmentImportItem::STATUS_SKIPPED]);

        $batch->update([
            'status' => JudgmentImportBatch::STATUS_CANCELLED,
            'pause_reason' => 'Cancelled by user.',
        ]);

        return redirect()
            ->route('judgments.index')
            ->with('success', 'Import cancelled. Already-indexed judgments were kept; remaining cases were discarded.');
    }

    public function download(Judgment $judgment): StreamedResponse|RedirectResponse
    {
        if ($judgment->hasLocalPdf() && Storage::disk('public')->exists($judgment->pdf_path)) {
            $filename = $this->sanitizeDownloadFilename($judgment->title ?: 'judgment').'.pdf';

            return Storage::disk('public')->download($judgment->pdf_path, $filename);
        }

        if (filled($judgment->source_url)) {
            return redirect()->away($judgment->source_url);
        }

        return redirect()
            ->back()
            ->with('error', 'The PDF could not be found on the server.');
    }

    /**
     * Case titles/citations often contain "/" (e.g. "S.C. Appeal No. 123/2010")
     * or other characters that Symfony's Content-Disposition header builder
     * rejects outright ("/" and "\" throw an InvalidArgumentException). Strip
     * anything unsafe for a filename while keeping it readable.
     */
    private function sanitizeDownloadFilename(string $title): string
    {
        // Replace path separators and other filesystem-unsafe characters with a dash.
        $safe = str_replace(['/', '\\'], '-', $title);
        $safe = preg_replace('/[<>:"|?*\x00-\x1F]/', '', $safe) ?? $safe;
        $safe = trim(preg_replace('/\s+/', ' ', $safe) ?? $safe);

        return $safe !== '' ? $safe : 'judgment';
    }

    public function destroy(Judgment $judgment): RedirectResponse
    {
        if (filled($judgment->pdf_path)) {
            Storage::disk('public')->delete($judgment->pdf_path);
        }
        $judgment->delete();

        return redirect()
            ->route('judgments.index')
            ->with('success', 'Judgment deleted successfully.');
    }

    /**
     * @return list<string>
     */
    private function parseCitedActsInput(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }

        if (! is_string($raw) || trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            'trim',
            preg_split('/\r\n|\r|\n|;/', $raw) ?: []
        )));
    }
}
