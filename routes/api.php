<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PublisherController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReminderController;
use App\Http\Controllers\Api\AdminDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes for Module 4 (Kelompok 1)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    // ==========================================
    // 1. Publisher Endpoints (Pra-cetak & Keputusan)
    // ==========================================
    Route::prefix('publisher')->group(function () {
        Route::get('/dashboard', [PublisherController::class, 'dashboard']);
        Route::get('/preprint-list', [PublisherController::class, 'preprintList']);
        Route::get('/check/{manuscriptId}', [PublisherController::class, 'getCheck']);
        Route::post('/check/{manuscriptId}', [PublisherController::class, 'storeCheck']);
        Route::post('/decision', [PublisherController::class, 'decision']);
    });

    // ==========================================
    // 2. Admin Endpoints (Dashboard & Logs)
    // ==========================================
    Route::prefix('admin')->group(function () {
        Route::get('/dashboard-stats', [AdminDashboardController::class, 'stats']);
        Route::get('/notification-logs', [NotificationController::class, 'logs']);
    });

    // ==========================================
    // 3. Notification Endpoint (internal)
    // ==========================================
    Route::post('/notification/send', [NotificationController::class, 'send']);

    // ==========================================
    // 4. Reminder Endpoint (manual trigger)
    // ==========================================
    Route::post('/reminder/trigger', [ReminderController::class, 'trigger']);
});