<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PublisherCheck;
use App\Events\DecisionMade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * PublisherController
 *
 * Handles the publisher (penerbit) workflow:
 *   - View the dashboard summary
 *   - Browse manuscripts in pre-print status
 *   - View and save a checklist for a specific manuscript
 *   - Record a final approval/revision decision
 *
 * All responses follow the API envelope:
 *   { "status": "success|error", "data": {...}, "_links": {...} }
 */
class PublisherController extends Controller
{
    /**
     * GET /api/v1/publisher/dashboard
     *
     * Returns a summary of pre-print activity and recent manuscripts.
     */
    public function dashboard(): JsonResponse
    {
        $preprintCount = DB::table('manuscripts')->where('status', 'preprint')->count();

        $currentMonth = now()->month;
        $currentYear  = now()->year;

        $approvedCount = PublisherCheck::where('decision', 'approved')
            ->whereYear('updated_at', $currentYear)
            ->whereMonth('updated_at', $currentMonth)
            ->count();

        $revisedCount = PublisherCheck::where('decision', 'revised')
            ->whereYear('updated_at', $currentYear)
            ->whereMonth('updated_at', $currentMonth)
            ->count();

        $latestPreprints = DB::table('manuscripts')
            ->join('users', 'manuscripts.author_id', '=', 'users.id')
            ->where('manuscripts.status', 'preprint')
            ->orderBy('manuscripts.created_at', 'desc')
            ->limit(5)
            ->get(['manuscripts.id', 'manuscripts.title', 'users.name as author', 'manuscripts.created_at as submitted_at'])
            ->map(fn ($item) => [
                'id'           => $item->id,
                'title'        => $item->title,
                'author'       => $item->author,
                'submitted_at' => $item->submitted_at,
                '_links'       => [
                    'check_detail' => ['href' => "/api/v1/publisher/manuscripts/{$item->id}", 'method' => 'GET'],
                ],
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'preprint_count'             => $preprintCount,
                'approved_count_this_month'  => $approvedCount,
                'revised_count_this_month'   => $revisedCount,
                'latest_preprints'           => $latestPreprints,
            ],
            '_links' => [
                'preprint_list' => ['href' => '/api/v1/publisher/manuscripts/pre-print', 'method' => 'GET'],
            ],
        ]);
    }

    /**
     * GET /api/v1/publisher/manuscripts/pre-print
     *
     * Paginated list of manuscripts in pre-print status.
     * Optional filter: ?kelengkapan=pending|complete|partial
     */
    public function prePrintManuscripts(Request $request): JsonResponse
    {
        $page        = (int) $request->input('page', 1);
        $limit       = (int) $request->input('limit', 10);
        $kelengkapan = $request->input('kelengkapan');

        $query = DB::table('manuscripts')
            ->join('users', 'manuscripts.author_id', '=', 'users.id')
            ->leftJoin('publisher_checks', 'manuscripts.id', '=', 'publisher_checks.manuscript_id')
            ->where('manuscripts.status', 'preprint')
            ->select(
                'manuscripts.id',
                'manuscripts.title',
                'users.name as author_name',
                'manuscripts.created_at as submitted_at',
                'publisher_checks.cover_ok',
                'publisher_checks.page_count_ok',
                'publisher_checks.admin_docs_ok'
            );

        if ($kelengkapan === 'pending') {
            $query->whereNull('publisher_checks.cover_ok');
        } elseif ($kelengkapan === 'complete') {
            $query->where('publisher_checks.cover_ok', true)
                  ->where('publisher_checks.page_count_ok', true)
                  ->where('publisher_checks.admin_docs_ok', true);
        } elseif ($kelengkapan === 'partial') {
            $query->where(fn ($q) =>
                $q->where('publisher_checks.cover_ok', false)
                  ->orWhere('publisher_checks.page_count_ok', false)
                  ->orWhere('publisher_checks.admin_docs_ok', false)
            );
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(fn ($item) => [
            'id'            => $item->id,
            'title'         => $item->title,
            'author_name'   => $item->author_name,
            'submitted_at'  => $item->submitted_at,
            'cover_checked' => (bool) $item->cover_ok,
            'pages_checked' => (bool) $item->page_count_ok,
            'admin_checked' => (bool) $item->admin_docs_ok,
            '_links'        => [
                'check_detail' => ['href' => "/api/v1/publisher/manuscripts/{$item->id}", 'method' => 'GET'],
            ],
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'items'      => $items,
                'pagination' => [
                    'page'        => $paginator->currentPage(),
                    'limit'       => $paginator->perPage(),
                    'total'       => $paginator->total(),
                    'total_pages' => $paginator->lastPage(),
                ],
            ],
            '_links' => [
                'dashboard' => ['href' => '/api/v1/publisher/dashboard', 'method' => 'GET'],
            ],
        ]);
    }

    /**
     * GET /api/v1/publisher/manuscripts/{id}
     *
     * Show a single manuscript with its files, admin documents,
     * and any previous check results.
     */
    public function showManuscript(int $id): JsonResponse
    {
        $manuscript = DB::table('manuscripts')
            ->join('users', 'manuscripts.author_id', '=', 'users.id')
            ->where('manuscripts.id', $id)
            ->select('manuscripts.id', 'manuscripts.title', 'manuscripts.total_pages', 'manuscripts.abstract', 'manuscripts.science_field', 'users.name as author_name')
            ->first();

        if (!$manuscript) {
            return response()->json(['status' => 'error', 'message' => 'Manuscript not found'], 404);
        }

        $coverFile = DB::table('manuscript_files')
            ->where('manuscript_id', $id)
            ->where('file_type', 'initial')
            ->first();

        $adminDocs = DB::table('author_documents')
            ->where('manuscript_id', $id)
            ->get(['id', 'document_type', 'file_path'])
            ->map(fn ($doc) => [
                'id'   => $doc->id,
                'type' => $doc->document_type,
                'url'  => asset('storage/' . $doc->file_path),
            ]);

        $check       = PublisherCheck::where('manuscript_id', $id)->first();
        $checkResult = $check ? [
            'cover_ok'  => $check->cover_ok,
            'pages_ok'  => $check->page_count_ok,
            'admin_ok'  => $check->admin_docs_ok,
            'notes'     => $check->notes,
            'decision'  => $check->decision,
        ] : null;

        return response()->json([
            'status' => 'success',
            'data'   => [
                'manuscript'   => [
                    'id'             => $manuscript->id,
                    'title'          => $manuscript->title,
                    'author_name'    => $manuscript->author_name,
                    'total_pages'    => $manuscript->total_pages,
                    'abstract'       => $manuscript->abstract,
                    'science_field'  => $manuscript->science_field,
                    'cover_file_url' => $coverFile ? asset('storage/' . $coverFile->file_path) : null,
                    'admin_documents'=> $adminDocs,
                ],
                'check_result' => $checkResult,
            ],
            '_links' => [
                'save_check'    => ['href' => "/api/v1/publisher/check/{$id}", 'method' => 'POST'],
                'make_decision' => ['href' => '/api/v1/publisher/decision', 'method' => 'POST'],
            ],
        ]);
    }

    /**
     * POST /api/v1/publisher/check/{manuscriptId}
     *
     * Save or update the publisher's checklist for a manuscript.
     */
    public function check(Request $request, int $manuscriptId): JsonResponse
    {
        $request->validate([
            'is_cover_valid'        => 'required|boolean',
            'is_page_count_valid'   => 'required|boolean',
            'is_admin_docs_complete'=> 'required|boolean',
            'check_notes'           => 'nullable|string|max:1000',
        ]);

        $publisherId = Auth::id() ?? 1; // Fallback for testing without auth middleware

        PublisherCheck::updateOrCreate(
            ['manuscript_id' => $manuscriptId],
            [
                'publisher_id'  => $publisherId,
                'cover_ok'      => $request->boolean('is_cover_valid'),
                'page_count_ok' => $request->boolean('is_page_count_valid'),
                'admin_docs_ok' => $request->boolean('is_admin_docs_complete'),
                'notes'         => $request->input('check_notes'),
                'checked_at'    => now(),
            ]
        );

        return response()->json([
            'status'  => 'success',
            'message' => 'Pemeriksaan berhasil disimpan.',
            '_links'  => [
                'check_detail'  => ['href' => "/api/v1/publisher/manuscripts/{$manuscriptId}", 'method' => 'GET'],
                'make_decision' => ['href' => '/api/v1/publisher/decision', 'method' => 'POST'],
            ],
        ]);
    }

    /**
     * POST /api/v1/publisher/decision
     *
     * Record the final approval or revision decision for a manuscript.
     * Updates the manuscript status and fires the DecisionMade event
     * to trigger the notification pipeline.
     *
     * Body: { "manuscript_id": int, "status": "approved|revised", "final_notes": string|null }
     */
    public function decision(Request $request): JsonResponse
    {
        $request->validate([
            'manuscript_id' => 'required|integer|exists:manuscripts,id',
            'status'        => 'required|in:approved,revised',
            'final_notes'   => 'nullable|string|max:1000',
        ]);

        $publisherId = Auth::id() ?? 1;

        // Locate the existing check record for this manuscript
        $check = PublisherCheck::where('manuscript_id', $request->manuscript_id)
            ->first();

        if (!$check) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Belum ada pemeriksaan untuk naskah ini. Lakukan pemeriksaan terlebih dahulu.',
            ], 404);
        }

        DB::beginTransaction();

        try {
            // 1. Persist the decision on the publisher_checks record
            $check->update([
                'decision'     => $request->status,
                'publisher_id' => $publisherId,
                'notes'        => $request->input('final_notes', $check->notes),
                'checked_at'   => now(),
            ]);

            // 2. Update the manuscript status accordingly
            $newStatus = $request->status === 'approved' ? 'ready_to_print' : 'publisher_revised';
            DB::table('manuscripts')
                ->where('id', $request->manuscript_id)
                ->update(['status' => $newStatus, 'updated_at' => now()]);

            DB::commit();

            // 3. Fire event → triggers SendDecisionNotification listener
            event(new DecisionMade($check, $request->input('final_notes')));

            return response()->json([
                'status'  => 'success',
                'message' => 'Keputusan berhasil disimpan.',
                'data'    => [
                    'manuscript_id'    => $request->manuscript_id,
                    'decision'         => $request->status,
                    'manuscript_status'=> $newStatus,
                ],
                '_links' => [
                    'dashboard'     => ['href' => '/api/v1/publisher/dashboard', 'method' => 'GET'],
                    'preprint_list' => ['href' => '/api/v1/publisher/manuscripts/pre-print', 'method' => 'GET'],
                ],
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal memproses keputusan.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
