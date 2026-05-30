<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function stats()
    {
        $totalPenulis = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('roles.name', 'penulis')
            ->count();

        $totalKontrakValid = DB::table('contracts')->where('status', 'validated')->count();
        $totalDraftMasuk = DB::table('manuscripts')->where('status', 'initial_draft_uploaded')->count();
        $totalReviewBerjalan = DB::table('review_submissions')->where('status', 'under_review')->count();
        $totalRevisi = DB::table('manuscripts')->where('status', 'revision_uploaded')->count();
        $totalPracetak = DB::table('manuscripts')->where('status', 'preprint')->count();
        $totalSiapCetak = DB::table('manuscripts')->where('status', 'ready_to_print')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_penulis' => $totalPenulis,
                'total_kontrak_valid' => $totalKontrakValid,
                'total_draft_masuk' => $totalDraftMasuk,
                'total_review_berjalan' => $totalReviewBerjalan,
                'total_revisi' => $totalRevisi,
                'total_pracetak' => $totalPracetak,
                'total_siap_cetak' => $totalSiapCetak
            ],
            '_links' => [
                'notification_logs' => [
                    'href' => '/api/admin/notification-logs',
                    'method' => 'GET'
                ]
            ]
        ]);
    }
}