<?php

namespace App\Http\Middleware;

use App\Traits\HasSchoolContext;
use App\Services\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Points the 'tenant' connection at the right school for the headmaster
 * portal - both the guest login page (registration_number lookup) and the
 * authenticated dashboard/ledgers/overdue/invoices routes - before any
 * tenant model (Headmaster, Voucher, ...) is touched.
 *
 * Same chicken-and-egg problem as EnsureParentPortalTenantContext: there's
 * no logged-in Laravel user to read the school from, only a plain session
 * flag (headmaster_id), so this resolves via HasSchoolContext's fallback
 * chain (currently: "if exactly one school exists, use it") and then pins
 * the resolved school's slug in session for subsequent requests.
 */
class EnsureHeadmasterTenantContext
{
    use HasSchoolContext;

    public function __construct(protected TenantDatabaseManager $tenantManager)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Already-authenticated headmaster: the session carries which school this is.
        if (session('headmaster_id')) {
            $slug = session('headmaster_school_slug');
            if ($slug) {
                $school = \App\Models\Central\School::where('slug', $slug)->first();
                if ($school) {
                    $this->tenantManager->switchToSchool($school);
                }
            }

            return $next($request);
        }

        // Guest hitting the login page / submitting login: resolve via the
        // same fallback chain used elsewhere (currently: the only school).
        $school = $this->getCurrentSchool();
        if ($school) {
            $this->tenantManager->switchToSchool($school);
            session(['headmaster_school_slug' => $school->slug]);
        }

        return $next($request);
    }
}
