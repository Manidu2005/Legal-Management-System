<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\EnsuresCaseAccessCode;
use App\Http\Requests\AttachCaseJudgmentRequest;
use App\Http\Requests\SearchCaseJudgmentsRequest;
use App\Models\CaseJudgment;
use App\Models\LegalCase;
use App\Services\JudgmentSimilaritySearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class CaseJudgmentController extends Controller
{
    use EnsuresCaseAccessCode;

    /**
     * Semantic search over the firm judgment library for this case.
     */
    public function search(
        SearchCaseJudgmentsRequest $request,
        LegalCase $case,
        JudgmentSimilaritySearchService $search,
    ): RedirectResponse {
        if ($redirect = $this->redirectForCaseAccessCode($case)) {
            return $redirect;
        }

        $results = $search->search($request->validated('query'))
            ->map(function (array $row) {
                $judgment = $row['judgment'];

                return [
                    'id' => $judgment->id,
                    'title' => $judgment->title,
                    'category' => $judgment->category,
                    'court' => $judgment->court,
                    'decided_date' => $judgment->decided_date?->format('d M Y'),
                    'summary' => $judgment->summary,
                    'cited_acts' => $judgment->cited_acts ?? [],
                    'source_label' => $judgment->sourceLabel(),
                    'source_url' => $judgment->source_url,
                    'has_pdf' => $judgment->hasLocalPdf(),
                    'source_start_page' => $judgment->source_start_page,
                    'source_end_page' => $judgment->source_end_page,
                    'similarity' => round($row['similarity'], 4),
                ];
            })
            ->all();

        return redirect()
            ->route('cases.show', $case)
            ->withFragment('related-judgments')
            ->with('judgmentSearchQuery', $request->validated('query'))
            ->with('judgmentSearchResults', $results);
    }

    /**
     * Attach a library judgment to this case.
     */
    public function store(AttachCaseJudgmentRequest $request, LegalCase $case): RedirectResponse
    {
        if ($redirect = $this->redirectForCaseAccessCode($case)) {
            return $redirect;
        }

        CaseJudgment::create([
            'legal_case_id' => $case->id,
            'judgment_id' => $request->validated('judgment_id'),
            'relevance_note' => $request->validated('relevance_note'),
            'added_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('cases.show', $case)
            ->withFragment('related-judgments')
            ->with('success', 'Judgment attached to this case.');
    }

    /**
     * Detach a judgment — only the attacher or a partner.
     */
    public function destroy(LegalCase $case, CaseJudgment $caseJudgment): RedirectResponse
    {
        if ($redirect = $this->redirectForCaseAccessCode($case)) {
            return $redirect;
        }

        $this->authorize('view', $case);

        abort_unless(
            $caseJudgment->legal_case_id === $case->id,
            404
        );

        $user = auth()->user();

        if ($caseJudgment->added_by !== $user->id && ! Gate::allows('manage-users')) {
            abort(403, 'You do not have permission to remove this attachment.');
        }

        $caseJudgment->delete();

        return redirect()
            ->route('cases.show', $case)
            ->withFragment('related-judgments')
            ->with('success', 'Judgment attachment removed.');
    }
}
