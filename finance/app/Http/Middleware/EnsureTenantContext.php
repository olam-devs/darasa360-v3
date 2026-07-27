<?php

namespace App\Http\Middleware;

use App\Models\Central\School;
use App\Models\Central\SchoolAccountant;
use App\Services\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Points the 'tenant' connection at the logged-in accountant's own school
 * before any tenant model (Student, SchoolClass, Voucher, ...) is touched.
 *
 * Without this, those models silently fall back to whatever TENANT_DB_DATABASE
 * (or DB_DATABASE) resolves to by default - the central database on this
 * environment - instead of the school's own provisioned tenant database. This
 * went unnoticed because until now only one Finance school existed, so
 * whichever DB got hit "looked" like it belonged to that school.
 */
class EnsureTenantContext
{
    public function __construct(protected TenantDatabaseManager $tenantManager)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Super-admin impersonating a school (see SuperAdmin\ImpersonationController).
        if (session('impersonating') && session('current_school_id')) {
            $school = School::find(session('current_school_id'));
            if ($school) {
                $this->tenantManager->switchToSchool($school);
            }

            return $next($request);
        }

        // Regular accountant login.
        $user = $request->user();
        if ($user instanceof SchoolAccountant && $user->school) {
            $this->tenantManager->switchToSchool($user->school);
        }

        return $next($request);
    }
}
