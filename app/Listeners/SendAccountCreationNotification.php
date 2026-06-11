<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\AccountCreated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\AccountCreatedMail; // Pastikan file Mailable ini sudah ada di folder app/Mail!

/**
 * SendAccountCreationNotification Listener
 *
 * Handles the AccountCreated event fired by Modul 1 (Accounts & Contracts).
 * Responsibilities:
 *   1. Extract the account data payload from the event.
 *   2. Retrieve a compiled email template from NotificationService.
 *   3. Persist a row in `notification_log` with status 'sent'.
 *      (Actual SMTP delivery is mocked; wire up a Mailable here when ready.)
 *
 * Implements ShouldQueue so the listener runs asynchronously via Laravel
 * Queue, preventing the account-creation HTTP response from being delayed
 * by notification processing.
 */
class SendAccountCreationNotification implements ShouldQueue
{
    /**
     * The event type identifier used in the notification_log table.
     */
    private const EVENT_TYPE = 'account_created';

    /**
     * Create a new listener instance.
     *
     * @param  NotificationService  $notificationService
     */
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Handle the AccountCreated event.
     *
     * @param  AccountCreated  $event
     * @return void
     */
    public function handle(AccountCreated $event): void
    {
        $payload = $event->payload;

        // ------------------------------------------------------------------
        // Guard: Ensure all required keys are present in the payload.
        // ------------------------------------------------------------------
        $required = ['user_id', 'email', 'name'];
        foreach ($required as $key) {
            if (empty($payload[$key])) {
                Log::error('[SendAccountCreationNotification] Missing required payload key.', [
                    'missing_key' => $key,
                    'payload'     => $payload,
                ]);
                return;
            }
        }

        try {
            // --------------------------------------------------------------
            // Step 1: Build the compiled email template.
            // --------------------------------------------------------------
            ['subject' => $subject, 'body_html' => $bodyHtml] =
                $this->notificationService->getTemplate(self::EVENT_TYPE, $payload);

                        // --------------------------------------------------------------
            // Step 2: Persist the notification log entry. (KITA MATIKAN DULU)
            // --------------------------------------------------------------
            // DB::table('notification_log')->insert([
            //     'recipient_id'  => $payload['user_id'],
            //     'manuscript_id' => null, 
            //     'rs_id'         => null, 
            //     'event_type'    => self::EVENT_TYPE,
            //     'email_to'      => $payload['email'],
            //     'subject'       => $subject,
            //     'body_html'     => $bodyHtml,
            //     'status'        => 'sent',          
            //     'sent_at'       => now(),
            //     'error_message' => null,
            //     'created_at'    => now(),
            //     'updated_at'    => now(),
            // ]);

            // --------------------------------------------------------------
            // Step 3: Kirim Email Sungguhan ke Mailtrap!
            // --------------------------------------------------------------
            Log::info('[SendAccountCreationNotification] Mengirim email sungguhan ke Mailtrap...', [
                'email_to' => $payload['email']
            ]);

            // Hapus tanda // di bawah ini!
            Mail::to($payload['email'])->send(new AccountCreatedMail($subject, $bodyHtml));

        } catch (\InvalidArgumentException $e) {
            Log::error('[SendAccountCreationNotification] Template not found.', [
                'event_type' => self::EVENT_TYPE,
                'message'    => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            Log::error('[SendAccountCreationNotification] Failed to process notification.', [
                'recipient_id' => $payload['user_id'] ?? null,
                'error'        => $e->getMessage(),
                'trace'        => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Handle a job failure.
     *
     * Called automatically by Laravel Queue when the job fails after all retries.
     *
     * @param  AccountCreated  $event
     * @param  \Throwable      $exception
     * @return void
     */
    public function failed(AccountCreated $event, \Throwable $exception): void
    {
        Log::critical('[SendAccountCreationNotification] Queue job permanently failed.', [
            'payload' => $event->payload,
            'error'   => $exception->getMessage(),
        ]);
    }
}
