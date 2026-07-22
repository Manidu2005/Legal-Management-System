<?php

namespace App\Services;

use App\Models\LegalCase;

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
}
