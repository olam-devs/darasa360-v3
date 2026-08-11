<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Only the school's super-admin-designated "main accountant"
 * (SchoolAccountant::is_main_accountant) may manage other accountants'
 * can_edit_history/can_view_logs, or (later) expense-category/budget/
 * submission-approval actions. Being main is set by the super admin only
 * (SuperAdmin\SchoolController::addAccountant()/updateAccountant()) - never
 * grantable through any route this middleware guards.
 */
class EnsureMainAccountant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (bool) ($user->is_main_accountant ?? false)) {
            return $next($request);
        }

        $message = 'Only this school\'s main accountant can do this. Ask your Darasa Finance administrator to designate one.';

        if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
            return response()->json(['error' => $message], 403);
        }

        return redirect()->route('accountant.dashboard')->with('error', $message);
    }
}
