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
});