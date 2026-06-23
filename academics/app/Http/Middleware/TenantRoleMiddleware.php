<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $userId  = session('user_id');
        $schoolDb = session('school_db');

        if (!$userId || !$schoolDb) {
            return redirect()->route('login')->with('error', 'Please login to continue');
        }

        $sessionRole = session('role');

        if (!in_array($sessionRole, $roles)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
