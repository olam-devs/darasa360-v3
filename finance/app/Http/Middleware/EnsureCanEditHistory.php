<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanEditHistory
{
    /**
     * Only accountants whose can_edit_history flag is on may view the
     * Reconciliation page at all, or hit its mutation endpoints (edit/delete
     * past book transactions). can_edit_history is per-accountant, set by the
     * super admin from a school's Accountants list
     * (SuperAdmin\SchoolController::addAccountant()/updateAccountant()) -
     * reconciliation is deliberately not gated by any school-wide flag, since
     * it's about which specific accountant is trusted with historical edits,
     * not whether the school "has" the feature.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (bool) ($user->can_edit_history ?? false)) {
            return $next($request);
        }

        $message = 'You do not have permission to view or edit reconciliation. Ask your Darasa Finance administrator to grant you the "Edit history" permission.';

        if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
            return response()->json(['error' => $message], 403);
        }

        return redirect()->route('accountant.dashboard')->with('error', $message);
    }
}
