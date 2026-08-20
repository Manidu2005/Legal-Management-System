<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use App\Services\BillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(
        protected BillingService $billingService
    ) {}

    /**
     * Financial dashboard.
     *
     * Partners see all cases firm-wide.
     * Associates see only their assigned cases plus an appearance-income
     * summary (merged from the former My Income page).
     */
    public function index(Request $request)
    {
        Gate::authorize('view-financials');

        $user = $request->user();

        $query = LegalCase::with(['client', 'assignedAttorney', 'courtDates', 'ledgerEntries']);

        if ($user->role === 'associate') {
            $query->where('assigned_attorney_id', $user->id);
        }

        $cases = $query->get();

        $caseSummaries = $cases->map(function (LegalCase $case) {
            $trialDateCount = $case->courtDates->where('type', 'trial_date')->count();
            $attorneyRate = $case->assignedAttorney->flat_appearance_rate ?? 0;
            $appearanceFee = $trialDateCount * (float) $attorneyRate;
            $trustBalance = (float) $case->ledgerEntries->where('type', 'trust')->sum('amount');
            $operationalBalance = (float) $case->ledgerEntries->where('type', 'operational')->sum('amount');

            return [
                'case' => $case,
                'trial_date_count' => $trialDateCount,
                'appearance_fee' => $appearanceFee,
                'trust_balance' => $trustBalance,
                'operational_balance' => $operationalBalance,
            ];
        });

        $totalRevenue = $caseSummaries->sum('operational_balance');
        $totalTrust = $caseSummaries->sum('trust_balance');
        $totalCases = $caseSummaries->count();

        // For associates, include the appearance-income summary that was
        // formerly on the separate "My Income" page.
        $incomeSummary = null;
        if ($user->role === 'associate') {
            $incomeSummary = $this->billingService->getAttorneyIncomeSummary($user);
        }

        return view('billing.index', compact(
            'caseSummaries', 'totalRevenue', 'totalTrust', 'totalCases', 'incomeSummary'
        ));
    }

    /**
     * Show form to select a case for invoice generation.
     *
     * Associates only see their own active cases.
     */
    public function createInvoice(Request $request)
    {
        Gate::authorize('view-financials');

        $query = LegalCase::with('client')->where('status', '!=', 'case_closed');

        if ($request->user()->role === 'associate') {
            $query->where('assigned_attorney_id', $request->user()->id);
        }

        $cases = $query->get();

        return view('billing.create-invoice', compact('cases'));
    }

    /**
     * Handle the form submission and generate the invoice report.
     */
    public function generateInvoice(Request $request)
    {
        Gate::authorize('view-financials');

        $request->validate([
            'case_id' => 'required|exists:legal_cases,id',
        ]);

        $case = LegalCase::findOrFail($request->case_id);

        $this->authorize('view', $case);

        return $this->generateReport($case);
    }

    /**
     * Billing details for a specific case.
     */
    public function caseBilling(LegalCase $case)
    {
        $this->authorize('view', $case);

        $case->load(['client', 'assignedAttorney', 'courtDates', 'ledgerEntries.recorder']);

        $summary = $this->billingService->getCaseBillingSummary($case);

        $trialDateCount = $case->courtDates->where('type', 'trial_date')->count();
        $attorneyRate = $case->assignedAttorney->flat_appearance_rate ?? 0;

        return view('billing.case-billing', [
            'case' => $case,
            'summary' => $summary,
            'trialDateCount' => $trialDateCount,
            'attorneyRate' => $attorneyRate,
            'trustEntries' => $case->ledgerEntries->where('type', 'trust')->sortByDesc('created_at'),
            'operationalEntries' => $case->ledgerEntries->where('type', 'operational')->sortByDesc('created_at'),
        ]);
    }

    /**
     * Generate a client-level PDF report for a case (FR-4.2).
     * No firm-wide totals included.
     */
    public function generateReport(LegalCase $case)
    {
        $this->authorize('view', $case);

        $case->load(['client', 'assignedAttorney', 'courtDates', 'ledgerEntries']);

        $summary = $this->billingService->getCaseBillingSummary($case);

        $trialDateCount = $case->courtDates->where('type', 'trial_date')->count();
        $attorneyRate = $case->assignedAttorney->flat_appearance_rate ?? 0;

        $pdf = Pdf::loadView('pdf.client-report', [
            'case' => $case,
            'summary' => $summary,
            'trialDateCount' => $trialDateCount,
            'attorneyRate' => $attorneyRate,
            'generatedAt' => now(),
        ]);

        $filename = 'client-report-case-' . $case->id . '-' . now()->format('Y-m-d') . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Firm-wide appearance-fee income, broken down per attorney.
     * Partner only.
     */
    public function firmIncome()
    {
        $summary = $this->billingService->getFirmIncomeSummary();

        return view('billing.firm-income', [
            'attorneySummaries' => $summary['attorney_summaries'],
            'grandTotal' => $summary['grand_total'],
        ]);
    }

    /**
     * Generate financial summary PDF.
     *
     * Partners get a firm-wide report across all cases.
     * Associates get a report scoped to their own assigned cases.
     */
    public function exportFinancialReport(Request $request)
    {
        Gate::authorize('view-financials');

        $query = LegalCase::with(['client', 'assignedAttorney', 'courtDates', 'ledgerEntries']);

        if ($request->user()->role === 'associate') {
            $query->where('assigned_attorney_id', $request->user()->id);
        }

        $cases = $query->get();

        $caseSummaries = $cases->map(function (LegalCase $case) {
            $trialDateCount    = $case->courtDates->where('type', 'trial_date')->count();
            $attorneyRate      = $case->assignedAttorney->flat_appearance_rate ?? 0;
            $appearanceFee     = $trialDateCount * (float) $attorneyRate;
            $trustBalance      = (float) $case->ledgerEntries->where('type', 'trust')->sum('amount');
            $operationalBalance = (float) $case->ledgerEntries->where('type', 'operational')->sum('amount');

            return [
                'case'                => $case,
                'trial_date_count'    => $trialDateCount,
                'appearance_fee'      => $appearanceFee,
                'trust_balance'       => $trustBalance,
                'operational_balance' => $operationalBalance,
            ];
        });

        $totalRevenue = $caseSummaries->sum('operational_balance');
        $totalTrust   = $caseSummaries->sum('trust_balance');
        $totalCases   = $caseSummaries->count();

        $pdf = Pdf::loadView('documents.templates.financial_report', [
            'caseSummaries' => $caseSummaries,
            'totalRevenue'  => $totalRevenue,
            'totalTrust'    => $totalTrust,
            'totalCases'    => $totalCases,
            'generatedAt'   => now()->format('d F Y, g:i A'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('LexLanka_Financial_Report_' . now()->format('Y-m-d') . '.pdf');
    }
}
