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
 * Real per-school login URLs now exist (/headmaster/login/{schoolSlug}, see
 * routes/headmaster.php) - the route parameter is authoritative when
 * present. Bare /headmaster/login (no slug, old bookmarks/links) still falls
 * back to HasSchoolContext's legacy "if exactly one school exists, use it"
 * guess, which silently resolves the wrong school's credentials the moment a
 * second real school exists.
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

        // Guest hitting the login page / submitting login.
        $routeSlug = $request->route('schoolSlug');
        if ($routeSlug) {
            // An explicit slug was given - resolve only that school. Don't
            // silently fall back to a different one on a bad/typo'd slug.
            $school = \App\Models\Central\School::where('slug', $routeSlug)->first();
            if (!$school) {
                abort(404, 'School not found.');
            }
        } else {
            // No slug given (old-style /headmaster/login) - legacy fallback.
            $school = $this->getCurrentSchool();
        }

        if ($school) {
            $this->tenantManager->switchToSchool($school);
            session(['headmaster_school_slug' => $school->slug]);
        }

        return $next($request);
    }
}
