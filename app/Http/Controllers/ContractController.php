<?php

namespace App\Http\Controllers;

use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContractController extends Controller
{
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

        // 2. Validasi input (Wajib berupa file PDF, maksimal 5MB)
        $validator = Validator::make($request->all(), [
            'contract_file' => 'required|file|mimes:pdf|max:5120', 
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
            $file = $request->file('contract_file');
            $filename = time() . '_kontrak_' . $authorProfile->id . '.' . $file->getClientOriginalExtension();
            
            // File akan tersimpan di folder: storage/app/public/contracts
            $path = $file->storeAs('contracts', $filename, 'public');

            // 4. Simpan atau Update data di database
            $contract = Contract::updateOrCreate(
                ['author_profile_id' => $authorProfile->id], // Cari berdasarkan ID penulis
                [
                    'file_url' => $path,
                    'status' => 'uploaded',
                    'uploaded_at' => now(),
                    'rejection_reason' => null // Reset alasan penolakan jika upload ulang
                ]
            );

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

        // 2. Cari data kontrak berdasarkan ID, sekalian tarik data email usernya
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

        // Jika disetujui, catat waktu validasinya
        if ($request->status === 'validated') {
            $contract->validated_at = now();
        }

        $contract->save();

        // 4. (Opsional) Tembak Notifikasi ke Server Dummy Kelompok 1
        try {
            $userEmail = $contract->authorProfile->user->email ?? 'dummy@mail.com';
            $pesan = "Status kontrak Anda saat ini adalah: " . strtoupper($request->status) . ". ";
            
            if ($request->status === 'validated') {
                $pesan .= "Selamat! Kontrak Anda valid. Silakan lanjut mengunggah Draft Awal Naskah Anda.";
            } else {
                $pesan .= "Catatan Admin: " . $request->rejection_reason;
            }

            \Illuminate\Support\Facades\Http::post('http://127.0.0.1:8001/api/notification/send', [
                'to' => $userEmail,
                'subject' => "Update Validasi Kontrak - Hibah Buku",
                'body' => $pesan,
                'type' => 'kontrak'
            ]);
        } catch (\Exception $e) {
            // Abaikan jika server notifikasi port 8001 sedang mati
        }

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

        // 2. Cari kontrak milik penulis ini
        $contract = Contract::where('author_profile_id', $authorProfile->id)->first();

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