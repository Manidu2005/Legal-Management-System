<?php

use App\Http\Controllers\ResearchNoteController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('/research-notes', [ResearchNoteController::class, 'store'])->name('research-notes.store');
    Route::delete('/research-notes/{researchNote}', [ResearchNoteController::class, 'destroy'])->name('research-notes.destroy');
});
