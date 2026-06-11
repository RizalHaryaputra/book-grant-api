<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{

    // Tambahkan fungsi ini di dalam ContractController.php
    public function index()
    {
        // Mengambil semua kontrak, diurutkan dari yang terbaru
        $contracts = Contract::with('authorProfile.user')->orderBy('created_at', 'desc')->get();
        
        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil daftar kontrak',
            'data' => $contracts
        ]);
    }
   // ==========================================
    // FUNGSI PENULIS: Upload Kontrak
    // ==========================================
    public function upload(Request $request)
    {
        // 1. Pastikan yang login punya profil penulis
        $authorProfile = $request->user()->authorProfile;
        
        if (!$authorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil penulis tidak ditemukan.'
            ], 404);
        }

        // 2. Validasi input (Wajib berupa file PDF, maksimal 10MB)
        $validator = Validator::make($request->all(), [
            'contract_file' => 'required|file|mimes:pdf|max:10240', // <-- SUDAH JADI 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // 3. Simpan file PDF ke folder storage Laravel
            $file = $request->file('contract_file'); // <-- Nama amplop harus sesuai
            $filename = time() . '_kontrak_' . $authorProfile->id . '.' . $file->getClientOriginalExtension();
            
            // File akan tersimpan di folder: storage/app/public/contracts
            $path = $file->storeAs('contracts', $filename, 'public');

            // 4. Simpan sebagai baris data BARU di database
                    $contract = Contract::create([
                        'author_profile_id' => $authorProfile->id,
                        'file_url' => $path,
                        'status' => 'uploaded',
                        'uploaded_at' => now(),
                        'rejection_reason' => null
                    ]);

            return response()->json([
                'success' => true,
                'message' => 'Kontrak berhasil diunggah dan menunggu validasi Admin.',
                'data' => $contract
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mengunggah file.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ==========================================
    // FUNGSI ADMIN: Validasi Kontrak
    // ==========================================
    public function validateContract(Request $request, int $id)
    {
        // 1. Validasi input (Status hanya boleh: validated, rejected, revision)
        $request->validate([
            'status' => 'required|in:validated,rejected,revision',
            'rejection_reason' => 'required_if:status,rejected,revision|nullable|string'
        ]);

        // 2. Cari data kontrak berdasarkan ID, sekalian tarik data user dan authorProfile
        $contract = Contract::with('authorProfile.user')->find($id);

        if (!$contract) {
            return response()->json([
                'success' => false, 
                'message' => 'Data kontrak tidak ditemukan.'
            ], 404);
        }

        // 3. Update status kontrak
        $contract->status = $request->status;
        $contract->rejection_reason = $request->rejection_reason;

        // Jika disetujui, catat waktu validasinya dan jalankan Event
        if ($request->status === 'validated') {
            $contract->validated_at = now();
            
            // Ambil relasi data untuk dikirim ke Event
            $user = $contract->authorProfile->user;
            $bookTitle = $contract->authorProfile->book_title ?? 'Naskah Tanpa Judul';

            // Trigger Event untuk Kelompok 1 (Background Process)
            \App\Events\ContractValidated::dispatch([
                'user_id' => $user->id, 
                'email' => $user->email, 
                'name' => $user->name, 
                'manuscript_title' => $bookTitle
            ]);
        }

        $contract->save();

        return response()->json([
            'success' => true,
            'message' => 'Status kontrak berhasil diperbarui.',
            'data' => $contract
        ], 200);
    }

    // ==========================================
    // FUNGSI PENULIS: Melihat Status Kontrak Sendiri
    // ==========================================
    public function myContract(Request $request)
    {
        // 1. Pastikan yang login punya profil penulis
        $authorProfile = $request->user()->authorProfile;
        
        if (!$authorProfile) {
            return response()->json([
                'success' => false,
                'message' => 'Profil penulis tidak ditemukan.'
            ], 404);
        }

        // 2. Cari kontrak milik penulis ini (AMBIL YANG TERBARU!)
        $contract = Contract::where('author_profile_id', $authorProfile->id)
                            ->latest() // <--- WAJIB TAMBAHKAN INI AGAR MENGAMBIL DATA TERPARIPURNA
                            ->first();

        if (!$contract) {
            return response()->json([
                'success' => false,
                'message' => 'Anda belum mengunggah dokumen kontrak.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil mengambil data kontrak Anda.',
            'data' => $contract
        ], 200);
    }
}