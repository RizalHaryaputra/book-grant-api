<?php

use App\Http\Controllers\ManuscriptController;
use Illuminate\Support\Facades\Route;

Route::prefix('author')->group(function () {
    Route::post('/manuscripts/drafts', [ManuscriptController::class, 'uploadDraft']);
});