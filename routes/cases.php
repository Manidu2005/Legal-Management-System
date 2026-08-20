<?php

use App\Http\Controllers\CaseAccessCodeController;
use App\Http\Controllers\LegalCaseController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('cases', LegalCaseController::class);
    Route::get('/cases/{case}/export-brief', [LegalCaseController::class, 'exportBrief'])->name('cases.export-brief');

    Route::get('/cases/{case}/access-code', [CaseAccessCodeController::class, 'show'])->name('cases.access-code.show');
    Route::post('/cases/{case}/access-code', [CaseAccessCodeController::class, 'store'])
        ->name('cases.access-code.store')
        ->middleware('throttle:5,1');
    Route::patch('/cases/{case}/access-code', [CaseAccessCodeController::class, 'update'])->name('cases.access-code.update');
});
