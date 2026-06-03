<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuthorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
// use Illuminate\Support\Facades\Http; // Sudah dihapus

class AuthorConfirmationController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'institution' => 'required|string',
            'book_title' => 'required|string',
            'book_type' => 'required|in:buku ajar,buku referensi',
            'ai_ethics_agreed' => 'required|accepted',
            'willingness_statement' => 'required|accepted'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'data' => [
                    'errors' => $validator->errors()
                ]
            ], 422);
        }

        try {
            // 2. Transaksi Database
            $result = DB::transaction(function () use ($request) {
                $rawPassword = Str::password(8, true, true, true, false);

                // Buat akun user
                $user = User::create([
                    'role_id' => 2,
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($rawPassword),
                    'is_active' => true,
                ]);

                // Simpan profil penulis
                $author = AuthorProfile::create([
                    'user_id' => $user->id,
                    'institution' => $request->institution,
                    'book_title' => $request->book_title,
                    'book_type' => $request->book_type,
                    'ai_ethics_agreed' => $request->boolean('ai_ethics_agreed'),
                    'willingness_statement' => $request->boolean('willingness_statement'),
                    'status' => 'account_created',
                ]);

                return [
                    'user' => $user,
                    'author' => $author,
                    'rawPassword' => $rawPassword
                ];
            });

            // 3. Trigger Event untuk Kelompok 1
            \App\Events\AccountCreated::dispatch([
                'user_id' => $result['user']->id, 
                'email' => $result['user']->email, 
                'name' => $result['user']->name,
                'raw_password' => $result['rawPassword'] // Disertakan untuk isi email
            ]);

            // 4. Response Langsung Sukses
            return response()->json([
                'success' => true,
                'message' => 'Akun penulis berhasil dibuat. Email kredensial diproses di latar belakang.',
                'data' => [
                    'author_id' => $result['author']->id,
                    'user_id' => $result['user']->id,
                    'status' => $result['author']->status
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