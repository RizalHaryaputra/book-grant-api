<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ManuscriptController;

Route::prefix('author')->group(function () {
    // [Fitur 3] Dasbor Penulis
    Route::get('/dashboard', [ManuscriptController::class, 'dashboard']);

    // [Fitur 1] Upload Draft Awal 
    Route::post('/manuscripts/drafts', [ManuscriptController::class, 'uploadDraft']);
    
    // [Fitur 2] Upload Dokumen Administrasi 
    Route::post('/manuscripts/{manuscriptId}/documents', [ManuscriptController::class, 'uploadDocument']);

    // [Fitur 4] Melihat Hasil Review
    Route::get('/manuscripts/{manuscriptId}/reviews', [ManuscriptController::class, 'reviews']);

    // [Fitur 5] Upload Revisi Naskah
    Route::post('/manuscripts/{manuscriptId}/revisions', [ManuscriptController::class, 'uploadRevision']);
});
