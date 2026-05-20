<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ManuscriptController;

Route::prefix('author')->group(function () {
    // [Fitur 1] Upload Draft Awal 
    Route::post('/manuscripts/drafts', [ManuscriptController::class, 'uploadDraft']);
    
    // [Fitur 2] Upload Dokumen Administrasi 
    Route::post('/manuscripts/{manuscriptId}/documents', [ManuscriptController::class, 'uploadDocument']);
});