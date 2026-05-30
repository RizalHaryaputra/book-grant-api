<?php

namespace App\Listeners;

use App\Events\DecisionMade;
use App\Http\Controllers\Api\NotificationController;
use App\Models\User;
use App\Models\Manuscript;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendDecisionNotification implements ShouldQueue
{
    protected $notificationController;

    public function __construct(NotificationController $notificationController)
    {
        $this->notificationController = $notificationController;
    }

    public function handle(DecisionMade $event): void
    {
        $publisherCheck = $event->publisherCheck;
        $revisionNotes = $event->revisionNotes;

        $manuscript = Manuscript::find($publisherCheck->manuscript_id);
        if (!$manuscript) return;

        $author = User::find($manuscript->author_id);
        if (!$author) return;

        $subject = $publisherCheck->decision === 'approved'
            ? 'Selamat! Naskah Anda Disetujui'
            : 'Naskah Anda Memerlukan Revisi';

        $body = $publisherCheck->decision === 'approved'
            ? "Naskah Anda telah disetujui oleh penerbit dan siap untuk proses cetak."
            : "Naskah Anda memerlukan revisi. Catatan: " . ($revisionNotes ?? 'Tidak ada catatan.');

        $request = request()->merge([
            'to' => $author->email,
            'subject' => $subject,
            'body' => $body,
            'type' => $publisherCheck->decision === 'approved' ? 'publisher_approved' : 'publisher_revised',
        ]);

        $this->notificationController->send($request);
    }
}