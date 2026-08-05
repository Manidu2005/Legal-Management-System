<?php

use App\Http\Controllers\DocumentController;
use App\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');
    Route::get('/documents/generate-pdf/{case}/{type}', [DocumentController::class, 'generatePdf'])->name('documents.generate-pdf');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::resource('documents', DocumentController::class)->except(['edit', 'update']);
});
