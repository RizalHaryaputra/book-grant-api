<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
// use Illuminate\Support\Facades\Http; // Sudah dihapus karena tidak pakai API eksternal lagi

class AdminUserController extends Controller
{
    // ==========================================
    // Fungsi untuk mengambil data (GET)
    // ==========================================
    public function index()
    {
        $users = User::with(['role', 'authorProfile'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil data seluruh pengguna beserta profil penulis.',
            'data' => $users
        ], 200);
    }

    // ==========================================
    // Membuat akun baru (POST)
    // ==========================================
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:reviewer,penerbit', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'data' => ['errors' => $validator->errors()]
            ], 422);
        }

        $roleId = $request->role === 'reviewer' ? 3 : 4;

        try {
            // 2. Transaksi Database
            $result = DB::transaction(function () use ($request, $roleId) {
                $rawPassword = Str::password(8, true, true, true, false);

                $user = User::create([
                    'role_id' => $roleId,
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($rawPassword),
                    'is_active' => true,
                ]);

                return [
                    'user' => $user,
                    'rawPassword' => $rawPassword
                ];
            });

            // 3. Trigger Event untuk Kelompok 1 (Background Process)
            \App\Events\AccountCreated::dispatch([
                'user_id' => $result['user']->id, 
                'email' => $result['user']->email, 
                'name' => $result['user']->name,
                'raw_password' => $result['rawPassword'] // Disertakan agar email bisa menampilkan password
            ]);

            // 4. Response Langsung Sukses (Tidak perlu nunggu email terkirim)
            return response()->json([
                'success' => true,
                'message' => "Akun " . $request->role . " berhasil dibuat. Email kredensial sedang dikirim di latar belakang.",
                'data' => [
                    'user_id' => $result['user']->id,
                    'role' => $request->role,
                    'is_active' => $result['user']->is_active
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat membuat akun.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}