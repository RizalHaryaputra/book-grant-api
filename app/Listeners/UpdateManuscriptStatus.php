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
        $publisherCheck = $event->publisherCheck;
        $manuscriptId = $publisherCheck->manuscript_id;

        $status = $publisherCheck->decision === 'approved' ? 'ready_to_print' : 'publisher_revised';
        $notes = $event->revisionNotes;

        $success = $this->manuscriptService->updateManuscriptStatus($manuscriptId, $status, $notes);

        if (!$success) {
            Log::warning("Gagal mengupdate status manuskrip {$manuscriptId} dari Kelompok 3.");
        }
    }
}