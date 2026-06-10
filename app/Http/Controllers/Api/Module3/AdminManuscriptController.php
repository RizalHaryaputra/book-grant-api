<?php

namespace App\Http\Controllers\Api\Module3;

use App\Http\Controllers\Controller;
use App\Http\Requests\Module3\AssignReviewerRequest;
use App\Http\Resources\ManuscriptResource;
use App\Models\Manuscript;
use App\Models\ReviewSubmission;
use Illuminate\Http\JsonResponse;

class AdminManuscriptController extends Controller
{
    /**
     * Get Unassigned Manuscripts
     */
    public function getUnassigned(): JsonResponse
    {
        // Get all manuscripts with status 'initial_draft_uploaded'
        $unassigned = Manuscript::with('author')
            ->where('status', 'initial_draft_uploaded')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar naskah belum diplot berhasil diambil.',
            'data' => ManuscriptResource::collection($unassigned),
            'links' => [
                [
                    'rel' => 'self',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts/unassigned')
                ],
                [
                    'rel' => 'all_manuscripts',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts')
                ],
                [
                    'rel' => 'get_reviewers',
                    'method' => 'GET',
                    'href' => url('/api/admin/reviewers')
                ]
            ]
        ]);
    }

    /**
     * Get All Manuscripts
     */
    public function index(): JsonResponse
    {
        $manuscripts = Manuscript::with(['author', 'reviewSubmissions.reviewer'])->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar semua naskah berhasil diambil.',
            'data' => ManuscriptResource::collection($manuscripts),
            'links' => [
                [
                    'rel' => 'self',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts')
                ],
                [
                    'rel' => 'unassigned_manuscripts',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts/unassigned')
                ],
                [
                    'rel' => 'get_reviewers',
                    'method' => 'GET',
                    'href' => url('/api/admin/reviewers')
                ]
            ]
        ]);
    }

    /**
     * Get All Reviewers
     */
    public function getReviewers(): JsonResponse
    {
        $reviewers = \App\Models\User::where('role_id', 2)->get();

        $data = $reviewers->map(function ($u) {
            $activeCount = ReviewSubmission::where('reviewer_id', $u->id)
                ->whereIn('status', ['pending', 'under_review'])
                ->count();

            return [
                'id' => $u->id,
                'name' => $u->name,
                'dept' => 'Sistem Informasi',
                'aktif' => $activeCount
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar reviewer berhasil diambil.',
            'data' => $data,
            'links' => [
                [
                    'rel' => 'self',
                    'method' => 'GET',
                    'href' => url('/api/admin/reviewers')
                ],
                [
                    'rel' => 'all_manuscripts',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts')
                ],
                [
                    'rel' => 'unassigned_manuscripts',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts/unassigned')
                ]
            ]
        ]);
    }

    /**
     * Remove Reviewer from Manuscript
     */
    public function removeReviewer(int $manuscriptId, int $reviewerId): JsonResponse
    {
        $deleted = ReviewSubmission::where('manuscript_id', $manuscriptId)
            ->where('reviewer_id', $reviewerId)
            ->delete();

        if ($deleted) {
            $remaining = ReviewSubmission::where('manuscript_id', $manuscriptId)->count();
            if ($remaining === 0) {
                $manuscript = Manuscript::findOrFail($manuscriptId);
                $manuscript->update(['status' => 'initial_draft_uploaded']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Reviewer berhasil dihapus dari naskah.',
                'data' => null,
                'links' => [
                    [
                        'rel' => 'assign_reviewer',
                        'method' => 'POST',
                        'href' => url("/api/admin/manuscripts/{$manuscriptId}/assign-reviewer")
                    ],
                    [
                        'rel' => 'all_manuscripts',
                        'method' => 'GET',
                        'href' => url('/api/admin/manuscripts')
                    ]
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Penugasan reviewer tidak ditemukan.'
        ], 404);
    }

    /**
     * Assign Reviewer to Manuscript
     */
    public function assignReviewer(AssignReviewerRequest $request, int $manuscriptId): JsonResponse
    {
        $manuscript = Manuscript::findOrFail($manuscriptId);

        $validated = $request->validated();

        // Prevent duplicate assignment
        $alreadyAssigned = ReviewSubmission::where('manuscript_id', $manuscript->id)
            ->where('reviewer_id', $validated['reviewer_id'])
            ->exists();

        if ($alreadyAssigned) {
            return response()->json([
                'success' => false,
                'message' => 'Reviewer ini sudah ditugaskan pada naskah yang sama.',
            ], 409);
        }

        // Create review submission assignment
        ReviewSubmission::create([
            'reviewer_id' => $validated['reviewer_id'],
            'manuscript_id' => $manuscript->id,
            'status' => 'pending',
            'deadline' => $validated['deadline'],
        ]);

        // Update manuscript status to reviewer_assigned
        $manuscript->update([
            'status' => 'reviewer_assigned'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Reviewer berhasil ditugaskan.',
            'data' => null,
            'links' => [
                [
                    'rel' => 'remove_reviewer',
                    'method' => 'DELETE',
                    'href' => url("/api/admin/manuscripts/{$manuscriptId}/remove-reviewer/{$validated['reviewer_id']}")
                ],
                [
                    'rel' => 'all_manuscripts',
                    'method' => 'GET',
                    'href' => url('/api/admin/manuscripts')
                ]
            ]
        ], 200);
    }
}
