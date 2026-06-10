<?php


use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ManuscriptController;

Route::prefix('author')->group(function () {

    Route::get('/dashboard',
        [ManuscriptController::class, 'dashboard']);

    Route::post('/manuscripts/drafts',
        [ManuscriptController::class, 'uploadDraft']);

    Route::post('/manuscripts/{manuscriptId}/documents',
        [ManuscriptController::class, 'uploadDocument']);

    Route::get('/manuscripts/{manuscriptId}/reviews',
        [ManuscriptController::class, 'reviews']);

    Route::post('/manuscripts/{manuscriptId}/revisions',
        [ManuscriptController::class, 'uploadRevision']);

    Route::get('/manuscripts/{manuscriptId}',
        [ManuscriptController::class, 'show']);

    Route::put('/manuscripts/{manuscriptId}',
        [ManuscriptController::class, 'updateMetadata']);

    Route::get('/manuscripts/{manuscriptId}/files',
        [ManuscriptController::class, 'files']);

    Route::get('/manuscripts/{manuscriptId}/documents',
        [ManuscriptController::class, 'documents']);

    Route::get('/manuscripts/{manuscriptId}/publisher-check',
        [ManuscriptController::class, 'publisherCheck']);

    Route::post('/manuscripts/{manuscriptId}/preprint-revision',
        [ManuscriptController::class, 'uploadPreprintRevision']);
});
