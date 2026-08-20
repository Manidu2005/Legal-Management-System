<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourtDateRequest;
use App\Models\CourtDate;
use App\Models\LegalCase;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourtDateController extends Controller
{
    /**
     * Display a listing of court dates grouped by month.
     */
    public function index(): View
    {
        $courtDates = CourtDate::with(['legalCase.client', 'legalCase.assignedAttorney'])
            ->orderBy('date', 'asc')
            ->get();

        // Separate into upcoming and past
        $now = now();
        $upcoming = $courtDates->filter(fn ($cd) => $cd->date->gte($now));
        $past = $courtDates->filter(fn ($cd) => $cd->date->lt($now));

        // Group upcoming by month
        $upcomingByMonth = $upcoming->groupBy(fn ($cd) => $cd->date->format('F Y'));

        // Group past by month (most recent first)
        $pastByMonth = $past->sortByDesc('date')->groupBy(fn ($cd) => $cd->date->format('F Y'));

        // Build a simple calendar grid for the current month (Mon–Sun, including pad days)
        $currentMonth = now();
        $daysInMonth = $currentMonth->daysInMonth;
        $firstDayOfWeek = $currentMonth->copy()->startOfMonth()->dayOfWeek; // 0=Sunday

        $gridStart = $currentMonth->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $currentMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $calendarDates = $courtDates
            ->filter(fn ($cd) => $cd->date->between($gridStart, $gridEnd))
            ->groupBy(fn ($cd) => $cd->date->toDateString());

        return view('court-dates.index', compact(
            'upcomingByMonth',
            'pastByMonth',
            'currentMonth',
            'daysInMonth',
            'firstDayOfWeek',
            'calendarDates'
        ));
    }

    /**
     * Show the form for creating a new court date.
     */
    public function create(): View
    {
        $cases = LegalCase::with('client')
            ->whereNotIn('status', ['case_closed'])
            ->orderBy('id', 'desc')
            ->get();

        return view('court-dates.create', compact('cases'));
    }

    /**
     * Store a newly created court date.
     */
    public function store(StoreCourtDateRequest $request): RedirectResponse
    {
        CourtDate::create($request->validated());

        return redirect()
            ->route('court-dates.index')
            ->with('success', 'Court date scheduled successfully.');
    }

    /**
     * Remove the specified court date.
     */
    public function destroy(CourtDate $courtDate): RedirectResponse
    {
        $courtDate->delete();

        return redirect()
            ->route('court-dates.index')
            ->with('success', 'Court date removed successfully.');
    }

    /**
     * Show the form for editing the specified court date.
     */
    public function edit(CourtDate $courtDate): View
    {
        $cases = LegalCase::with('client')
            ->whereNotIn('status', ['case_closed'])
            ->orderBy('id', 'desc')
            ->get();

        return view('court-dates.edit', compact('courtDate', 'cases'));
    }

    /**
     * Update the specified court date.
     */
    public function update(Request $request, CourtDate $courtDate): RedirectResponse
    {
        $validated = $request->validate([
            'case_id'  => ['required', 'exists:legal_cases,id'],
            'date'     => ['required', 'date'],
            'type'     => ['required', 'in:calling_date,trial_date'],
        ]);

        $courtDate->update($validated);

        return redirect()
            ->route('court-dates.index')
            ->with('success', 'Court date updated successfully.');
    }
}
