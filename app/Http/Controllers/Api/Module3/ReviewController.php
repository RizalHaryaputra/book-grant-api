<?php

namespace App\Http\Controllers\Api\Module3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Module3\SubmitReviewRequest;
use App\Http\Resources\CompiledReviewResource;
use App\Models\Manuscript;
use App\Models\ReviewSubmission;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    /**
     * Submit Review Result
     *
     * Menyimpan skor per kriteria, naratif feedback, dan mengupdate status review.
     */
    public function submitReview(SubmitReviewRequest $request, int $manuscriptId): JsonResponse
    {
        $reviewerId = auth()->id();

        if (!$reviewerId) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Cari penugasan reviewer yang masih pending atau under_review
        $submission = ReviewSubmission::where('manuscript_id', $manuscriptId)
            ->where('reviewer_id', $reviewerId)
            ->whereIn('status', ['pending', 'under_review'])
            ->first();

        if (!$submission) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada penugasan review yang aktif untuk naskah ini.',
            ], 404);
        }

        $validated = $request->validated();

        DB::transaction(function () use ($validated, $submission, $manuscriptId) {
            // 1. Simpan skor per kriteria ke tabel review_scores
            foreach ($validated['rubric_scores'] as $scoreData) {
                DB::table('review_scores')->insert([
                    'rs_id'      => $submission->id,
                    'rubric_id'  => $scoreData['criteria_id'],
                    'nilai'      => $scoreData['score'], // kolom 'nilai' sesuai database
                ]);
            }

            // 2. Simpan narrative feedback ke tabel review_comments
            DB::table('review_comments')->insert([
                'rs_id'   => $submission->id,
                'comment' => $validated['narrative_feedback'],
            ]);

            // 3. Calculate final score for THIS submission
            $weightedResult = DB::table('review_scores')
                ->join('assessment_rubric', 'review_scores.rubric_id', '=', 'assessment_rubric.id')
                ->where('review_scores.rs_id', $submission->id)
                ->selectRaw('SUM(review_scores.nilai * assessment_rubric.weight) as weighted_sum, SUM(assessment_rubric.weight) as total_weight')
                ->first();

            $reviewerScore = ($weightedResult && $weightedResult->total_weight > 0)
                ? $weightedResult->weighted_sum / $weightedResult->total_weight
                : 0;

            // 4. Save to review_outcomes summary table
            DB::table('review_outcomes')->insert([
                'rs_id'         => $submission->id,
                'overall_score' => $reviewerScore,
                'status'        => $reviewerScore >= 75 ? 1 : 0,
            ]);

            // 5. Update status penugasan reviewer
            $submission->update(['status' => 'review_completed']);

            // 6. Cek apakah semua reviewer untuk naskah ini sudah selesai
            $totalReviewers = ReviewSubmission::where('manuscript_id', $manuscriptId)->count();
            $completedReviewers = ReviewSubmission::where('manuscript_id', $manuscriptId)
                ->where('status', 'review_completed')
                ->count();

            if ($totalReviewers === $completedReviewers) {
                Manuscript::where('id', $manuscriptId)->update(['status' => 'review_completed']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil disimpan.',
            'data' => [
                'manuscript_id' => $manuscriptId,
                'status'        => 'review_completed',
            ],
        ]);
    }

    /**
     * Get Compiled Reviews
     *
     * Mengambil rata-rata skor dan semua feedback untuk sebuah naskah.
     */
    public function getCompiledReviews(int $manuscriptId): JsonResponse
    {
        $manuscript = Manuscript::findOrFail($manuscriptId);

        // Hitung rata-rata skor akhir dari semua reviewer (diambil langsung dari summary table)
        $compiledScore = DB::table('review_submissions')
            ->join('review_outcomes', 'review_submissions.id', '=', 'review_outcomes.rs_id')
            ->where('review_submissions.manuscript_id', $manuscriptId)
            ->avg('review_outcomes.overall_score') ?? 0;

        // Ambil semua feedback naratif dan skor masing-masing reviewer dari summary table
        $feedbacks = DB::table('review_submissions')
            ->join('review_comments', 'review_submissions.id', '=', 'review_comments.rs_id')
            ->join('review_outcomes', 'review_submissions.id', '=', 'review_outcomes.rs_id')
            ->where('review_submissions.manuscript_id', $manuscriptId)
            ->select('review_comments.comment', 'review_outcomes.overall_score')
            ->get()
            ->map(function ($item, $index) {
                return [
                    'reviewer_alias' => 'Reviewer ' . ($index + 1),
                    'score'          => round($item->overall_score, 2),
                    'feedback'       => $item->comment,
                ];
            });

        // Keputusan: diterima jika skor tertimbang >= 75, ditolak jika di bawahnya
        $decision = $compiledScore >= 75 ? 'accepted' : 'rejected';

        $compiled = [
            'manuscript_id'      => $manuscriptId,
            'title'              => $manuscript->title,
            'overall_score'      => round($compiledScore, 2),
            'decision'           => $decision,
            'reviewer_feedbacks' => $feedbacks,
            'compiled_at'        => now()->toIso8601String(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Kompilasi review berhasil diambil.',
            'data'    => new CompiledReviewResource($compiled),
        ]);
    }
}