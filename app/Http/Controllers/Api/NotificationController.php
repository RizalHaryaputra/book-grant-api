<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendNotificationRequest;
use App\Models\NotificationLog;
use App\Models\User;
use App\Services\EmailService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    protected $emailService;

    public function __construct(EmailService $emailService)
    {
        $this->emailService = $emailService;
    }

    /**
     * POST /notification/send
     */
    public function send(SendNotificationRequest $request)
    {
        $data = $request->validated();

        $emailResult = $this->emailService->send($data['to'], $data['subject'], $data['body']);
        $recipient = User::where('email', $data['to'])->first();

        // Simpan langsung ke notification_log tanpa template
        NotificationLog::create([
            'recipient_id'   => $recipient ? $recipient->id : null,
            'manuscript_id'  => null, // bisa diisi jika ada konteks manuscript
            'rs_id'          => null, // bisa diisi jika ada konteks review submission
            'event_type'     => $data['type'],
            'email_to'       => $data['to'],
            'subject'        => $data['subject'],
            'body_html'      => $data['body'],
            'status'         => $emailResult['success'] ? 'sent' : 'failed',
            'sent_at'        => $emailResult['success'] ? now() : null,
            'error_message'  => $emailResult['error'],
        ]);

        $response = [
            'success' => $emailResult['success'],
            'message' => $emailResult['success'] ? 'Email berhasil dikirim' : 'Email gagal dikirim',
            '_links' => [
                'notification_logs' => [
                    'href' => '/api/admin/notification-logs',
                    'method' => 'GET'
                ]
            ]
        ];
        return response()->json($response, $emailResult['success'] ? 200 : 500);
    }

    /**
     * GET /admin/notification-logs
     */
    public function logs(Request $request)
    {
        $query = NotificationLog::query();

        if ($request->has('type')) {
            $query->where('event_type', $request->type);
        }
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $limit = $request->input('limit', 20);
        $page = $request->input('page', 1);
        $logs = $query->orderBy('created_at', 'desc')->paginate($limit, ['*'], 'page', $page);

        $items = $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'recipient' => $log->email_to,
                'type' => $log->event_type,
                'subject' => $log->subject,
                'status' => $log->status,
                'sent_at' => $log->sent_at,
                'created_at' => $log->created_at,
                '_links' => [
                    'self' => [
                        'href' => "/api/admin/notification-logs/{$log->id}",
                        'method' => 'GET'
                    ]
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'items' => $items,
                'pagination' => [
                    'page' => $logs->currentPage(),
                    'limit' => $logs->perPage(),
                    'total' => $logs->total(),
                    'total_pages' => $logs->lastPage()
                ]
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