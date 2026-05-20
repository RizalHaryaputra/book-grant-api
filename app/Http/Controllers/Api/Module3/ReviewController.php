<?php

namespace App\Http\Controllers\Api\Module3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Module3\SubmitReviewRequest;
use App\Http\Resources\CompiledReviewResource;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    /**
     * Submit Review Result
     */
    public function submitReview(SubmitReviewRequest $request, int $manuscriptId): JsonResponse
    {
        // Mock successful submission
        return response()->json([
            'success' => true,
            'message' => 'Review berhasil dikirim.',
            'data' => [
                'manuscript_id' => $manuscriptId,
                'status' => 'review_completed'
            ]
        ]);
    }

    /**
     * Get Compiled Reviews
     */
    public function getCompiledReviews(int $manuscriptId): JsonResponse
    {
        // Mock compiled review
        $compiled = [
            'manuscript_id' => $manuscriptId,
            'title' => 'Buku Ajar Pemrograman Lanjut',
            'average_score' => 82.5,
            'decision' => 'accepted_with_minor_revisions',
            'reviewer_feedbacks' => [
                [
                    'reviewer_alias' => 'Reviewer 1',
                    'score' => 85,
                    'feedback' => 'Materi bagus, tambahkan latihan soal.'
                ],
                [
                    'reviewer_alias' => 'Reviewer 2',
                    'score' => 80,
                    'feedback' => 'Beberapa paragraf kurang jelas, perbaiki tata bahasa.'
                ]
            ],
            'compiled_at' => '2026-05-22T09:00:00Z'
        ];

        return response()->json([
            'success' => true,
            'message' => 'Kompilasi review berhasil diambil.',
            'data' => new CompiledReviewResource($compiled)
        ]);
    }
}
