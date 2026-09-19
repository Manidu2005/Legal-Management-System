<?php

use App\Http\Controllers\CaseCategoryController;
use App\Http\Controllers\CourtController;
use Illuminate\Support\Facades\Route;

// Case-category taxonomy & courts/forum list — partner-only admin screens.
Route::middleware(['auth', 'role:partner'])->group(function () {
    Route::resource('case-categories', CaseCategoryController::class)->except(['show']);
    Route::patch('/case-categories/{caseCategory}/toggle-active', [CaseCategoryController::class, 'toggleActive'])
        ->name('case-categories.toggle-active');

    Route::resource('courts', CourtController::class)->except(['show']);
    Route::patch('/courts/{court}/toggle-active', [CourtController::class, 'toggleActive'])
        ->name('courts.toggle-active');
});
