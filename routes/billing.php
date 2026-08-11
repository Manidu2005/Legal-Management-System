<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\LedgerEntryController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::middleware('role:partner,associate')->group(function () {
        Route::get('/billing', [BillingController::class, 'index'])->name('billing.index')->middleware('role:partner');
        Route::get('/billing/export-report', [BillingController::class, 'exportFinancialReport'])->name('billing.export-report')->middleware('can:view-financials');
        Route::get('/billing/case/{case}', [BillingController::class, 'caseBilling'])->name('billing.case');
        Route::get('/billing/case/{case}/report', [BillingController::class, 'generateReport'])->name('billing.report');
        Route::post('/ledger-entries', [LedgerEntryController::class, 'store'])->name('ledger-entries.store');
        Route::delete('/ledger-entries/{ledgerEntry}', [LedgerEntryController::class, 'destroy'])->name('ledger-entries.destroy');
    });
});
