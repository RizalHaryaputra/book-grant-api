<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\DecisionMade;
use App\Models\NotificationLog;
use App\Services\EmailService;
use App\Models\Manuscript;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

/**
 * SendDecisionNotification Listener
 *
 * Handles the DecisionMade event fired by PublisherController.
 * Sends a publisher decision email to the manuscript author and
 * persists the result in notification_log.
 *
 * Previously used an anti-pattern of injecting NotificationController
 * into a Listener. Refactored to use EmailService directly.
 */
class SendDecisionNotification implements ShouldQueue
{
    public function __construct(
        private readonly EmailService $emailService
    ) {}

    /**
     * Handle the DecisionMade event.
     */
    public function handle(DecisionMade $event): void
    {
        $publisherCheck = $event->publisherCheck;
        $revisionNotes  = $event->revisionNotes;

        // ---------------------------------------------------------------
        // Step 1: Resolve the manuscript and its author
        // ---------------------------------------------------------------
        $manuscript = Manuscript::find($publisherCheck->manuscript_id);
        if (!$manuscript) {
            Log::warning('[SendDecisionNotification] Manuscript not found.', [
                'manuscript_id' => $publisherCheck->manuscript_id,
            ]);
            return;
        }

        $author = User::find($manuscript->author_id);
        if (!$author) {
            Log::warning('[SendDecisionNotification] Author not found.', [
                'author_id' => $manuscript->author_id,
            ]);
            return;
        }

        // ---------------------------------------------------------------
        // Step 2: Build subject and body based on decision
        // ---------------------------------------------------------------
        $isApproved = $publisherCheck->decision === 'approved';
        $eventType  = $isApproved ? 'publisher_approved' : 'publisher_revised';

        $subject = $isApproved
            ? 'Selamat! Naskah Anda Telah Disetujui'
            : 'Naskah Anda Memerlukan Revisi';

        $bodyText = $isApproved
            ? "Halo {$author->name},\n\nNaskah Anda berjudul \"{$manuscript->title}\" telah disetujui oleh penerbit dan siap untuk proses cetak.\n\nTerima kasih,\nTim Book Grant"
            : "Halo {$author->name},\n\nNaskah Anda berjudul \"{$manuscript->title}\" memerlukan revisi.\n\nCatatan Penerbit: " . ($revisionNotes ?? 'Tidak ada catatan.') . "\n\nTerima kasih,\nTim Book Grant";

        // ---------------------------------------------------------------
        // Step 3: Send the email via EmailService
        // ---------------------------------------------------------------
        $emailResult = $this->emailService->send($author->email, $subject, $bodyText);

        // ---------------------------------------------------------------
        // Step 4: Persist to notification_log
        // ---------------------------------------------------------------
        try {
            NotificationLog::create([
                'recipient_id'  => $author->id,
                'manuscript_id' => $manuscript->id,
                'rs_id'         => null,
                'event_type'    => $eventType,
                'email_to'      => $author->email,
                'subject'       => $subject,
                'body_html'     => nl2br(e($bodyText)),
                'status'        => $emailResult['success'] ? 'sent' : 'failed',
                'sent_at'       => $emailResult['success'] ? now() : null,
                'error_message' => $emailResult['error'],
            ]);

            Log::info('[SendDecisionNotification] Notification processed.', [
                'recipient_id' => $author->id,
                'event_type'   => $eventType,
                'email_sent'   => $emailResult['success'],
            ]);
        } catch (\Throwable $e) {
            Log::error('[SendDecisionNotification] Failed to persist notification log.', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle a permanent queue job failure.
     */
    public function failed(DecisionMade $event, \Throwable $exception): void
    {
        Log::critical('[SendDecisionNotification] Queue job permanently failed.', [
            'manuscript_id' => $event->publisherCheck->manuscript_id ?? null,
            'error'         => $exception->getMessage(),
        ]);
    }
}