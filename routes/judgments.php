<?php

use App\Http\Controllers\CaseJudgmentController;
use App\Http\Controllers\JudgmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/judgments', [JudgmentController::class, 'index'])->name('judgments.index');
    Route::post('/judgments', [JudgmentController::class, 'store'])->name('judgments.store');

    Route::get('/judgments/imports/{batch}/review', [JudgmentController::class, 'reviewImport'])
        ->name('judgments.imports.review');
    Route::post('/judgments/imports/{batch}/confirm', [JudgmentController::class, 'confirmImport'])
        ->name('judgments.imports.confirm');
    Route::post('/judgments/imports/{batch}/resume', [JudgmentController::class, 'resumeImport'])
        ->name('judgments.imports.resume');
    Route::post('/judgments/imports/{batch}/cancel', [JudgmentController::class, 'cancelImport'])
        ->name('judgments.imports.cancel');

    Route::post('/judgments/datasets/import', [JudgmentController::class, 'importDatasets'])
        ->name('judgments.datasets.import');
    Route::post('/judgments/datasets/embed', [JudgmentController::class, 'embedDatasets'])
        ->name('judgments.datasets.embed');

    // Legacy aliases kept so old session bookmarks don't 404 mid-flow
    Route::get('/judgments/split/review', function () {
        return redirect()->route('judgments.index');
    })->name('judgments.split.review');

    Route::get('/judgments/{judgment}/download', [JudgmentController::class, 'download'])->name('judgments.download');
    Route::delete('/judgments/{judgment}', [JudgmentController::class, 'destroy'])->name('judgments.destroy');

    Route::post('/cases/{case}/related-judgments/search', [CaseJudgmentController::class, 'search'])
        ->name('cases.related-judgments.search');
    Route::post('/cases/{case}/related-judgments', [CaseJudgmentController::class, 'store'])
        ->name('cases.related-judgments.store');
    Route::delete('/cases/{case}/related-judgments/{caseJudgment}', [CaseJudgmentController::class, 'destroy'])
        ->name('cases.related-judgments.destroy');
});
