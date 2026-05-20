<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AuthorConfirmationController;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\AdminUserController;


// Grup Rute Khusus yang Hanya Bisa Diakses oleh Admin setelah Login
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    
    // Endpoint Admin membuat akun Reviewer / Penerbit
    Route::post('/admin/users', [AdminUserController::class, 'store']);
    
});

// ==========================================
// DUMMY ENDPOINT KELOMPOK 1 (Untuk Testing Lokal Modul 1)
// ==========================================
Route::post('/notification/send', function (Request $request) {
    // Validasi sederhana
    if (!$request->to || !$request->subject || !$request->body) {
        return response()->json(['success' => false, 'message' => 'Data tidak lengkap'], 400);
    }

    // Kirim email berupa teks mentah ke Mailtrap
    Mail::raw($request->body, function ($message) use ($request) {
        $message->to($request->to)
                ->subject($request->subject);
    });

    return response()->json([
        'success' => true,
        'message' => 'Email berhasil dikirim (Dummy).'
    ]);
});

// Endpoint publik (tidak perlu token)
Route::post('/auth/login', [AuthController::class, 'login']);
// Rute pendaftaran penulis (Publik)
Route::post('/author-confirmations', [AuthorConfirmationController::class, 'store']);
// Endpoint terproteksi (wajib pakai token Bearer)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    
    // Endpoint me untuk mengecek user yang sedang login
    Route::get('/auth/me', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()->load('role')
        ], 200);
    });
});