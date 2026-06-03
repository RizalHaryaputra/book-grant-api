<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendNotificationRequest;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\JsonResponse;

/**
 * NotificationController
 *
 * Provides manual notification dispatch and log retrieval.
 *
 * Most notifications are handled automatically via the event/listener
 * pipeline (AccountCreated, ContractValidated, DecisionMade). This
 * controller exposes a manual-send endpoint for ad-hoc use cases
 * (e.g., admin sending a custom message).
 */
class NotificationController extends Controller
{
    public function __construct(
        private readonly EmailService $emailService
    ) {}

    /**
     * POST /api/v1/notification/send
     *
     * Manually send a notification email and log the result.
     *
     * Body: { "to": email, "subject": string, "body": string, "type": event_type_enum }
     */
    public function send(SendNotificationRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Send via SMTP
        $emailResult = $this->emailService->send($data['to'], $data['subject'], $data['body']);

        // Resolve recipient user ID from email (nullable — external recipients may not have accounts)
        $recipient = User::where('email', $data['to'])->first();

        // Persist the log entry
        NotificationLog::create([
            'recipient_id'  => $recipient?->id,
            'manuscript_id' => null,
            'rs_id'         => null,
            'event_type'    => $data['type'],
            'email_to'      => $data['to'],
            'subject'       => $data['subject'],
            'body_html'     => $data['body'],
            'status'        => $emailResult['success'] ? 'sent' : 'failed',
            'sent_at'       => $emailResult['success'] ? now() : null,
            'error_message' => $emailResult['error'],
        ]);

        return response()->json([
            'status'  => $emailResult['success'] ? 'success' : 'error',
            'message' => $emailResult['success'] ? 'Email berhasil dikirim.' : 'Email gagal dikirim.',
            '_links'  => [
                'notification_logs' => ['href' => '/api/v1/monitoring/logs', 'method' => 'GET'],
            ],
        ], $emailResult['success'] ? 200 : 500);
    }
}
