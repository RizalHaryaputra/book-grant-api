<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }

        // Get role name safely (fallback to DB if the User->role relationship isn't built yet)
        $roleName = null;
        if (method_exists($user, 'role') && $user->role) {
            $roleName = $user->role->name;
        } elseif (isset($user->role_id)) {
            $roleName = \Illuminate\Support\Facades\DB::table('roles')->where('id', $user->role_id)->value('name');
        }

        if (! in_array($roleName, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied.'
            ], 403);
        }

        return $next($request);
    }
}