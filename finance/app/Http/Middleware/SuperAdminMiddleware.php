<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if super admin is authenticated
        if (!auth('superadmin')->check()) {
            return redirect()->route('superadmin.login')
                ->with('error', 'Please login to access the super admin panel');
        }

        // Check if super admin is active
        $superAdmin = auth('superadmin')->user();
        if (!$superAdmin->is_active) {
            auth('superadmin')->logout();
            return redirect()->route('superadmin.login')
                ->with('error', 'Your account has been deactivated. Please contact support.');
        }

        // Ensure we're using the central database
        config(['database.default' => 'central']);

        return $next($request);
    }
}
