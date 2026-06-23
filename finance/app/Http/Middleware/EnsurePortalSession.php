<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allow accountant web auth, headmaster session, or parent session.
 */
class EnsurePortalSession
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && in_array($request->user()->role, ['accountant', 'superadmin'], true)) {
            return $next($request);
        }

        if ($request->session()->has('headmaster_id') || $request->session()->has('parent_student_id')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['error' => 'Unauthorized. Please sign in.'], 401);
        }

        return redirect()->route('login');
    }
}
