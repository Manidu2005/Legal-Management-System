<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourtDateRequest;
use App\Models\CourtDate;
use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CourtDateController extends Controller
{
    /**
     * Get the case IDs assigned to the current user (for associates).
     * Returns null for partners/clerks (meaning "no filter, show all").
     */
    private function assignedCaseIds(): ?array
    {
        $user = Auth::user();

        if ($user->role === 'associate') {
            return LegalCase::where('assigned_attorney_id', $user->id)
                ->pluck('id')
                ->all();
        }

        return null; // partners & clerks see everything
    }

    /**
     * Display a listing of court dates grouped by month.
     * Associates only see dates for cases assigned to them.
     */
    public function index(): View
    {
        $query = CourtDate::with(['legalCase.client', 'legalCase.assignedAttorney'])
            ->orderBy('date', 'asc');

        $caseIds = $this->assignedCaseIds();
        if ($caseIds !== null) {
            $query->whereIn('case_id', $caseIds);
        }

        $courtDates = $query->get();

        // Separate into upcoming and past
        $now = now();
        $upcoming = $courtDates->filter(fn ($cd) => $cd->date->gte($now));
        $past = $courtDates->filter(fn ($cd) => $cd->date->lt($now));

        // Group upcoming by month
        $upcomingByMonth = $upcoming->groupBy(fn ($cd) => $cd->date->format('F Y'));

        // Group past by month (most recent first)
        $pastByMonth = $past->sortByDesc('date')->groupBy(fn ($cd) => $cd->date->format('F Y'));

        // Build a map of all court-date events keyed by date string for the Alpine calendar widget
        $calendarEvents = $courtDates
            ->groupBy(fn ($cd) => $cd->date->toDateString())
            ->map(fn ($events) => $events->map(fn ($event) => [
                'time' => $event->date->format('g:i A'),
                'type' => $event->type === 'trial_date' ? __('Trial Date') : __('Calling Date'),
                'is_trial' => $event->type === 'trial_date',
                'case' => $event->legalCase?->display_name ?? __('Unknown Client'),
            ])->values()->all())
            ->all();

        return view('court-dates.index', compact(
            'upcomingByMonth',
            'pastByMonth',
            'calendarEvents'
        ));
    }

    /**
     * Show the form for creating a new court date.
     * Associates only see their own cases in the dropdown.
     */
    public function create(): View
    {
        $query = LegalCase::with('client')
            ->whereNotIn('status', ['case_closed'])
            ->orderBy('id', 'desc');

        $caseIds = $this->assignedCaseIds();
        if ($caseIds !== null) {
            $query->whereIn('id', $caseIds);
        }

        $cases = $query->get();

        return view('court-dates.create', compact('cases'));
    }

    /**
     * Store a newly created court date.
     */
    public function store(StoreCourtDateRequest $request): RedirectResponse
    {
        // Ensure associates can only schedule dates for their own cases
        $caseIds = $this->assignedCaseIds();
        if ($caseIds !== null) {
            $caseId = $request->validated()['case_id'];
            if (! in_array((int) $caseId, $caseIds)) {
                abort(403, 'You can only schedule dates for cases assigned to you.');
            }
        }

        CourtDate::create($request->validated());

        return redirect()
            ->route('court-dates.index')
            ->with('success', 'Court date scheduled successfully.');
    }

    /**
     * Remove the specified court date.
     * Associates can only delete dates belonging to their own cases.
     */
    public function destroy(CourtDate $courtDate): RedirectResponse
    {
        $this->authorizeCourtDateAccess($courtDate);

        $courtDate->delete();

        return redirect()
            ->route('court-dates.index')
            ->with('success', 'Court date removed successfully.');
    }

    /**
     * Show the form for editing the specified court date.
     * Associates only see their own cases in the dropdown.
     */
    public function edit(CourtDate $courtDate): View
    {
        $this->authorizeCourtDateAccess($courtDate);

        $query = LegalCase::with('client')
            ->whereNotIn('status', ['case_closed'])
            ->orderBy('id', 'desc');

        $caseIds = $this->assignedCaseIds();
        if ($caseIds !== null) {
            $query->whereIn('id', $caseIds);
        }

        $cases = $query->get();

        return view('court-dates.edit', compact('courtDate', 'cases'));
    }

    /**
     * Update the specified court date.
     * Associates can only update dates belonging to their own cases.
     */
    public function update(Request $request, CourtDate $courtDate): RedirectResponse
    {
        $this->authorizeCourtDateAccess($courtDate);

        $validated = $request->validate([
            'case_id'  => ['required', 'exists:legal_cases,id'],
            'date'     => ['required', 'date'],
            'type'     => ['required', 'in:calling_date,trial_date'],
        ]);

        // Ensure associates cannot reassign the court date to someone else's case
        $caseIds = $this->assignedCaseIds();
        if ($caseIds !== null && ! in_array((int) $validated['case_id'], $caseIds)) {
            abort(403, 'You can only assign dates to cases assigned to you.');
        }

        $courtDate->update($validated);

        return redirect()
            ->route('court-dates.index')
            ->with('success', 'Court date updated successfully.');
    }

    /**
     * Abort 403 if the current associate does not own the court date's case.
     */
    private function authorizeCourtDateAccess(CourtDate $courtDate): void
    {
        $caseIds = $this->assignedCaseIds();

        if ($caseIds !== null && ! in_array($courtDate->case_id, $caseIds)) {
            abort(403, 'You do not have access to this court date.');
        }
    }
}
