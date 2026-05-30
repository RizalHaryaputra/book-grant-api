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
        $publisherDecision = $event->publisherDecision;

        // Ambil informasi naskah dan penulis
        $publisherCheck = $publisherDecision->check;
        $manuscript = Manuscript::find($publisherCheck->manuscript_id);
        $author = User::find($manuscript->author_id);

        // Tentukan subject dan body email
        $subject = $publisherDecision->decision === 'approved' ? 'Selamat! Naskah Anda Disetujui' : 'Naskah Anda Memerlukan Revisi';
        $body = $publisherDecision->decision === 'approved' 
            ? "Naskah Anda telah disetujui oleh penerbit dan siap untuk proses cetak." 
            : "Naskah Anda memerlukan revisi. Catatan: {$publisherDecision->revision_notes}";

        // Siapkan request palsu untuk controller
        $request = request()->merge([
            'to' => $author->email,
            'subject' => $subject,
            'body' => $body,
            'type' => $publisherDecision->decision === 'approved' ? 'publisher_approved' : 'publisher_revised',
        ]);

        // Panggil controller notifikasi
        $this->notificationController->send($request);
    }
}