<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AdminDashboardController;
use App\Http\Controllers\Api\V1\MonitoringController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\PublisherController;
use App\Http\Controllers\Api\V1\ReminderController;
use App\Http\Controllers\Api\V1\UserDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes — Book Grant API v1
|--------------------------------------------------------------------------
|
| All routes are prefixed with /api/v1 (the /api prefix is applied
| automatically by RouteServiceProvider or bootstrap/app.php).
|
| Single canonical controller namespace: App\Http\Controllers\Api\V1
|
*/

Route::prefix('v1')->group(function () {

    // ------------------------------------------------------------------
    // 1. Admin Dashboard
    // ------------------------------------------------------------------
    Route::get('/admin/dashboard/summary', [AdminDashboardController::class, 'summary']);

    // ------------------------------------------------------------------
    // 2. Monitoring (read-only — deadlines & notification log)
    // ------------------------------------------------------------------
    Route::get('/monitoring/deadlines', [MonitoringController::class, 'deadlines']);
    Route::get('/monitoring/logs',      [MonitoringController::class, 'logs']);

    // ------------------------------------------------------------------
    // 3. User Dashboard
    // ------------------------------------------------------------------
    Route::get('/user/deadline-widget', [UserDashboardController::class, 'deadlineWidget']);

    // ------------------------------------------------------------------
    // 4. Publisher Workflow
    //    Middleware temporarily disabled for testing — re-enable with:
    //    ->middleware(['auth:sanctum', 'role:publisher'])
    // ------------------------------------------------------------------
    Route::prefix('publisher')->group(function () {
        Route::get('/dashboard',               [PublisherController::class, 'dashboard']);
        Route::get('/manuscripts/pre-print',   [PublisherController::class, 'prePrintManuscripts']);
        Route::get('/manuscripts/{id}',        [PublisherController::class, 'showManuscript']);
        Route::post('/check/{manuscriptId}',   [PublisherController::class, 'check']);
        Route::post('/decision',               [PublisherController::class, 'decision']);
    });

    // ------------------------------------------------------------------
    // 5. Notifications (manual send — event-driven sends use Listeners)
    // ------------------------------------------------------------------
    Route::post('/notification/send', [NotificationController::class, 'send']);

    // ------------------------------------------------------------------
    // 6. Reminders (trigger deadline email sweep)
    //    Intended to be called by a scheduled command:
    //    $schedule->call(fn() => app(ReminderController::class)->trigger())->dailyAt('08:00');
    // ------------------------------------------------------------------
    Route::post('/reminder/trigger', [ReminderController::class, 'trigger']);
});