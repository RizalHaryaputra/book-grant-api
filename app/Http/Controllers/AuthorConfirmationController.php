<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuthorProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

class AuthorConfirmationController extends Controller
{
    public function store(Request $request)
    {
        // 1. Validasi Input (Disesuaikan dengan ERD baru)
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'institution' => 'required|string',
            'book_title' => 'required|string',
            'book_type' => 'required|in:buku ajar,buku referensi', 
            'ai_ethics_agreed' => 'boolean',
            'willingness_statement' => 'boolean' 
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'data' => ['errors' => $validator->errors()]
            ], 422);
        }

        try {
            $result = DB::transaction(function () use ($request) {
                // Generate password acak 8 karakter
                $rawPassword = Str::password(8, true, true, true, false);

                // a. Buat akun User
                $user = User::create([
                    'role_id' => 2, // 2 = Penulis
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($rawPassword),
                    'is_active' => true,
                ]);

                // b. Buat Profil Penulis (Mapping dari request ke kolom DB terbaru)
                $author = AuthorProfile::create([
                    'user_id' => $user->id,
                    'institution' => $request->institution,      
                    'book_title' => $request->book_title,
                    'book_type' => $request->book_type,
                    'ai_ethics_agreed' => $request->ai_ethics_agreed ?? true, // Sudah diperbaiki jadi 'ai'
                    'willingness_statement' => $request->willingness_statement ?? true, // Sesuaikan ERD
                    'status' => 'account_created', // Status awal sesuai alur ERD baru
                ]);

                return [
                    'user' => $user,
                    'author' => $author,
                    'rawPassword' => $rawPassword
                ];
            });

            // 3. Kirim Email Notifikasi via Server Modul 4 (Port 8001)
            try {
                $response = Http::post('http://127.0.0.1:8001/api/notification/send', [
                    'to' => $result['user']->email,
                    'subject' => 'Akun Sistem Hibah Buku Anda',
                    'body' => "Halo {$result['user']->name}, ini kredensial Anda. Email: {$result['user']->email}, Password: {$result['rawPassword']}",
                    'type' => 'akun'
                ]);

                if ($response->successful()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Akun penulis berhasil dibuat. Email kredensial telah dikirim.',
                        'data' => [
                            'author_id' => $result['author']->id,
                            'user_id' => $result['user']->id,
                            'status' => $result['author']->status
                        ]
                    ], 201);
                }
            } catch (\Exception $e) {
                // Biarkan kosong agar tetap masuk ke response 202 jika email gagal
            }

            return response()->json([
                'success' => false, 
                'message' => 'Akun dibuat, namun email gagal terkirim.',
                'data' => [
                    'author_id' => $result['author']->id,
                    'user_id' => $result['user']->id,
                    'status' => $result['author']->status
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