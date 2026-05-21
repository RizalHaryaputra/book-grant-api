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
        $validated = $request->validated();
        
        // 1. Cari data penugasan review (ReviewSubmission) untuk reviewer yang sedang login
        $reviewerId = auth('sanctum')->id() ?? \App\Models\User::where('role_id', 3)->first()->id; // Sesuaikan role_id reviewer
        
        $submission = ReviewSubmission::where('manuscript_id', $manuscriptId)
            ->where('reviewer_id', $reviewerId)
            ->firstOrFail();

        // 2. Gunakan Database Transaction agar jika salah satu insert gagal, database tidak corrupt
        DB::transaction(function () use ($validated, $submission, $manuscriptId) {
            
            // Loop data nilai rubrik dari request dan simpan ke tabel review_scores
            foreach ($validated['rubric_scores'] as $scoreData) {
                DB::table('review_scores')->insert([
                    'rs_id' => $submission->id, // Berelasi ke review_submissions
                    'rubric_id' => $scoreData['criteria_id'],
                    'score' => $scoreData['score'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Simpan catatan naratif/kesimpulan ke tabel review_outcomes (sesuai skema SQL kamu)
            DB::table('review_outcomes')->insert([
                'rs_id' => $submission->id,
                'rubric_id' => $validated['rubric_scores'][0]['criteria_id'], // Atau sesuaikan FK database kamu
                'score_id' => null, // Jika opsional
                'feedback_text' => $validated['narrative_feedback'],
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 3. Update status penugasan reviewer menjadi 'review_completed'
            $submission->update(['status' => 'review_completed']);

            // 4. Cek apakah semua reviewer untuk naskah ini sudah selesai menilai
            $totalReviewers = ReviewSubmission::where('manuscript_id', $manuscriptId)->count();
            $completedReviewers = ReviewSubmission::where('manuscript_id', $manuscriptId)
                ->where('status', 'review_completed')
                ->count();

            // Jika semua reviewer sudah submit, ubah status naskah utama menjadi 'review_completed'
            if ($totalReviewers === $completedReviewers) {
                Manuscript::where('id', $manuscriptId)->update([
                    'status' => 'review_completed'
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Review berhasil disimpan ke database dan status diperbarui.',
            'data' => [
                'manuscript_id' => $manuscriptId,
                'status' => 'review_completed'
            ]
        ]);
    }

    /**
     * Get Compiled Reviews (Mengambil akumulasi nilai riil dari database)
     */
    public function getCompiledReviews(int $manuscriptId): JsonResponse
    {
        $manuscript = Manuscript::findOrFail($manuscriptId);

        // 1. Hitung rata-rata nilai dari seluruh reviewer untuk naskah ini
        $averageScore = DB::table('review_submissions')
            ->join('review_scores', 'review_submissions::id', '=', 'review_scores.rs_id')
            ->where('review_submissions.manuscript_id', $manuscriptId)
            ->avg('review_scores.score');

        // 2. Ambil semua catatan feedback naratif dari tabel review_outcomes
        $feedbacks = DB::table('review_submissions')
            ->join('users', 'review_submissions.reviewer_id', '=', 'users.id')
            ->join('review_outcomes', 'review_submissions.id', '=', 'review_outcomes.rs_id')
            ->where('review_submissions.manuscript_id', $manuscriptId)
            ->select('users.name as reviewer_name', 'review_outcomes.feedback_text')
            ->get()
            ->map(function($item, $index) {
                return [
                    'reviewer_alias' => 'Reviewer ' . ($index + 1), // Anonimitas reviewer sesuai standar double-blind review
                    'feedback' => $item->feedback_text
                ];
            });

        // 3. Tentukan rekomendasi keputusan sistem berdasarkan nilai rata-rata
        $decision = 'requires_major_revision';
        if ($averageScore >= 80) {
            $decision = 'accepted_with_minor_revisions';
        } elseif ($averageScore >= 60) {
            $decision = 'requires_major_revision';
        } else {
            $decision = 'rejected';
        }

        $compiled = [
            'manuscript_id' => $manuscriptId,
            'title' => $manuscript->title,
            'average_score' => round($averageScore, 2),
            'decision' => $decision,
            'reviewer_feedbacks' => $feedbacks,
            'compiled_at' => now()->toIso8601String()
        ];

        return response()->json([
            'success' => true,
            'message' => 'Kompilasi review berhasil dihitung dari database.',
            'data' => new \App\Http\Resources\CompiledReviewResource($compiled)
        ]);
    }
}
