<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;

// Controller Kelompok 4
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorConfirmationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ContractController;

// Controller Kelompok 1
use App\Http\Controllers\Api\V1\AdminDashboardController;
use App\Http\Controllers\Api\V1\MonitoringController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PublisherController;
use App\Http\Controllers\Api\V1\ReminderController;
use App\Http\Controllers\Api\V1\UserDashboardController;

// Controller Kelompok 3 (Modul Penulis dan Manajemen Naskah)
use App\Http\Controllers\ManuscriptController;

// Controller Kelompok 2 (Modul Reviewer dan Proses Review)
use App\Http\Controllers\Api\Module3\AdminManuscriptController;
use App\Http\Controllers\Api\Module3\AdminRubricController;
use App\Http\Controllers\Api\Module3\ReviewerManuscriptController;
use App\Http\Controllers\Api\Module3\ReviewController;

// =========================================================================
// 1. GRUP RUTE ADMIN (Wajib Login & Harus Role Admin)
// =========================================================================
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Rute Manajemen User
    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::post('/admin/users', [AdminUserController::class, 'store']);
    Route::put('/admin/users/{id}', [AdminUserController::class, 'update']);
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy']);
    
    // Rute Manajemen Kontrak
    Route::get('/admin/contracts', [ContractController::class, 'index']);
    Route::post('/admin/contracts/{id}/validate', [ContractController::class, 'validateContract']);

    // Rute Plotting & Review (Kelompok 2 / Module 3)
    Route::prefix('admin/manuscripts')->group(function () {
        Route::get('/', [AdminManuscriptController::class, 'index']);
        Route::get('/unassigned', [AdminManuscriptController::class, 'getUnassigned']);
        Route::post('/{manuscriptId}/assign-reviewer', [AdminManuscriptController::class, 'assignReviewer']);
        Route::delete('/{manuscriptId}/remove-reviewer/{reviewerId}', [AdminManuscriptController::class, 'removeReviewer']);
    });
    Route::get('/admin/reviewers', [AdminManuscriptController::class, 'getReviewers']);

    // Rubric Management
    Route::prefix('admin/rubrics')->group(function () {
        Route::get('/', [AdminRubricController::class, 'index']);
        Route::get('/{id}', [AdminRubricController::class, 'show']);
        Route::post('/', [AdminRubricController::class, 'store']);
        Route::put('/{id}', [AdminRubricController::class, 'update']);
        Route::delete('/{id}', [AdminRubricController::class, 'destroy']);
    });
});

// =========================================================================
// 2. ENDPOINT PUBLIK (Bisa Diakses Siapa Saja Tanpa Token)
// =========================================================================
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/author-confirmations', [AuthorConfirmationController::class, 'store']);

// =========================================================================
// 3. GRUP RUTE TERPROTEKSI (Wajib Login / Pakai Token Bearer)
// =========================================================================
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('role')
        ], 200);
    });

    // Kontrak Penulis
    Route::post('/author/contracts/upload', [ContractController::class, 'upload']);
    Route::get('/author/contracts/my-contract', [ContractController::class, 'myContract']);

    // Rute Penulis dan Manajemen Naskah (Kelompok 3 / Module 2)
    Route::prefix('author')->group(function () {
        Route::get('/dashboard', [ManuscriptController::class, 'dashboard']);
        Route::post('/manuscripts/drafts', [ManuscriptController::class, 'uploadDraft']);
        Route::post('/manuscripts/{manuscriptId}/documents', [ManuscriptController::class, 'uploadDocument']);
        Route::get('/manuscripts/{manuscriptId}/reviews', [ManuscriptController::class, 'reviews']);
        Route::post('/manuscripts/{manuscriptId}/revisions', [ManuscriptController::class, 'uploadRevision']);
        Route::get('/manuscripts/{manuscriptId}', [ManuscriptController::class, 'show']);
        Route::put('/manuscripts/{manuscriptId}', [ManuscriptController::class, 'updateMetadata']);
        Route::get('/manuscripts/{manuscriptId}/files', [ManuscriptController::class, 'files']);
        Route::get('/manuscripts/{manuscriptId}/documents', [ManuscriptController::class, 'documents']);
        Route::get('/manuscripts/{manuscriptId}/publisher-check', [ManuscriptController::class, 'publisherCheck']);
        Route::post('/manuscripts/{manuscriptId}/preprint-revision', [ManuscriptController::class, 'uploadPreprintRevision']);
    });

    // Rute Reviewer (Kelompok 2 / Module 3)
    Route::prefix('reviewer')->middleware('role:reviewer')->group(function () {
        Route::get('/dashboard', [ReviewerManuscriptController::class, 'dashboard']);
        Route::prefix('manuscripts/{manuscriptId}')->group(function () {
            Route::get('/', [ReviewerManuscriptController::class, 'show']);
            Route::get('/rubric', [ReviewerManuscriptController::class, 'getRubric']);
            Route::post('/review', [ReviewController::class, 'submitReview']);
            Route::get('/download', [ReviewerManuscriptController::class, 'downloadDraft']);
        });
    });

    // Rute Kompilasi Review (bisa diakses admin, reviewer, author)
    Route::get('/manuscripts/{manuscriptId}/compiled-reviews', [ReviewController::class, 'getCompiledReviews'])
        ->middleware('role:admin,reviewer,author');

});

/*
|--------------------------------------------------------------------------
| API Routes — Book Grant API v1 (Modul Kelompok 1 / Module 4)
|--------------------------------------------------------------------------
*/
Route::prefix('v1')->group(function () {

    // 1. Admin Dashboard
    Route::get('/admin/dashboard/summary', [AdminDashboardController::class, 'summary']);

    // 2. Monitoring
    Route::get('/monitoring/deadlines', [MonitoringController::class, 'deadlines']);
    Route::get('/monitoring/logs',      [MonitoringController::class, 'logs']);

    // 3. User Dashboard
    Route::get('/user/deadline-widget', [UserDashboardController::class, 'deadlineWidget']);

    // 4. Publisher Workflow
    Route::prefix('publisher')->group(function () {
        Route::get('/dashboard',               [PublisherController::class, 'dashboard']);
        Route::get('/manuscripts/pre-print',   [PublisherController::class, 'prePrintManuscripts']);
        Route::get('/manuscripts/{id}',        [PublisherController::class, 'showManuscript']);
        Route::post('/check/{manuscriptId}',   [PublisherController::class, 'check']);
        Route::post('/decision',               [PublisherController::class, 'decision']);
    });

    // 5. Notifications
    Route::post('/notification/send', [NotificationController::class, 'send']);

    // 6. Reminders
    Route::post('/reminder/trigger', [ReminderController::class, 'trigger']);
});
