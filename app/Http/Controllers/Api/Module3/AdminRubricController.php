<?php

namespace App\Http\Controllers\Api\Module3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Module3\StoreRubricRequest;
use App\Http\Requests\Module3\UpdateRubricRequest;
use App\Models\AssessmentRubric;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminRubricController extends Controller
{
    /**
     * GET /admin/rubrics
     * List semua kriteria rubrik (bisa filter by book_type)
     */
    public function index(): JsonResponse
    {
        $bookType = request('book_type');
        $query = AssessmentRubric::query();

        if ($bookType && in_array($bookType, ['Buku Ajar', 'Buku Referensi'])) {
            $query->where('book_type', $bookType);
        }

        $rubrics = $query->orderBy('book_type')->orderBy('weight', 'desc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar rubrik berhasil diambil.',
            'data' => $rubrics
        ]);
    }

    /**
     * GET /admin/rubrics/{id}
     * Detail satu kriteria rubrik
     */
    public function show(int $id): JsonResponse
    {
        $rubric = AssessmentRubric::find($id);
        if (!$rubric) {
            return response()->json([
                'success' => false,
                'message' => 'Rubrik tidak ditemukan.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Detail rubrik berhasil diambil.',
            'data' => $rubric
        ]);
    }

    /**
     * POST /admin/rubrics
     * Tambah kriteria rubrik baru
     */
    public function store(StoreRubricRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Set default status = 1 (aktif) jika tidak dikirim
        if (!isset($validated['status'])) {
            $validated['status'] = 1;
        }

        DB::beginTransaction();
        try {
            $rubric = AssessmentRubric::create($validated);

            // Validasi total bobot setelah insert
            $currentTotal = AssessmentRubric::getCurrentTotalWeight($rubric->book_type);
            if ($currentTotal > 100) {
                // Batalkan insert
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => "Total bobot untuk {$rubric->book_type} melebihi 100. (Saat ini: {$currentTotal}/100)",
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kriteria rubrik berhasil ditambahkan.',
                'data' => $rubric
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /admin/rubrics/{id}
     * Update kriteria rubrik
     */
    public function update(UpdateRubricRequest $request, int $id): JsonResponse
    {
        $rubric = AssessmentRubric::find($id);
        if (!$rubric) {
            return response()->json([
                'success' => false,
                'message' => 'Rubrik tidak ditemukan.'
            ], 404);
        }

        $oldBookType = $rubric->book_type;
        $oldWeight = $rubric->weight;
        $oldStatus = $rubric->status;

        DB::beginTransaction();
        try {
            $rubric->update($request->validated());

            // Ambil data terbaru setelah update
            $newBookType = $rubric->book_type;
            $newStatus = $rubric->status;

            // Periksa total bobot untuk book_type yang mungkin berubah
            // Kita perlu cek untuk kedua kemungkinan book_type (lama dan baru jika berbeda)
            $errors = [];

            if ($oldBookType === $newBookType) {
                $total = AssessmentRubric::getCurrentTotalWeight($newBookType, $rubric->id);
                if ($total > 100) {
                    $errors[] = "Total bobot untuk {$newBookType} melebihi 100. (Saat ini: {$total}/100)";
                }
            } else {
                // Cek total untuk book_type lama (setelah mungkin ada pengurangan bobot)
                $totalOld = AssessmentRubric::getCurrentTotalWeight($oldBookType, $rubric->id);
                if ($totalOld > 100) {
                    $errors[] = "Total bobot untuk {$oldBookType} melebihi 100. (Saat ini: {$totalOld}/100)";
                }
                // Cek total untuk book_type baru
                $totalNew = AssessmentRubric::getCurrentTotalWeight($newBookType, $rubric->id);
                if ($totalNew > 100) {
                    $errors[] = "Total bobot untuk {$newBookType} melebihi 100. (Saat ini: {$totalNew}/100)";
                }
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi bobot gagal.',
                    'errors' => $errors
                ], 422);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kriteria rubrik berhasil diperbarui.',
                'data' => $rubric->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /admin/rubrics/{id}
     * Hapus kriteria rubrik
     */
    public function destroy(int $id): JsonResponse
    {
        $rubric = AssessmentRubric::find($id);
        if (!$rubric) {
            return response()->json([
                'success' => false,
                'message' => 'Rubrik tidak ditemukan.'
            ], 404);
        }

        $rubric->delete();

        return response()->json([
            'success' => true,
            'message' => 'Kriteria rubrik berhasil dihapus.'
        ]);
    }
}