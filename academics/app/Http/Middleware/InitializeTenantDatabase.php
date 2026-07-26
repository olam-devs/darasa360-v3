<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\School;

class InitializeTenantDatabase
{
    public function handle(Request $request, Closure $next)
    {
        $tenantDb = session('school_db'); // or get from Auth, subdomain, etc.

        if ($tenantDb) {
            $school = School::where('database_url', $tenantDb)->first();
            if ($school) {
                $school->useAsTenant();
            }
        }

        return $next($request);
    }
}
