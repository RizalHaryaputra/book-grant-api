<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Module3\AdminManuscriptController;
use App\Http\Controllers\Api\Module3\ReviewerManuscriptController;
use App\Http\Controllers\Api\Module3\ReviewController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// For the mock API, we skip actual middleware authentication for now.

Route::prefix('admin/manuscripts')->group(function () {
    Route::get('/unassigned', [AdminManuscriptController::class, 'getUnassigned']);
    Route::post('/{manuscriptId}/assign-reviewer', [AdminManuscriptController::class, 'assignReviewer']);
});

Route::prefix('reviewer')->group(function () {
    Route::get('/dashboard', [ReviewerManuscriptController::class, 'dashboard']);
    Route::prefix('manuscripts/{manuscriptId}')->group(function () {
        Route::get('/', [ReviewerManuscriptController::class, 'show']);
        Route::get('/rubric', [ReviewerManuscriptController::class, 'getRubric']);
        Route::post('/review', [ReviewController::class, 'submitReview']);
    });
});

Route::get('/manuscripts/{manuscriptId}/compiled-reviews', [ReviewController::class, 'getCompiledReviews']);
