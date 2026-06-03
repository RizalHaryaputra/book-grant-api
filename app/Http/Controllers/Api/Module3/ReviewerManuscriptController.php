<?php

namespace App\Http\Controllers\Api\Module3;

use App\Http\Controllers\Controller;
use App\Http\Resources\ManuscriptResource;
use App\Http\Resources\ReviewRubricResource;
use App\Models\Manuscript;
use App\Models\ReviewSubmission;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReviewerManuscriptController extends Controller
{
    /**
     * Get current reviewer
     */
    private function getCurrentReviewer(): ?User
    {
        // TODO: Uncomment this and remove the block underneath to switch to real auth
        // return auth('sanctum')->user() ?? auth()->user() ?? User::where('role_id', 2)->first();

        // Comments hardcoded 'sanctum' guard to prevent crashes when the guard is not yet defined
        return auth()->user() ?? User::where('role_id', 2)->first();
    }

    /**
     * Reviewer Dashboard
     */
    public function dashboard(): JsonResponse
    {
        $reviewer = $this->getCurrentReviewer();

        if (!$reviewer) {
            return response()->json([
                'success' => false,
                'message' => 'Reviewer not found.'
            ], 404);
        }

        $submissions = ReviewSubmission::where('reviewer_id', $reviewer->id)
            ->with(['manuscript.author'])
            ->get();

        $taskList = $submissions->map(function ($sub) {
            $m = $sub->manuscript;

            $statusText = 'Belum Review';
            $progress = 0;
            if ($sub->status === 'under_review') {
                $statusText = 'Sedang Review';
                $progress = 50;
            } elseif ($sub->status === 'review_completed') {
                $statusText = 'Selesai Review';
                $progress = 100;
            }

            return [
                'id' => $sub->id,
                'manuscript_id' => $sub->manuscript_id,
                'judul' => $m ? $m->title : '',
                'penulis' => $m && $m->author ? $m->author->name : '',
                'progres' => $progress,
                'tenggat' => $sub->deadline ? $sub->deadline->translatedFormat('j F Y') : 'Belum ditentukan',
                'status' => $statusText
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar tugas review berhasil diambil.',
            'data' => $taskList,
            'links' => [
                [
                    'rel' => 'self',
                    'method' => 'GET',
                    'href' => url('/api/reviewer/dashboard')
                ]
            ]
        ]);
    }

    /**
     * Get Manuscript Details for Review
     */
    public function show(int $manuscriptId): JsonResponse
    {
        $reviewer = $this->getCurrentReviewer();

        if (!$reviewer) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }

        $assignment = ReviewSubmission::where('reviewer_id', $reviewer->id)
            ->where('manuscript_id', $manuscriptId)
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }

        // Auto transition status when reviewer opens manuscript details
        if ($assignment->status === 'pending') {
            $assignment->update(['status' => 'under_review']);
        }

        $manuscript = Manuscript::findOrFail($manuscriptId);
        if ($manuscript->status === 'reviewer_assigned') {
            $manuscript->update(['status' => 'under_review']);
        }

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

        $reviewer = $this->getCurrentReviewer();

        if (!$reviewer) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }

        $assignment = ReviewSubmission::where('reviewer_id', $reviewer->id)
            ->where('manuscript_id', $manuscriptId)
            ->first();
            
        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You are not assigned to this manuscript.'
            ], 401);
        }

        $existingScores = [];
        $existingOutcome = null;
        $existingComment = null;

        if ($assignment->status === 'review_completed') {
            $scores = DB::table('review_scores')->where('rs_id', $assignment->id)->get();
            foreach ($scores as $score) {
                $existingScores[$score->rubric_id] = $score->nilai;
            }

            $existingOutcome = DB::table('review_outcomes')->where('rs_id', $assignment->id)->first();
            $commentRow = DB::table('review_comments')->where('rs_id', $assignment->id)->first();
            $existingComment = $commentRow ? $commentRow->comment : null;
        }

        $manuscript = Manuscript::findOrFail($manuscriptId);

        // Fetch rubric criteria from database matching the manuscript book type
        $rubricData = DB::table('assessment_rubric')
            ->where('book_type', $manuscript->book_type)
            ->where('status', 1)
            ->get()
            ->map(fn($row) => [
                'criteria_id' => $row->id,
                'aspect' => $row->criteria,
                'description' => $row->description,
                'max_score' => $row->weight,
                'submitted_score' => $existingScores[$row->id] ?? null
            ]);

        $response = [
            'success' => true,
            'message' => 'Rubrik berhasil diambil.',
            'data'    => ReviewRubricResource::collection($rubricData)
        ];

        if ($assignment->status === 'review_completed') {
            $response['submitted_review'] = [
                'final_score'   => $existingOutcome ? round($existingOutcome->overall_score, 2) : null,
                'status'        => $existingOutcome ? ($existingOutcome->status ? 'accepted' : 'rejected') : null,
                'feedback'      => $existingComment
            ];
        }

        return response()->json($response);
    }

    /**
     * Download Draft File for Reviewer
     * Mengunduh file draft awal (initial) yang diunggah penulis.
     */
    public function downloadDraft(int $manuscriptId)
    {
        $reviewer = $this->getCurrentReviewer();

        if (!$reviewer) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }

        $assignment = ReviewSubmission::where('reviewer_id', $reviewer->id)
            ->where('manuscript_id', $manuscriptId)
            ->first();

        if (!$assignment) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Anda tidak ditugaskan untuk naskah ini.'
            ], 401);
        }

        $manuscript = Manuscript::findOrFail($manuscriptId);
        $file = $manuscript->files()->where('file_type', 'initial')->first();

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File draft awal tidak ditemukan.'
            ], 404);
        }

        // Cek apakah file benar-benar ada di storage
        if (!Storage::disk('public')->exists($file->file_path)) {
            return response()->json([
                'success' => false,
                'message' => 'File tidak tersedia di server.'
            ], 404);
        }

        // Kembalikan file download
        return Storage::disk('public')->download($file->file_path, 'draft_' . $manuscriptId . '.pdf');
    }
}
