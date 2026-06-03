<?php

namespace App\Http\Controllers;

use App\Models\Manuscript;
use App\Models\ManuscriptFile;
use App\Models\AuthorDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManuscriptController extends Controller
{
    // FITUR 3: DASBOR PENULIS
    public function dashboard()
    {
        try {
            // SEMENTARA: Hardcode author_id = 1 karena fitur Login (Modul 1)
            $authorId = 1;
            // Nama disesuaikan dengan data dummy di tabel users yang kita buat sebelumnya
            $authorName = "Dummy Penulis"; 

            // Ambil 1 naskah yang paling terakhir dibuat sebagai "active_manuscript"
            $activeManuscript = Manuscript::where('author_id', $authorId)
                                ->latest()
                                ->first();

            // Ambil seluruh riwayat naskah milik penulis ini
            $history = Manuscript::where('author_id', $authorId)
                        ->orderBy('created_at', 'desc')
                        ->get();

            // Response
            return response()->json([
                'success' => true,
                'message' => 'Data dasbor berhasil diambil.',
                'data'    => [
                    'author_name'       => $authorName,
                    'active_manuscript' => $activeManuscript,
                    'history'           => $history
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dasbor: ' . $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }

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
        $request->validate([
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'document_type' => 'required|in:surat_pernyataan,scan_bermeterai'
        ]);

        try {
            $docTypeDB = $request->document_type === 'scan_bermeterai' ? 'scan_bermeteri' : 'surat_pernyataan';

            $file = $request->file('document_file');
            $fileName = time() . '_' . $docTypeDB . '_ms' . $manuscriptId . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('documents', $fileName, 'public');

            $document = AuthorDocument::create([
                'manuscript_id' => $manuscriptId,
                'document_type' => $docTypeDB,
                'file_path'     => $filePath,
                'is_valid'      => 0 
            ]);

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

    // FITUR 5: UPLOAD REVISI NASKAH
    public function uploadRevision(Request $request, $manuscriptId)
    {
        $validator = Validator::make($request->all(), [
            'file_revision' => 'required|file|mimes:pdf,docx|max:5120',
            'revision_note' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'data'    => $validator->errors()
            ], 422);
        }

        try {
            // TODO: Ganti dengan $request->user()->id saat auth Modul 1/Kelompok 4 sudah terintegrasi.
            $authorId = 1;

            $manuscript = Manuscript::find($manuscriptId);

            if (!$manuscript) {
                return response()->json([
                    'success' => false,
                    'message' => 'Naskah tidak ditemukan.',
                    'data'    => null
                ], 404);
            }

            if ((int) $manuscript->author_id !== $authorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke naskah ini.',
                    'data'    => null
                ], 403);
            }

            if ($manuscript->status !== 'revision_requested') {
                return response()->json([
                    'success' => false,
                    'message' => 'Revisi hanya dapat diunggah jika status naskah membutuhkan revisi.',
                    'data'    => [
                        'current_status' => $manuscript->status
                    ]
                ], 422);
            }

            $file = $request->file('file_revision');
            $fileName = time() . '_revision_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('revisions', $fileName, 'public');

            $latestVersion = ManuscriptFile::where('manuscript_id', $manuscriptId)->max('version') ?? 0;

            ManuscriptFile::create([
                'manuscript_id' => $manuscript->id,
                'file_path'     => $filePath,
                'file_type'     => 'revision',
                'version'       => ((int) $latestVersion) + 1,
                'revision_note' => $request->input('revision_note'),
            ]);

            $manuscript->status = 'revision_uploaded';
            $manuscript->save();

            return response()->json([
                'success' => true,
                'message' => 'Revisi naskah berhasil diunggah.',
                'data'    => $manuscript->fresh()
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah revisi naskah: ' . $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }

    // FITUR 4: MELIHAT HASIL REVIEW
    public function reviews($manuscriptId)
    {
        try {
            // TODO: Ganti dengan $request->user()->id saat auth Modul 1/Kelompok 4 sudah terintegrasi.
            $authorId = 1;

            $manuscript = Manuscript::find($manuscriptId);

            if (!$manuscript) {
                return response()->json([
                    'success' => false,
                    'message' => 'Naskah tidak ditemukan.',
                    'data'    => null
                ], 404);
            }

            if ((int) $manuscript->author_id !== $authorId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke naskah ini.',
                    'data'    => null
                ], 403);
            }

            $reviews = DB::table('review_submissions as rs')
                ->leftJoin('review_outcomes as ro', 'ro.rs_id', '=', 'rs.id')
                ->leftJoin('review_scores as rsc', 'rsc.rs_id', '=', 'rs.id')
                ->leftJoin('review_comments as rc', 'rc.rs_id', '=', 'rs.id')
                ->where('rs.manuscript_id', $manuscriptId)
                ->where('rs.status', 'review_completed')
                ->orderBy('rs.id')
                ->select([
                    'rs.id',
                    DB::raw('COALESCE(ro.overall_score, rsc.nile) as score'),
                    'rc.comment as feedback',
                ])
                ->get()
                ->values()
                ->map(function ($review, $index) {
                    return [
                        'reviewer_alias' => 'Reviewer ' . ($index + 1),
                        'score'          => $review->score === null ? null : (int) $review->score,
                        'feedback'       => $review->feedback,
                    ];
                });

            return response()->json([
                'success' => true,
                'message' => 'Hasil review berhasil diambil.',
                'data'    => $reviews
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil hasil review: ' . $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }
}
