<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Deadline;
use App\Models\ReminderLog;
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
     * Menjalankan reminder untuk deadline yang mendekat (H-3 s.d H)
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

        $deadlines = Deadline::whereIn('due_date', $targetDates)
                            ->where('status', 'active')
                            ->get();

        $sentCount = 0;
        $failedCount = 0;

        foreach ($deadlines as $deadline) {
            // Cek apakah reminder sudah dikirim hari ini untuk deadline ini
            $alreadySent = ReminderLog::where('deadline_id', $deadline->id)
                                      ->whereDate('sent_at', $today)
                                      ->exists();
            if ($alreadySent) {
                continue;
            }

            $assignee = User::find($deadline->assignee_id);
            if (!$assignee) {
                Log::warning("User dengan ID {$deadline->assignee_id} tidak ditemukan untuk deadline {$deadline->id}");
                continue;
            }

            $due = Carbon::parse($deadline->due_date);
            $daysBefore = $today->diffInDays($due);

            $subject = "Reminder: Deadline {$deadline->deadline_type} akan tiba";
            $body = "Halo {$assignee->name}, deadline untuk tugas {$deadline->deadline_type} pada naskah ID {$deadline->manuscript_id} akan berakhir pada {$deadline->due_date}. Harap segera diselesaikan.";

            $emailResult = $this->emailService->send($assignee->email, $subject, $body);

            // Simpan log reminder (pastikan model ReminderLog memiliki fillable 'created_at' dan $timestamps=false)
            ReminderLog::create([
                'deadline_id' => $deadline->id,
                'recipient_id' => $assignee->id,
                'days_before' => $daysBefore,
                'sent_at' => now(),
                'success' => $emailResult['success'],
                'error_message' => $emailResult['error'],
                'created_at' => now(), // Tambahkan karena model tidak menggunakan timestamps otomatis
            ]);

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