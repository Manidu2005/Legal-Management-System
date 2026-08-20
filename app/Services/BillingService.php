<?php

namespace App\Services;

use App\Models\LegalCase;
use App\Models\User;
use Illuminate\Support\Collection;

class BillingService
{
    /**
     * Calculate appearance fee: trial_date count × attorney flat rate.
     */
    public function calculateAppearanceFee(LegalCase $case): float
    {
        $trialDates = $case->courtDates()->where('type', 'trial_date')->count();
        $attorneyRate = $case->assignedAttorney->flat_appearance_rate ?? 0;

        return $trialDates * (float) $attorneyRate;
    }

    /**
     * Get trust and operational balances for a case.
     *
     * @return array{trust: float, operational: float}
     */
    public function getCaseBalances(LegalCase $case): array
    {
        return [
            'trust' => (float) $case->ledgerEntries()->where('type', 'trust')->sum('amount'),
            'operational' => (float) $case->ledgerEntries()->where('type', 'operational')->sum('amount'),
        ];
    }

    /**
     * Get a full billing summary for a case.
     *
     * @return array{appearance_fee: float, balances: array, court_dates: \Illuminate\Database\Eloquent\Collection, ledger_entries: \Illuminate\Database\Eloquent\Collection}
     */
    public function getCaseBillingSummary(LegalCase $case): array
    {
        return [
            'appearance_fee' => $this->calculateAppearanceFee($case),
            'balances' => $this->getCaseBalances($case),
            'court_dates' => $case->courtDates()->orderBy('date', 'asc')->get(),
            'ledger_entries' => $case->ledgerEntries()->orderBy('created_at', 'desc')->get(),
        ];
    }

    /**
     * Build an appearance-fee income summary for a single attorney: their
     * flat rate, a per-case breakdown (trial date count + fee), and a
     * running total across all their assigned cases.
     *
     * @return array{rate: float, case_summaries: Collection, total_income: float}
     */
    public function getAttorneyIncomeSummary(User $attorney): array
    {
        $cases = $attorney->assignedCases()->with('client')->get();

        $caseSummaries = $cases->map(function (LegalCase $case) use ($attorney) {
            $case->setRelation('assignedAttorney', $attorney);

            return [
                'case' => $case,
                'trial_date_count' => $case->trialDateCount(),
                'appearance_fee' => $this->calculateAppearanceFee($case),
            ];
        });

        return [
            'rate' => (float) ($attorney->flat_appearance_rate ?? 0),
            'case_summaries' => $caseSummaries,
            'total_income' => $caseSummaries->sum('appearance_fee'),
        ];
    }

    /**
     * Build a firm-wide appearance-fee income summary, broken down per
     * attorney (partner + associates — clerks are never assigned cases).
     *
     * @return array{attorney_summaries: Collection, grand_total: float}
     */
    public function getFirmIncomeSummary(): array
    {
        $attorneys = User::where('role', '!=', 'clerk')
            ->orderBy('name')
            ->get();

        $attorneySummaries = $attorneys
            ->map(function (User $attorney) {
                return array_merge(
                    ['attorney' => $attorney],
                    $this->getAttorneyIncomeSummary($attorney)
                );
            })
            ->filter(fn (array $summary) => $summary['case_summaries']->isNotEmpty())
            ->values();

        return [
            'attorney_summaries' => $attorneySummaries,
            'grand_total' => $attorneySummaries->sum('total_income'),
        ];
    }
}
