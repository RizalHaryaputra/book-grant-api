<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/**
 * AdminDashboardController
 *
 * Provides a high-level statistics summary for the admin dashboard.
 * Counts are role-aware and cover every stage of the manuscript lifecycle.
 */
class AdminDashboardController extends Controller
{
    /**
     * GET /api/v1/admin/dashboard/summary
     *
     * Returns aggregate counts across all modules for the admin overview panel.
     */
    public function summary(): JsonResponse
    {
        // Role-aware author count (joins roles table)
        $totalPenulis = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('roles.name', 'author')
            ->count();

        // Contract pipeline
        $totalKontrakValid = DB::table('contracts')->where('status', 'validated')->count();

        // Manuscript pipeline — each distinct status
        $totalDraftMasuk      = DB::table('manuscripts')->where('status', 'initial_draft_uploaded')->count();
        $totalReviewBerjalan  = DB::table('review_submissions')->where('status', 'under_review')->count();
        $totalRevisi          = DB::table('manuscripts')->where('status', 'revision_uploaded')->count();
        $totalPracetak        = DB::table('manuscripts')->where('status', 'preprint')->count();
        $totalSiapCetak       = DB::table('manuscripts')->where('status', 'ready_to_print')->count();

        return response()->json([
            'status' => 'success',
            'data'   => [
                'total_penulis'         => $totalPenulis,
                'total_kontrak_valid'   => $totalKontrakValid,
                'total_draft_masuk'     => $totalDraftMasuk,
                'total_review_berjalan' => $totalReviewBerjalan,
                'total_revisi'          => $totalRevisi,
                'total_pracetak'        => $totalPracetak,
                'total_siap_cetak'      => $totalSiapCetak,
            ],
            '_links' => [
                'notification_logs' => ['href' => '/api/v1/monitoring/logs', 'method' => 'GET'],
                'deadlines'         => ['href' => '/api/v1/monitoring/deadlines', 'method' => 'GET'],
            ],
        ]);
    }
}
