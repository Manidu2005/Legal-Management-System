<?php

use App\Http\Controllers\CourtDateController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::resource('court-dates', CourtDateController::class)->except(['show']);
});
