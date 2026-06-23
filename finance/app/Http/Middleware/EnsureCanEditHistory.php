<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureCanEditHistory
{
    /**
     * Only accountants whose can_edit_history flag is on may hit reconciliation
     * mutation endpoints (edit / delete past book transactions).
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! (bool) ($user->can_edit_history ?? false)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'You do not have permission to edit historical records. Ask the system administrator to grant you the "Edit history" permission.',
                ], 403);
            }

            abort(403, 'You do not have permission to edit historical records.');
        }

        return $next($request);
    }
}
