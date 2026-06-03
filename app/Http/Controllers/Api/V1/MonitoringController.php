<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Deadline;
use App\Models\NotificationLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

/**
 * MonitoringController
 *
 * Provides read-only monitoring endpoints for admin dashboards:
 *   - Upcoming deadlines (within 3 days)
 *   - Notification log history
 */
class MonitoringController extends Controller
{
    /**
     * GET /api/v1/monitoring/deadlines
     *
     * Returns all active deadlines due within the next 3 days,
     * with the assignee relationship eager-loaded.
     */
    public function deadlines(): JsonResponse
    {
        $deadlines = Deadline::with(['assignee:id,name,email', 'manuscript:id,title'])
            ->where('due_date', '<=', Carbon::now()->addDays(3)->toDateString())
            ->where('status', 'active')
            ->orderBy('due_date', 'asc')
            ->get()
            ->map(fn ($d) => [
                'id'            => $d->id,
                'deadline_type' => $d->deadline_type,
                'due_date'      => $d->due_date,
                'days_before'   => $d->days_before,
                'assignee'      => $d->assignee ? [
                    'id'    => $d->assignee->id,
                    'name'  => $d->assignee->name,
                    'email' => $d->assignee->email,
                ] : null,
                'manuscript' => $d->manuscript ? [
                    'id'    => $d->manuscript->id,
                    'title' => $d->manuscript->title,
                ] : null,
                '_links' => [
                    'trigger_reminder' => ['href' => '/api/v1/reminder/trigger', 'method' => 'POST'],
                ],
            ]);

        return response()->json([
            'status' => 'success',
            'data'   => $deadlines,
            '_links' => [
                'notification_logs' => ['href' => '/api/v1/monitoring/logs', 'method' => 'GET'],
            ],
        ]);
    }

    /**
     * GET /api/v1/monitoring/logs
     *
     * Returns paginated notification log history.
     * Optional filters: ?type=event_type&status=sent|failed|pending
     */
    public function logs(): JsonResponse
    {
        $request = request();
        $limit   = (int) $request->input('limit', 20);
        $page    = (int) $request->input('page', 1);

        $query = NotificationLog::query();

        if ($request->filled('type')) {
            $query->where('event_type', $request->input('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $paginated = $query->orderBy('created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $page);

        $items = $paginated->map(fn ($log) => [
            'id'         => $log->id,
            'event_type' => $log->event_type,
            'email_to'   => $log->email_to,
            'subject'    => $log->subject,
            'status'     => $log->status,
            'sent_at'    => $log->sent_at,
            'created_at' => $log->created_at,
        ]);

        return response()->json([
            'status' => 'success',
            'data'   => [
                'items'      => $items,
                'pagination' => [
                    'page'        => $paginated->currentPage(),
                    'limit'       => $paginated->perPage(),
                    'total'       => $paginated->total(),
                    'total_pages' => $paginated->lastPage(),
                ],
            ],
            '_links' => [
                'deadlines' => ['href' => '/api/v1/monitoring/deadlines', 'method' => 'GET'],
            ],
        ]);
    }
}
