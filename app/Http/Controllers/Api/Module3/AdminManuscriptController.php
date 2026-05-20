<?php

namespace App\Http\Controllers\Api\Module3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Module3\AssignReviewerRequest;
use App\Http\Resources\ManuscriptResource;
use Illuminate\Http\JsonResponse;

class AdminManuscriptController extends Controller
{
    /**
     * Get Unassigned Manuscripts
     */
    public function getUnassigned(): JsonResponse
    {
        // Mock unassigned manuscripts
        $unassigned = [
            [
                'id' => 1,
                'title' => 'Buku Ajar Algoritma dan Pemrograman',
                'book_type' => 'buku_ajar',
                'status' => 'unassigned',
                'author' => 'Dr. Budi',
                'submitted_at' => '2026-05-15T10:00:00Z',
            ],
            [
                'id' => 2,
                'title' => 'Monograf Machine Learning',
                'book_type' => 'buku_referensi',
                'status' => 'unassigned',
                'author' => 'Prof. Siti',
                'submitted_at' => '2026-05-18T14:30:00Z',
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Daftar naskah belum diplot berhasil diambil.',
            'data' => ManuscriptResource::collection($unassigned)
        ]);
    }

    /**
     * Assign Reviewer to Manuscript
     */
    public function assignReviewer(AssignReviewerRequest $request, int $manuscriptId): JsonResponse
    {
        // In a real app, we would save to the DB here.
        // For the mock, we just validate the request and return success.

        return response()->json([
            'success' => true,
            'message' => 'Reviewer berhasil ditugaskan.',
            'data' => null // SuccessResponse as per contract
        ], 200);
    }
}
