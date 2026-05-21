<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Module3\AdminManuscriptController;
use App\Http\Controllers\Api\Module3\ReviewerManuscriptController;
use App\Http\Controllers\Api\Module3\ReviewController;

/*
|--------------------------------------------------------------------------
| API Routes - Module 3 (Reviewer & Review Process)
|--------------------------------------------------------------------------
*/

// Route yang memerlukan autentikasi
Route::middleware(['auth:sanctum'])->group(function () {

    // ---------- ADMIN ONLY (role_id = 1) ----------
    Route::prefix('admin')->middleware('role:1')->group(function () {
        Route::prefix('manuscripts')->group(function () {
            Route::get('/', [AdminManuscriptController::class, 'index']);
            Route::get('/unassigned', [AdminManuscriptController::class, 'getUnassigned']);
            Route::post('/{manuscriptId}/assign-reviewer', [AdminManuscriptController::class, 'assignReviewer']);
            Route::delete('/{manuscriptId}/remove-reviewer/{reviewerId}', [AdminManuscriptController::class, 'removeReviewer']);
        });
        Route::get('/reviewers', [AdminManuscriptController::class, 'getReviewers']);
    });

    // ---------- REVIEWER ONLY (role_id = 2) ----------
    Route::prefix('reviewer')->middleware('role:2')->group(function () {
        Route::get('/dashboard', [ReviewerManuscriptController::class, 'dashboard']);
        Route::prefix('manuscripts/{manuscriptId}')->group(function () {
            Route::get('/', [ReviewerManuscriptController::class, 'show']);
            Route::get('/rubric', [ReviewerManuscriptController::class, 'getRubric']);
            Route::post('/review', [ReviewController::class, 'submitReview']);
        });
    });

    // ---------- COMPILED REVIEWS (bisa diakses oleh admin, reviewer, dan author) ----------
    // Author perlu melihat hasil review untuk merevisi naskah.
    Route::get('/manuscripts/{manuscriptId}/compiled-reviews', [ReviewController::class, 'getCompiledReviews'])
        ->middleware('role:1,2,3'); // role 1=admin, 2=reviewer, 3=author

});
