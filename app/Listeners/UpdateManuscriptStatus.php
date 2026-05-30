<?php

namespace App\Listeners;

use App\Events\DecisionMade;
use App\Services\ManuscriptIntegrationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class UpdateManuscriptStatus implements ShouldQueue
{
    protected $manuscriptService;

    public function __construct(ManuscriptIntegrationService $manuscriptService)
    {
        $this->manuscriptService = $manuscriptService;
    }

    public function handle(DecisionMade $event): void
    {
        $publisherDecision = $event->publisherDecision;

        // Ambil check_id dari publisher_checks
        $publisherCheck = $publisherDecision->check;
        $manuscriptId = $publisherCheck->manuscript_id;

        // Tentukan status baru berdasarkan keputusan
        $status = $publisherDecision->decision === 'approved' ? 'ready_to_print' : 'publisher_revised';
        $notes = $publisherDecision->decision === 'revised' ? $publisherDecision->revision_notes : null;

        $success = $this->manuscriptService->updateManuscriptStatus($manuscriptId, $status, $notes);

        if (!$success) {
            Log::warning("Gagal mengupdate status manuskrip {$manuscriptId} dari Kelompok 2.");
        }
    }
}