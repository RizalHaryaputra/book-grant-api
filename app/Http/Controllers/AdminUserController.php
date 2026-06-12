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
            'role' => 'required|in:reviewer,editor,author,admin', 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'data' => ['errors' => $validator->errors()]
            ], 422);
        }

        $roleObj = \App\Models\Role::where('name', $request->role)->first();
        $roleId = $roleObj ? $roleObj->id : 2;

        try {
            // 2. Transaksi Database
            $result = DB::transaction(function () use ($request, $roleId) {
                $rawPassword = 'password123'; // Str::password(8, true, true, true, false);

                $user = User::create([
                    'role_id' => $roleId,
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($rawPassword),
                    'is_active' => true,
                ]);

                if ($request->role === 'author') {
                    \App\Models\AuthorProfile::create([
                        'user_id' => $user->id,
                        'institutions' => $request->institution ?? 'Belum Diatur',
                        'book_title' => 'Belum Diatur',
                        'book_type' => 'Buku Ajar',
                        'at_ethics_agreed' => true,
                        'willingness_status' => true,
                        'status' => 'active'
                    ]);
                }

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

    // ==========================================
    // Mengubah data akun (PUT)
    // ==========================================
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.'
            ], 404);
        }

        // Validasi input edit
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'is_active' => 'sometimes|required|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'data' => ['errors' => $validator->errors()]
            ], 422);
        }

        try {
            // Update data
            if ($request->has('name')) $user->name = $request->name;
            if ($request->has('is_active')) $user->is_active = $request->is_active;
            
            $user->save();

            if ($request->has('institution') && $user->authorProfile) {
                $user->authorProfile->institutions = $request->institution;
                $user->authorProfile->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil diperbarui.',
                'data' => $user
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat update akun.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // Menghapus akun (DELETE)
    // ==========================================
    public function destroy($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan.'
            ], 404);
        }

        try {
            $user->delete();

            return response()->json([
                'success' => true,
                'message' => 'Data user berhasil dihapus.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat menghapus akun.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
