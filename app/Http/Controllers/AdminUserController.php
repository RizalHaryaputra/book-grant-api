<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    // ==========================================
    // TAMBAHAN BARU: Fungsi untuk mengambil data (GET)
    // ==========================================
    public function index()
    {
        // Menambahkan 'authorProfile' ke dalam array with()
        $users = User::with(['role', 'authorProfile'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil data seluruh pengguna beserta profil penulis.',
            'data' => $users
        ], 200);
    }

    // ==========================================
    // FUNGSI LAMA: Membuat akun baru (POST)
    // ==========================================
    public function store(Request $request)
    {
        // 1. Validasi Input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|in:reviewer,penerbit', // Hanya boleh reviewer atau penerbit
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'data' => ['errors' => $validator->errors()]
            ], 422);
        }

        // Mapping string role ke role_id sesuai database (Asumsi: 3 = Reviewer, 4 = Penerbit)
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

            // 3. Kirim Email Kredensial lewat Server Dummy Notifikasi (Port 8001)
            try {
                $response = Http::post('http://127.0.0.1:8001/api/notification/send', [
                    'to' => $result['user']->email,
                    'subject' => "Akun Sistem Hibah Buku - Akses " . ucfirst($request->role),
                    'body' => "Halo {$result['user']->name}, Admin telah membuatkan akun " . $request->role . " Anda. Email: {$result['user']->email}, Password: {$result['rawPassword']}",
                    'type' => 'akun'
                ]);

                if ($response->successful()) {
                    return response()->json([
                        'success' => true,
                        'message' => "Akun " . $request->role . " berhasil dibuat dan email kredensial telah dikirim.",
                        'data' => [
                            'user_id' => $result['user']->id,
                            'role' => $request->role,
                            'is_active' => $result['user']->is_active
                        ]
                    ], 201);
                }
            } catch (\Exception $e) {
                // Log error jika email gagal terkirim, tapi user tetap aman di DB
            }

            return response()->json([
                'success' => false,
                'message' => 'Akun berhasil dibuat, namun email kredensial gagal terkirim.',
                'data' => [
                    'user_id' => $result['user']->id,
                    'role' => $request->role
                ]
            ], 202);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat membuat akun.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}