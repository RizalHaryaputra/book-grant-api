<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorConfirmationController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\ContractController;
use Illuminate\Support\Facades\Mail;

// =========================================================================
// 1. GRUP RUTE ADMIN (Wajib Login & Harus Role Admin)
// =========================================================================
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    
    // Endpoint Admin membuat akun Reviewer / Penerbit
    Route::post('/admin/users', [AdminUserController::class, 'store']);
    
    // Endpoint Admin melihat daftar User/Author
    Route::get('/admin/users', [AdminUserController::class, 'index']);

    // Endpoint Admin memvalidasi kontrak
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
    
    // Endpoint Logout
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Endpoint untuk mengecek profil user yang sedang login
    Route::get('/auth/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('role')
        ], 200);
    });

    // RUTE PENULIS: Mengunggah file kontrak PDF
    Route::post('/contracts/upload', [ContractController::class, 'upload']);
    
    // RUTE PENULIS: Mengecek status dokumen kontrak sendiri
    Route::get('/contracts/my-contract', [ContractController::class, 'myContract']);
    
});

// ==========================================
// DUMMY ENDPOINT KELOMPOK 1 (Untuk Testing Lokal Modul 1)
// ==========================================
Route::post('/notification/send', function (Request $request) {
    if (!$request->to || !$request->subject || !$request->body) {
        return response()->json(['success' => false, 'message' => 'Data tidak lengkap'], 400);
    }

    Mail::raw($request->body, function ($message) use ($request) {
        $message->to($request->to)
                ->subject($request->subject);
    });

    return response()->json([
        'success' => true,
        'message' => 'Email berhasil dikirim (Dummy).'
    ]);
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