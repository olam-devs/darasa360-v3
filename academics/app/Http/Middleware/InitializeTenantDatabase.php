<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class InitializeTenantDatabase
{
    public function handle(Request $request, Closure $next)
    {
        $tenantDb = session('school_db'); // or get from Auth, subdomain, etc.

        if ($tenantDb) {
            // Dynamically set the tenant DB name
            Config::set('database.connections.tenant.database', $tenantDb);

            // Clear any previous DB connection cache
            DB::purge('tenant');

            // Reconnect to the new DB
            DB::reconnect('tenant');
        }

        return $next($request);
    }
}
