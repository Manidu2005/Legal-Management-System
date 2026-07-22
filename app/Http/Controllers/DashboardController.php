<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\CourtDate;
use App\Models\Document;
use App\Models\LegalCase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with summary counts.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'associate') {
            $totalCases = LegalCase::where('assigned_attorney_id', $user->id)->count();
            $caseIds = LegalCase::where('assigned_attorney_id', $user->id)->pluck('id');
            $upcomingCourtDates = CourtDate::whereIn('case_id', $caseIds)
                ->whereBetween('date', [Carbon::now(), Carbon::now()->addDays(30)])
                ->count();
            $totalDocuments = Document::whereIn('case_id', $caseIds)->count();
        } else {
            $totalCases = LegalCase::count();
            $upcomingCourtDates = CourtDate::whereBetween('date', [Carbon::now(), Carbon::now()->addDays(30)])
                ->count();
            $totalDocuments = Document::count();
        }

        $activeClients = Client::count();

        return view('dashboard', compact(
            'totalCases',
            'upcomingCourtDates',
            'activeClients',
            'totalDocuments'
        ));
    }
}
