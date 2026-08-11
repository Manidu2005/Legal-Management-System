<?php

use App\Http\Controllers\LegalCaseController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('cases', LegalCaseController::class);
    Route::get('/cases/{case}/export-brief', [LegalCaseController::class, 'exportBrief'])->name('cases.export-brief');
});
