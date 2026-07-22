<?php

namespace App\Http\Controllers;

use App\Models\LegalCase;
use App\Services\BillingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Gate;

class BillingController extends Controller
{
    public function __construct(
        protected BillingService $billingService
    ) {}

    /**
     * Financial dashboard — partner only.
     */
    public function index()
    {
        Gate::authorize('view-financials');

        $cases = LegalCase::with(['client', 'assignedAttorney', 'courtDates', 'ledgerEntries'])->get();

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

        return view('billing.index', compact('caseSummaries', 'totalRevenue', 'totalTrust', 'totalCases'));
    }

    /**
     * Billing details for a specific case.
     */
    public function caseBilling(LegalCase $case)
    {
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
}
