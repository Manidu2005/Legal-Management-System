<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResearchNoteRequest;
use App\Models\ResearchNote;
use Illuminate\Support\Facades\Gate;

class ResearchNoteController extends Controller
{
    /**
     * Store a new research note.
     *
     * Authorization is handled by StoreResearchNoteRequest::authorize(),
     * which checks the user can view the target case via CaseAccessPolicy.
     */
    public function store(StoreResearchNoteRequest $request)
    {
        ResearchNote::create([
            'case_id' => $request->validated('case_id'),
            'category' => $request->validated('category'),
            'citation' => $request->validated('citation'),
            'court_or_source' => $request->validated('court_or_source'),
            'note' => $request->validated('note'),
            'source_url' => $request->validated('source_url'),
            'added_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('cases.show', $request->validated('case_id'))
            ->withFragment('research')
            ->with('success', 'Research note added successfully.');
    }

    /**
     * Delete a research note.
     *
     * Only the note's author (added_by) or a partner (via manage-users Gate)
     * may delete a research note. The user must also be able to view the case.
     */
    public function destroy(ResearchNote $researchNote)
    {
        $this->authorize('view', $researchNote->legalCase);

        $user = auth()->user();

        if ($researchNote->added_by !== $user->id && ! Gate::allows('manage-users')) {
            abort(403, 'You do not have permission to delete this research note.');
        }

        $caseId = $researchNote->case_id;

        $researchNote->delete();

        return redirect()
            ->route('cases.show', $caseId)
            ->withFragment('research')
            ->with('success', 'Research note deleted successfully.');
    }
}
