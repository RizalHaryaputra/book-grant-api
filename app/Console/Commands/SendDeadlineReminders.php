<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Deadline;
use App\Models\NotificationTemplate;
use App\Models\ReminderLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendDeadlineReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send-deadline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send deadline reminders to users for active deadlines approaching within 3 days.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::now()->startOfDay();
        $threeDaysFromNow = Carbon::now()->addDays(3)->endOfDay();

        // Query active deadlines within 3 days
        $deadlines = Deadline::with(['assignee', 'manuscript'])
            ->where('status', 'active') // is_completed is false equivalent based on schema
            ->whereBetween('due_date', [$today->toDateString(), $threeDaysFromNow->toDateString()])
            ->get();

        if ($deadlines->isEmpty()) {
            $this->info('No approaching deadlines found.');
            return;
        }

        $template = NotificationTemplate::where('event_type', 'deadline_reminder')->first();

        if (!$template) {
            $this->error('Deadline reminder template not found in notification_templates table.');
            return;
        }

        foreach ($deadlines as $deadline) {
            $recipient = $deadline->assignee;
            $manuscript = $deadline->manuscript;

            if (!$recipient || !$manuscript) {
                continue;
            }

            // Anti-Spam: Check if reminder already sent today for this deadline
            $alreadySentToday = ReminderLog::where('deadline_id', $deadline->id)
                ->whereDate('sent_at', Carbon::today())
                ->where('success', true)
                ->exists();

            if ($alreadySentToday) {
                continue; // Skip, already reminded today
            }

            // Template parsing setup
            $data = [
                'nama' => $recipient->name ?? 'Pengguna',
                'judul_buku' => $manuscript->title ?? 'Naskah',
                'deadline_type' => $deadline->deadline_type,
                'due_date' => Carbon::parse($deadline->due_date)->format('d F Y'),
            ];

            $parsedSubject = $this->parseTemplate($template->subject, $data);
            $parsedBody = $this->parseTemplate($template->body_html, $data);

            // Mock sending email
            Log::info("Sending Reminder Email to: {$recipient->email}");
            Log::info("Subject: {$parsedSubject}");
            Log::info("Body: \n{$parsedBody}");

            // Logging success records
            DB::beginTransaction();

            try {
                // Insert into reminder_logs
                $daysBefore = Carbon::today()->diffInDays(Carbon::parse($deadline->due_date), false);
                
                ReminderLog::create([
                    'deadline_id' => $deadline->id,
                    'recipient_id' => $recipient->id,
                    'days_before' => $daysBefore >= 0 ? $daysBefore : 0,
                    'success' => true,
                    'sent_at' => now(),
                    'error_message' => null
                ]);

                // Insert into notification_log
                DB::table('notification_log')->insert([
                    'template_id' => $template->id,
                    'recipient_id' => $recipient->id,
                    'manuscript_id' => $manuscript->id,
                    'email_to' => $recipient->email ?? 'unknown@example.com',
                    'subject' => $parsedSubject,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'created_at' => now()
                ]);

                DB::commit();
                $this->info("Reminder sent and logged for Deadline ID: {$deadline->id}");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to log reminder for Deadline ID: {$deadline->id}. Error: " . $e->getMessage());
                $this->error("Failed to log reminder for Deadline ID: {$deadline->id}");
            }
        }

        $this->info('Deadline reminders processing completed.');
    }

    /**
     * Helper function to replace Mustache style variables {{var}} with actual data.
     *
     * @param string $content
     * @param array $data
     * @return string
     */
    private function parseTemplate($content, $data)
    {
        foreach ($data as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        return $content;
    }
}
