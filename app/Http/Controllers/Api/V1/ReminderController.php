<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Deadline;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * ReminderController
 *
 * Triggers deadline reminder emails for all active deadlines
 * due within the next 3 days. Intended to be called by a
 * scheduled job (e.g., daily cron via Laravel Scheduler).
 *
 * Every sent reminder is persisted to notification_log for
 * full auditability.
 */
class ReminderController extends Controller
{
    public function __construct(
        private readonly EmailService $emailService
    ) {}

    /**
     * POST /api/v1/reminder/trigger
     *
     * Scan for approaching deadlines and send reminder emails.
     * Returns a summary of sent/failed counts.
     */
    public function trigger(): JsonResponse
    {
        $today       = Carbon::today();
        $lookAhead   = $today->copy()->addDays(3)->toDateString();

        // Fetch all active deadlines due within 3 days with assignees
        $deadlines = Deadline::with('assignee:id,name,email')
            ->whereBetween('due_date', [$today->toDateString(), $lookAhead])
            ->where('status', 'active')
            ->get();

        $sentCount   = 0;
        $failedCount = 0;
        $skippedCount = 0;

        foreach ($deadlines as $deadline) {
            $assignee = $deadline->assignee;

            if (!$assignee) {
                Log::warning('[ReminderController] Assignee not found for deadline.', [
                    'deadline_id'  => $deadline->id,
                    'assignee_id'  => $deadline->assignee_id,
                ]);
                $skippedCount++;
                continue;
            }

            $dueDate     = Carbon::parse($deadline->due_date);
            $daysLeft    = (int) $today->diffInDays($dueDate);
            $dueDateFmt  = $dueDate->translatedFormat('d F Y');

            $subject = "⏰ Reminder: Deadline {$deadline->deadline_type} Akan Berakhir dalam {$daysLeft} Hari";
            $body    = "Halo {$assignee->name},\n\n"
                     . "Ini adalah pengingat bahwa deadline Anda untuk tugas *{$deadline->deadline_type}* "
                     . "pada Naskah ID #{$deadline->manuscript_id} akan berakhir pada {$dueDateFmt}.\n\n"
                     . "Harap segera selesaikan sebelum tenggat waktu.\n\n"
                     . "Salam,\nTim Book Grant";

            $emailResult = $this->emailService->send($assignee->email, $subject, $body);

            // Persist to notification_log for auditability
            try {
                NotificationLog::create([
                    'recipient_id'  => $assignee->id,
                    'manuscript_id' => $deadline->manuscript_id,
                    'rs_id'         => null,
                    'event_type'    => 'deadline_reminder',
                    'email_to'      => $assignee->email,
                    'subject'       => $subject,
                    'body_html'     => nl2br(e($body)),
                    'status'        => $emailResult['success'] ? 'sent' : 'failed',
                    'sent_at'       => $emailResult['success'] ? now() : null,
                    'error_message' => $emailResult['error'],
                ]);
            } catch (\Throwable $e) {
                Log::error('[ReminderController] Failed to write notification log.', [
                    'deadline_id' => $deadline->id,
                    'error'       => $e->getMessage(),
                ]);
            }

            if ($emailResult['success']) {
                $sentCount++;
            } else {
                $failedCount++;
                Log::error('[ReminderController] Failed to send reminder.', [
                    'deadline_id' => $deadline->id,
                    'email'       => $assignee->email,
                    'error'       => $emailResult['error'],
                ]);
            }
        }

        return response()->json([
            'status'  => 'success',
            'data'    => [
                'total_deadlines_found' => $deadlines->count(),
                'reminders_sent'        => $sentCount,
                'reminders_failed'      => $failedCount,
                'reminders_skipped'     => $skippedCount,
            ],
            '_links'  => [
                'monitoring_deadlines' => ['href' => '/api/v1/monitoring/deadlines', 'method' => 'GET'],
                'notification_logs'    => ['href' => '/api/v1/monitoring/logs', 'method' => 'GET'],
            ],
        ]);
    }
}
