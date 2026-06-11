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

// =========================================================================
// 1. GRUP RUTE ADMIN (Wajib Login & Harus Role Admin)
// =========================================================================
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    // Rute Manajemen User
    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::post('/admin/users', [AdminUserController::class, 'store']);
    Route::put('/admin/users/{id}', [AdminUserController::class, 'update']); // <-- INI PINTU UNTUK EDIT
    Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy']); // <-- INI PINTU UNTUK HAPUS
    // Tambahkan baris ini tepat di bawah rute user:
    Route::get('/admin/contracts', [ContractController::class, 'index']);
    Route::post('/admin/contracts/{id}/validate', [ContractController::class, 'validateContract']);
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
    Route::post('/author/contracts/upload', [ContractController::class, 'upload']);
    Route::get('/author/contracts/my-contract', [ContractController::class, 'myContract']);
});

/*
|--------------------------------------------------------------------------
| API Routes — Book Grant API v1 (Modul Kelompok 1)
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