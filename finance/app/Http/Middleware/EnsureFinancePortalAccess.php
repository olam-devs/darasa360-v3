<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allow accountant/superadmin web auth OR active headmaster session (read-only portal).
 */
class EnsureFinancePortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user && in_array($user->role, ['superadmin', 'accountant'], true)) {
            return $next($request);
        }

        if ($request->session()->has('headmaster_id')) {
            return $next($request);
        }

        if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
            return response()->json(['error' => 'Unauthorized.'], 401);
        }

        return redirect()->route('login');
    }
}
