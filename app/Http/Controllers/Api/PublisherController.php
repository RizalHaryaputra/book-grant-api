<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\PublisherCheck;
use App\Models\PublisherDecision;

class PublisherController extends Controller
{
    // Ambil daftar preprint
    public function preprintList()
    {
        return response()->json([
            'success' => true,
            'message' => 'Preprint list berhasil diambil'
        ]);
    }

    // Simpan hasil pengecekan publisher
    public function checkStore(Request $request)
    {
        $check = PublisherCheck::create([
            'manuscript_id' => $request->manuscript_id,
            'publisher_id' => $request->publisher_id,
            'cover_ok' => $request->cover_ok,
            'page_count_ok' => $request->page_count_ok,
            'admin_docs_ok' => $request->admin_docs_ok,
            'notes' => $request->notes,
            'checked_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Publisher check berhasil disimpan',
            'data' => $check
        ]);
    }

    // Simpan keputusan publisher
    public function decisionStore(Request $request)
    {
        $decision = PublisherDecision::create([
            'check_id' => $request->check_id,
            'publisher_id' => $request->publisher_id,
            'decision' => $request->decision,
            'revision_notes' => $request->revision_notes,
            'decided_at' => now()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Decision berhasil disimpan',
            'data' => $decision
        ]);
    }
}