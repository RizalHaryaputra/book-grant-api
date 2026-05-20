<?php

namespace App\Http\Controllers\Api\Module3;

use App\Http\Controllers\Controller;
use App\Http\Resources\ManuscriptResource;
use App\Http\Resources\ReviewRubricResource;
use Illuminate\Http\JsonResponse;

class ReviewerManuscriptController extends Controller
{
    /**
     * Reviewer Dashboard
     */
    public function dashboard(): JsonResponse
    {
        $dashboardStats = [
            'total_assigned' => 5,
            'completed_reviews' => 2,
            'pending_reviews' => 3,
            'upcoming_deadlines' => [
                [
                    'manuscript_id' => 3,
                    'title' => 'Pengembangan Sistem Berbasis Cloud',
                    'deadline' => '2026-05-25'
                ]
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Data dasbor berhasil diambil.',
            // Using a simple array since the contract says SuccessResponseList (though it might be object depending on interpretation)
            'data' => [$dashboardStats]
        ]);
    }

    /**
     * Get Manuscript Details for Review
     */
    public function show(int $manuscriptId): JsonResponse
    {
        // Mock manuscript
        $manuscript = [
            'id' => $manuscriptId,
            'title' => 'Buku Ajar Pemrograman Lanjut',
            'abstract' => 'Buku ini membahas topik lanjutan dalam pemrograman...',
            'book_type' => 'buku_ajar',
            'file_url' => 'http://localhost:8000/storage/manuscripts/draft_123.pdf',
            'status' => 'review_in_progress',
            'author' => 'Dr. Andi'
        ];

        return response()->json([
            'success' => true,
            'message' => 'Detail naskah berhasil diambil.',
            'data' => new ManuscriptResource($manuscript)
        ]);
    }

    /**
     * Get Review Rubric
     */
    public function getRubric(int $manuscriptId): JsonResponse
    {
        // Mock rubric criteria
        $rubric = [
            [
                'criteria_id' => 1,
                'aspect' => 'Kesesuaian dengan RPS',
                'description' => 'Apakah materi sesuai dengan Rencana Pembelajaran Semester?',
                'max_score' => 20
            ],
            [
                'criteria_id' => 2,
                'aspect' => 'Kedalaman Materi',
                'description' => 'Tingkat kedalaman pembahasan materi.',
                'max_score' => 30
            ],
            [
                'criteria_id' => 3,
                'aspect' => 'Keterbacaan & Bahasa',
                'description' => 'Penggunaan bahasa yang mudah dipahami mahasiswa.',
                'max_score' => 20
            ]
        ];

        return response()->json([
            'success' => true,
            'message' => 'Rubrik berhasil diambil.',
            'data' => ReviewRubricResource::collection($rubric)
        ]);
    }
}
