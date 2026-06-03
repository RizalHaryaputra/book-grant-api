<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Deadline;

class UserDashboardController extends Controller
{
    public function deadlineWidget(Request $request)
    {
        $user = $request->user();
        $userId = $user ? $user->id : 1; // Fallback for testing if no auth

        $activeTasks = Deadline::where('assignee_id', $userId)
            ->where('status', 'active')
            ->count();

        $nearestDeadline = Deadline::where('assignee_id', $userId)
            ->where('status', 'active')
            ->orderBy('due_date', 'asc')
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'active_tasks_count' => $activeTasks,
                'nearest_deadline' => $nearestDeadline
            ]
        ], 200);
    }
}
