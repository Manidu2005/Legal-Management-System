<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourtDateRequest;
use App\Models\CourtDate;
use App\Models\LegalCase;
use Illuminate\Http\RedirectResponse;
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

        // Build a simple calendar grid for the current month
        $currentMonth = now();
        $daysInMonth = $currentMonth->daysInMonth;
        $firstDayOfWeek = $currentMonth->copy()->startOfMonth()->dayOfWeek; // 0=Sunday

        // Get court dates for this month for calendar highlighting
        $monthStart = $currentMonth->copy()->startOfMonth();
        $monthEnd = $currentMonth->copy()->endOfMonth();
        $calendarDates = $courtDates->filter(function ($cd) use ($monthStart, $monthEnd) {
            return $cd->date->between($monthStart, $monthEnd);
        })->groupBy(fn ($cd) => $cd->date->format('j'));

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
}
