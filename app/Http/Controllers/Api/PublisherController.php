<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\PublisherCheck;
use App\Models\PublisherDecision;
use App\Events\DecisionMade;

class PublisherController extends Controller
{
    /**
     * GET /publisher/dashboard
     */
    public function dashboard()
    {
        // Hitung jumlah naskah pra-cetak (status 'preprint' di tabel manuscripts)
        $preprintCount = DB::table('manuscripts')->where('status', 'preprint')->count();

        // Hitung approved dan revised bulan ini dari publisher_decisions
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $approvedCount = PublisherDecision::where('decision', 'approved')
            ->whereYear('decided_at', $currentYear)
            ->whereMonth('decided_at', $currentMonth)
            ->count();

        $revisedCount = PublisherDecision::where('decision', 'revised')
            ->whereYear('decided_at', $currentYear)
            ->whereMonth('decided_at', $currentMonth)
            ->count();

        // Ambil 5 naskah pra-cetak terbaru beserta author
        $latestPreprints = DB::table('manuscripts')
            ->join('users', 'manuscripts.author_id', '=', 'users.id')
            ->where('manuscripts.status', 'preprint')
            ->orderBy('manuscripts.created_at', 'desc')
            ->limit(5)
            ->get(['manuscripts.id', 'manuscripts.title', 'users.name as author', 'manuscripts.created_at as submitted_at']);

        $latest = $latestPreprints->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'author' => $item->author,
                'submitted_at' => $item->submitted_at,
                '_links' => [
                    'check_detail' => [
                        'href' => "/api/publisher/check/{$item->id}",
                        'method' => 'GET'
                    ]
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'preprint_count' => $preprintCount,
                'approved_count_this_month' => $approvedCount,
                'revised_count_this_month' => $revisedCount,
                'latest_preprints' => $latest
            ],
            '_links' => [
                'preprint_list' => [
                    'href' => '/api/publisher/preprint-list',
                    'method' => 'GET'
                ]
            ]
        ]);
    }

    /**
     * GET /publisher/preprint-list
     */
    public function preprintList(Request $request)
    {
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 10);
        $kelengkapan = $request->input('kelengkapan'); // pending, partial, complete

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

        // Filter berdasarkan status kelengkapan
        if ($kelengkapan === 'pending') {
            $query->whereNull('publisher_checks.cover_ok');
        } elseif ($kelengkapan === 'complete') {
            $query->where('publisher_checks.cover_ok', true)
                  ->where('publisher_checks.page_count_ok', true)
                  ->where('publisher_checks.admin_docs_ok', true);
        } elseif ($kelengkapan === 'partial') {
            $query->where(function ($q) {
                $q->where('publisher_checks.cover_ok', false)
                  ->orWhere('publisher_checks.page_count_ok', false)
                  ->orWhere('publisher_checks.admin_docs_ok', false);
            });
        }

        $paginator = $query->paginate($limit, ['*'], 'page', $page);

        $items = collect($paginator->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title,
                'author_name' => $item->author_name,
                'submitted_at' => $item->submitted_at,
                'cover_checked' => (bool) $item->cover_ok,
                'pages_checked' => (bool) $item->page_count_ok,
                'admin_checked' => (bool) $item->admin_docs_ok,
                '_links' => [
                    'check_detail' => [
                        'href' => "/api/publisher/check/{$item->id}",
                        'method' => 'GET'
                    ]
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'page' => $paginator->currentPage(),
                    'limit' => $paginator->perPage(),
                    'total' => $paginator->total(),
                    'total_pages' => $paginator->lastPage()
                ]
            ],
            '_links' => [
                'dashboard' => [
                    'href' => '/api/publisher/dashboard',
                    'method' => 'GET'
                ]
            ]
        ]);
    }

    /**
     * GET /publisher/check/{manuscriptId}
     */
    public function getCheck($manuscriptId)
    {
        // Data manuscript (dari kelompok 2)
        $manuscript = DB::table('manuscripts')
            ->join('users', 'manuscripts.author_id', '=', 'users.id')
            ->where('manuscripts.id', $manuscriptId)
            ->select('manuscripts.id', 'manuscripts.title', 'users.name as author_name', 'manuscripts.total_pages as page_count')
            ->first();

        if (!$manuscript) {
            return response()->json(['success' => false, 'message' => 'Manuscript not found'], 404);
        }

        // Cover file URL (dari manuscript_files)
        $coverFile = DB::table('manuscript_files')
            ->where('manuscript_id', $manuscriptId)
            ->where('file_type', 'initial')
            ->first();
        $coverFileUrl = $coverFile ? asset('storage/' . $coverFile->file_path) : null;

        // Admin documents
        $adminDocs = DB::table('author_documents')
            ->where('manuscript_id', $manuscriptId)
            ->get(['id', 'document_type', 'file_path']);
        $adminDocsArray = $adminDocs->map(function ($doc) {
            return [
                'id' => $doc->id,
                'type' => $doc->document_type,
                'url' => asset('storage/' . $doc->file_path)
            ];
        });

        // Data pemeriksaan sebelumnya
        $check = PublisherCheck::where('manuscript_id', $manuscriptId)->first();
        $checkResult = $check ? [
            'cover_ok' => (bool) $check->cover_ok,
            'pages_ok' => (bool) $check->page_count_ok,
            'admin_ok' => (bool) $check->admin_docs_ok,
            'notes' => $check->notes
        ] : null;

        return response()->json([
            'success' => true,
            'data' => [
                'manuscript' => [
                    'id' => $manuscript->id,
                    'title' => $manuscript->title,
                    'author_name' => $manuscript->author_name,
                    'cover_file_url' => $coverFileUrl,
                    'page_count' => $manuscript->page_count,
                    'admin_documents' => $adminDocsArray
                ],
                'check_result' => $checkResult
            ],
            '_links' => [
                'save_check' => [
                    'href' => "/api/publisher/check/{$manuscriptId}",
                    'method' => 'POST'
                ],
                'make_decision' => [
                    'href' => '/api/publisher/decision',
                    'method' => 'POST'
                ]
            ]
        ]);
    }

    /**
     * POST /publisher/check/{manuscriptId}
     */
    public function storeCheck(Request $request, $manuscriptId)
    {
        $request->validate([
            'cover_ok' => 'required|boolean',
            'pages_ok' => 'required|boolean',
            'admin_ok' => 'required|boolean',
            'notes' => 'nullable|string'
        ]);

        $publisherId = Auth::id();

        // Upsert: update jika sudah ada, buat baru jika belum
        $check = PublisherCheck::updateOrCreate(
            ['manuscript_id' => $manuscriptId],
            [
                'publisher_id' => $publisherId,
                'cover_ok' => $request->cover_ok,
                'page_count_ok' => $request->pages_ok,
                'admin_docs_ok' => $request->admin_ok,
                'notes' => $request->notes,
                'checked_at' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Pemeriksaan berhasil disimpan',
            '_links' => [
                'check_detail' => [
                    'href' => "/api/publisher/check/{$manuscriptId}",
                    'method' => 'GET'
                ],
                'make_decision' => [
                    'href' => '/api/publisher/decision',
                    'method' => 'POST'
                ]
            ]
        ]);
    }

    /**
     * POST /publisher/decision
     */
    public function decision(Request $request)
    {
        $request->validate([
            'check_id' => 'required|integer|exists:publisher_checks,id',
            'decision' => 'required|in:approved,revised',
            'revision_notes' => 'required_if:decision,revised|nullable|string'
        ]);

        $publisherId = Auth::id();

        $decision = PublisherDecision::create([
            'check_id' => $request->check_id,
            'publisher_id' => $publisherId,
            'decision' => $request->decision,
            'revision_notes' => $request->revision_notes,
            'decided_at' => now()
        ]);

        // Trigger event untuk integrasi kelompok 2 dan notifikasi email
        event(new DecisionMade($decision));

        return response()->json([
            'success' => true,
            'message' => 'Keputusan berhasil disimpan',
            'data' => [
                'decision_id' => $decision->id,
                'status' => $decision->decision
            ],
            '_links' => [
                'dashboard' => [
                    'href' => '/api/publisher/dashboard',
                    'method' => 'GET'
                ],
                'preprint_list' => [
                    'href' => '/api/publisher/preprint-list',
                    'method' => 'GET'
                ]
            ]
        ]);
    }
}