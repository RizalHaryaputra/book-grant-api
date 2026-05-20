<?php

namespace App\Http\Controllers;

use App\Models\Manuscript;
use App\Models\ManuscriptFile;
use Illuminate\Http\Request;

class ManuscriptController extends Controller
{
    public function uploadDraft(Request $request)
    {
        // Validasi Input Sesuai api-contract-project.yaml
        $request->validate([
            'file_draft'  => 'required|file|mimes:pdf,docx|max:5120',
            'title'       => 'required|string|max:255',
            'book_type'   => 'required|in:ajar,referensi',
            'abstract'    => 'required|string',
            'total_pages' => 'required|integer',
            'category'    => 'required|string',
        ]);

        try {
            $bookTypeDB = $request->book_type === 'ajar' ? 'Buku Ajar' : 'Buku Referensi';

            // Simpan Data Teks ke Tabel manuscripts
            $manuscript = Manuscript::create([
                'author_id'     => 1, 
                'title'         => $request->title,
                'book_type'     => $bookTypeDB,
                'abstract'      => $request->abstract,
                'science_field' => $request->category,
                'total_pages'   => $request->total_pages,
                'status'        => 'initial_draft_uploaded',
            ]);

            // Simpan File Fisik ke Storage Lokal
            $file = $request->file('file_draft');
            $fileName = time() . '_draft_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('drafts', $fileName, 'public');

            // Catat File ke Tabel manuscript_files (Relasi)
            ManuscriptFile::create([
                'manuscript_id' => $manuscript->id,
                'file_path'     => $filePath,
                'file_type'     => 'initial',
                'version'       => 1,
            ]);

            // Kembalikan Response Sesuai Standar Integrasi Modul
            return response()->json([
                'success' => true,
                'message' => 'Operasi manuskrip berhasil dilakukan.',
                'data'    => $manuscript
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah draft: ' . $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }
}