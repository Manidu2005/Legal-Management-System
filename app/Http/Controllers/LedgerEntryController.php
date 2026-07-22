<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLedgerEntryRequest;
use App\Models\LedgerEntry;

class LedgerEntryController extends Controller
{
    /**
     * Store a new ledger entry.
     */
    public function store(StoreLedgerEntryRequest $request)
    {
        LedgerEntry::create([
            'case_id' => $request->validated('case_id'),
            'type' => $request->validated('type'),
            'amount' => $request->validated('amount'),
            'description' => $request->validated('description'),
            'recorded_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('billing.case', $request->validated('case_id'))
            ->with('success', 'Ledger entry recorded successfully.');
    }

    /**
     * Delete a ledger entry.
     */
    public function destroy(LedgerEntry $ledgerEntry)
    {
        $caseId = $ledgerEntry->case_id;

        $ledgerEntry->delete();

        return redirect()
            ->route('billing.case', $caseId)
            ->with('success', 'Ledger entry deleted successfully.');
    }
}
