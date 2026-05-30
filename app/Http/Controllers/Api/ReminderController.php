<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deadline;
use App\Models\User;
use App\Services\EmailService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReminderController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * POST /reminder/trigger
     */
    public function trigger(Request $request)
    {
        $today = Carbon::today();
        $targetDates = [
            $today->copy()->addDays(3)->toDateString(),
            $today->copy()->addDays(2)->toDateString(),
            $today->copy()->addDays(1)->toDateString(),
            $today->toDateString(),
        ];

        // Ambil deadline aktif yang due_date-nya mendekat
        $deadlines = Deadline::whereIn('due_date', $targetDates)
                            ->where('status', 'active')
                            ->get();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($deadlines as $deadline) {
            // (Opsional) cegah pengiriman berulang dalam satu hari – bisa simpan di cache atau log notifikasi
            // Untuk sederhana, lewati dulu.

            $assignee = User::find($deadline->assignee_id);
            if (!$assignee) {
                Log::warning("User ID {$deadline->assignee_id} tidak ditemukan untuk deadline {$deadline->id}");
                continue;
            }

            $due = Carbon::parse($deadline->due_date);
            $daysBefore = $today->diffInDays($due);

            $subject = "Reminder: Deadline {$deadline->deadline_type} akan tiba";
            $body = "Halo {$assignee->name}, deadline untuk tugas {$deadline->deadline_type} pada naskah ID {$deadline->manuscript_id} akan berakhir pada {$deadline->due_date}. Harap segera diselesaikan.";

            $emailResult = $this->emailService->send($assignee->email, $subject, $body);

            // Jika ingin tetap mencatat log, gunakan NotificationLog (opsional)
            // NotificationLog::create([...]);

            if ($emailResult['success']) {
                $sentCount++;
            } else {
                $failedCount++;
                Log::error("Gagal mengirim reminder untuk deadline {$deadline->id}: {$emailResult['error']}");
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'reminders_sent' => $sentCount,
                'reminders_failed' => $failedCount
            ],
            '_links' => [
                'dashboard' => [
                    'href' => '/api/admin/dashboard-stats',
                    'method' => 'GET'
                ]
            ]
        ]);
    }
}