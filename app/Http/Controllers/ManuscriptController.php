<?php

namespace App\Http\Controllers;

use App\Models\Manuscript;
use App\Models\ManuscriptFile;
use App\Models\AuthorDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ManuscriptController extends Controller
{
    /**
     * [FITUR 3] Menampilkan data dasbor milik penulis.
     *
     * Mengambil naskah aktif (terbaru) dan seluruh riwayat naskah
     * yang dimiliki oleh penulis yang sedang login.
     *
     */
    public function dashboard()
    {
        try {
            $authorId = 1;
            $authorName = "Dummy Penulis";

            $activeManuscript = Manuscript::where('author_id', $authorId)
                                ->latest()
                                ->first();

            $history = Manuscript::where('author_id', $authorId)
                        ->orderBy('created_at', 'desc')
                        ->get();

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

    /**
     * [FITUR 1] Mengunggah draft awal naskah buku.
     *
     * Menerima file draft (PDF/DOCX) beserta metadata naskah seperti judul,
     * jenis buku, abstrak, dan jumlah halaman. Membuat record baru pada tabel
     * manuscripts dan manuscript_files dengan status 'initial_draft_uploaded'.
     *
     */
    public function uploadDraft(Request $request)
    {
        $request->validate([
            'file_draft'  => 'required|file|mimes:pdf,docx|max:5120',
            'title'       => 'required|string|max:255',
            'book_type'   => 'required|in:Buku Ajar,Buku Referensi',
            'abstract'    => 'required|string',
            'total_pages' => 'required|integer|min:1',
        ]);

        try {
            $manuscript = Manuscript::create([
                'author_id'     => 1,
                'title'         => $request->title,
                'book_type'     => $request->book_type,
                'abstract'      => $request->abstract,
                'science_field' => $request->science_field,
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

    /**
     * [FITUR 2] Mengunggah dokumen administrasi untuk suatu naskah.
     *
     * Menerima file dokumen (PDF/JPG/PNG) dengan jenis tertentu
     * (surat_pernyataan atau scan_bermeteri). Jika dokumen dengan jenis yang
     * sama sudah pernah diunggah sebelumnya, file lama akan dihapus dan
     * datanya diperbarui (upsert). Status validasi direset ke 0 (belum valid).
     *
     */
    public function uploadDocument(Request $request, $manuscriptId)
    {
        $request->validate([
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'document_type' => 'required|in:surat_pernyataan,scan_bermeteri'
        ]);

        try {
            $docTypeDB = $request->document_type;
            $file = $request->file('document_file');

            $fileName = time() . '_' .
                        $docTypeDB .
                        '_ms' .
                        $manuscriptId .
                        '.' .
                        $file->getClientOriginalExtension();

            $filePath = $file->storeAs('documents', $fileName, 'public');

            $existingDocument = AuthorDocument::where('manuscript_id', $manuscriptId)
                ->where('document_type', $docTypeDB)
                ->first();

            if ($existingDocument) {
                if (Storage::disk('public')->exists($existingDocument->file_path)) {
                    Storage::disk('public')->delete($existingDocument->file_path);
                }

                $existingDocument->update([
                    'file_path'   => $filePath,
                    'is_valid'    => 0,
                    'uploaded_at' => now()
                ]);

                $document = $existingDocument;
                $message  = 'Dokumen berhasil diperbarui.';

            } else {
                $document = AuthorDocument::create([
                    'manuscript_id' => $manuscriptId,
                    'document_type' => $docTypeDB,
                    'file_path'     => $filePath,
                    'is_valid'      => 0
                ]);

                $message = 'Dokumen administrasi berhasil diunggah.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data'    => $document
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengunggah dokumen: ' . $e->getMessage(),
                'data'    => null
            ], 500);
        }
    }

    /**
     * [FITUR 5] Mengunggah file revisi naskah.
     *
     * Hanya dapat dilakukan jika status naskah saat ini adalah 'revision_requested'.
     * File revisi disimpan dengan nomor versi yang otomatis bertambah dari versi terakhir.
     * Setelah berhasil, status naskah diubah menjadi 'revision_uploaded'.
     */
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
            $authorId   = 1;
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
                    'data'    => ['current_status' => $manuscript->status]
                ], 422);
            }

            $file     = $request->file('file_revision');
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

    /**
     * [FITUR 4] Menampilkan hasil review naskah oleh reviewer.
     *
     * Mengambil data review yang telah selesai (status 'review_completed') dari
     * tabel review_submissions beserta skor dan komentar masing-masing reviewer.
     * Identitas reviewer disembunyikan dan diganti alias (Reviewer 1, Reviewer 2, dst.)
     * untuk menjaga anonimitas. Jika belum ada data review nyata, mengembalikan
     * data dummy sebagai fallback sementara.
     *
     */
    public function reviews($manuscriptId)
    {
        try {
            $authorId   = 1;
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

            if ($reviews->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Hasil review dummy.',
                    'data'    => [
                        'review_status'  => 'review_completed',
                        'recommendation' => 'revision_requested',
                        'overall_score'  => 86,
                        'reviews'        => [
                            ['reviewer_alias' => 'Reviewer 1', 'score' => 85, 'feedback' => 'Bab 3 perlu diperjelas.'],
                            ['reviewer_alias' => 'Reviewer 2', 'score' => 88, 'feedback' => 'Tambahkan referensi terbaru.']
                        ]
                    ]
                ]);
            }

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

    /**
     * Menampilkan detail satu naskah berdasarkan ID.
     *
     * Mengambil seluruh data kolom dari tabel manuscripts untuk naskah
     * dengan ID yang diberikan. Mengembalikan 404 jika naskah tidak ditemukan.
     *
     */
    public function show($manuscriptId)
    {
        $manuscript = Manuscript::find($manuscriptId);

        if (!$manuscript) {
            return response()->json([
                'success' => false,
                'message' => 'Naskah tidak ditemukan.',
                'data'    => null
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail naskah berhasil diambil.',
            'data'    => $manuscript
        ], 200);
    }

    /**
     * Memperbarui metadata naskah yang sudah ada.
     *
     * Memungkinkan penulis untuk mengedit informasi utama naskah seperti judul,
     * jenis buku, abstrak, bidang ilmu, dan jumlah halaman tanpa harus mengunggah
     * ulang file. Mengembalikan 404 jika naskah tidak ditemukan.
     *
     */
    public function updateMetadata(Request $request, $manuscriptId)
    {
        $request->validate([
            'title'         => 'required|string|max:255',
            'book_type'     => 'required',
            'abstract'      => 'required|string',
            'science_field' => 'required',
            'total_pages'   => 'required|integer'
        ]);

        $manuscript = Manuscript::find($manuscriptId);

        if (!$manuscript) {
            return response()->json([
                'success' => false,
                'message' => 'Naskah tidak ditemukan.',
                'data'    => null
            ], 404);
        }

        $manuscript->update([
            'title'         => $request->title,
            'book_type'     => $request->book_type,
            'abstract'      => $request->abstract,
            'science_field' => $request->science_field,
            'total_pages'   => $request->total_pages,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Metadata berhasil diperbarui.',
            'data'    => $manuscript
        ]);
    }

    /**
     * Menampilkan riwayat seluruh file yang terkait dengan suatu naskah.
     *
     * Mengambil semua record dari tabel manuscript_files untuk naskah tertentu,
     * diurutkan dari versi terbaru ke terlama. Mencakup file draft awal
     * maupun file revisi yang pernah diunggah.
     *
     */
    public function files($manuscriptId)
    {
        $files = ManuscriptFile::where('manuscript_id', $manuscriptId)
            ->orderBy('version', 'desc')
            ->get()
            ->map(function ($file) {

                $jenis = 'Draft Awal';

                if ($file->file_type === 'revision') {

                    if (
                        $file->revision_note &&
                        str_contains($file->revision_note, '[PREPRINT]')
                    ) {

                        $jenis = 'Revisi Pra-Cetak';

                    } else {

                        $jenis = 'Revisi Reviewer';
                    }
                }

                $file->display_type = $jenis;

                return $file;
            });

        return response()->json([
            'success' => true,
            'message' => 'Riwayat file berhasil diambil.',
            'data' => $files
        ]);
    }

    /**
     * Menampilkan seluruh dokumen administrasi milik suatu naskah.
     *
     * Mengambil semua record dari tabel author_documents yang terhubung
     * dengan naskah tertentu, termasuk status validasi (is_valid) dari
     * masing-masing dokumen (surat_pernyataan, scan_bermeteri, dll.).
     *
     */
    public function documents($manuscriptId)
    {
        $documents = AuthorDocument::where('manuscript_id', $manuscriptId)->get();

        return response()->json([
            'success' => true,
            'message' => 'Dokumen administrasi berhasil diambil.',
            'data'    => $documents
        ]);
    }

    #endpoint publiser check
    public function publisherCheck($manuscriptId)
    {
        try {

            $publisherCheck = DB::table('publisher_checks')
                ->where('manuscript_id', $manuscriptId)
                ->latest('id')
                ->first();

            // Dummy jika belum ada data dari kelompok publisher
            if (!$publisherCheck) {

                return response()->json([
                    'success' => true,
                    'message' => 'Data dummy publisher check.',
                    'data' => [
                        'cover_ok' => false,
                        'page_count_ok' => true,
                        'admin_docs_ok' => false,
                        'decision' => 'revised',
                        'notes' => 'Cover belum sesuai template penerbit dan surat pernyataan perlu diperbarui.',
                        'checked_at' => now()
                    ]
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Hasil pemeriksaan penerbit berhasil diambil.',
                'data' => $publisherCheck
            ]);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil publisher check: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    #endpoint revisi pra-cetak
    public function uploadPreprintRevision(Request $request, $manuscriptId)
    {
        $request->validate([
            'revision_file' => 'required|file|mimes:pdf,doc,docx|max:5120',
            'revision_note' => 'nullable|string'
        ]);

        try {

            $manuscript = Manuscript::find($manuscriptId);

            if (!$manuscript) {
                return response()->json([
                    'success' => false,
                    'message' => 'Naskah tidak ditemukan.',
                    'data' => null
                ], 404);
            }

            $lastVersion = ManuscriptFile::where(
                'manuscript_id',
                $manuscriptId
            )->max('version');

            $nextVersion = ($lastVersion ?? 0) + 1;

            $file = $request->file('revision_file');

            $fileName =
                time() .
                '_preprint_revision_v' .
                $nextVersion .
                '.' .
                $file->getClientOriginalExtension();

            $filePath = $file->storeAs(
                'preprint_revisions',
                $fileName,
                'public'
            );

            $revision = ManuscriptFile::create([
                'manuscript_id' => $manuscriptId,
                'file_path' => $filePath,
                'file_type' => 'revision',
                'version' => $nextVersion,
                'revision_note' => '[PREPRINT] ' . $request->revision_note
            ]);

            $manuscript->update([
                'status' => 'preprint_revision_uploaded'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Revisi pra-cetak berhasil diunggah.',
                'data' => $revision
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Gagal upload revisi pra-cetak: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }
}