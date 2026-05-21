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

class ReviewerManuscriptController extends Controller
{
    /**
     * Get current reviewer
     */
    private function getCurrentReviewer(): ?User
    {
        return auth('sanctum')->user() ?? auth()->user() ?? User::where('role_id', 2)->first();
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
            'data' => $taskList
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
                'max_score' => $row->weight
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Rubrik berhasil diambil.',
            'data' => ReviewRubricResource::collection($rubricData)
        ]);
    }
}
