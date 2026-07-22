<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Module routes
require __DIR__.'/cases.php';        // Module 5: Cases CRUD + Dashboard
require __DIR__.'/documents.php';    // Module 1: Documents & Search
require __DIR__.'/scheduling.php';   // Module 2: Court Dates
require __DIR__.'/billing.php';      // Module 3: Billing & Financial
require __DIR__.'/users.php';        // Module 4: Users & Client Intake
require __DIR__.'/auth.php';         // Auth (Breeze)
