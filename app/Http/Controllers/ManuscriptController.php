<?php

namespace App\Http\Controllers;

use App\Models\Manuscript;
use App\Models\ManuscriptFile;
use App\Models\AuthorDocument; // <-- Tambahan model baru
use Illuminate\Http\Request;

class ManuscriptController extends Controller
{
    // FITUR 1: UPLOAD DRAFT AWAL
    public function uploadDraft(Request $request)
    {
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

            $manuscript = Manuscript::create([
                'author_id'     => 1, 
                'title'         => $request->title,
                'book_type'     => $bookTypeDB,
                'abstract'      => $request->abstract,
                'science_field' => $request->category,
                'total_pages'   => $request->total_pages,
                'status'        => 'initial_draft_uploaded',
            ]);

            $file = $request->file('file_draft');
            $fileName = time() . '_draft_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('drafts', $fileName, 'public');

            ManuscriptFile::create([
                'manuscript_id' => $manuscript->id,
                'file_path'     => $filePath,
                'file_type'     => 'initial',
                'version'       => 1,
            ]);

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

    // FITUR 2: UPLOAD DOKUMEN ADMINISTRASI
    public function uploadDocument(Request $request, $manuscriptId)
    {
        // 1. Validasi File (Hanya PDF/JPG/PNG, maksimal 5MB) dan Tipe Dokumen
        $request->validate([
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'document_type' => 'required|in:surat_pernyataan,scan_bermeterai'
        ]);

        try {
            $docTypeDB = $request->document_type === 'scan_bermeterai' ? 'scan_bermeteri' : 'surat_pernyataan';

            // 2. Simpan File Fisik
            $file = $request->file('document_file');
            $fileName = time() . '_' . $docTypeDB . '_ms' . $manuscriptId . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('documents', $fileName, 'public');

            // 3. Simpan ke Database
            $document = AuthorDocument::create([
                'manuscript_id' => $manuscriptId,
                'document_type' => $docTypeDB,
                'file_path'     => $filePath,
                'is_valid'      => 0 
            ]);

            // 4. Response
            return response()->json([
                'success' => true,
                'message' => 'Dokumen administrasi berhasil diunggah.',
                'data'    => $document
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah dokumen: ' . $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }
}