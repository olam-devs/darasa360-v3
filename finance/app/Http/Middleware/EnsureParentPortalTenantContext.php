<?php

namespace App\Http\Middleware;

use App\Traits\HasSchoolContext;
use App\Services\TenantDatabaseManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Points the 'tenant' connection at the right school for the parent portal -
 * both the guest login page (student/portal_email lookup, school branding)
 * and the authenticated dashboard - before any tenant model (Student, ...)
 * is touched.
 *
 * Unlike EnsureTenantContext (accountant), there's no logged-in user to read
 * the school from until *after* the tenant DB has already been queried once
 * (to find the student by portal_email) - so this resolves via
 * HasSchoolContext's fallback chain instead. Today that chain's only real
 * option for an anonymous visitor is "if exactly one school exists, use it",
 * which is the same latent gap already flagged for headmaster/parent portals
 * (see CLAUDE.md) - this closes it for parent, at least until real
 * per-school parent-portal URLs (subdomain/slug) exist.
 */
class EnsureParentPortalTenantContext
{
    use HasSchoolContext;

    public function __construct(protected TenantDatabaseManager $tenantManager)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Already-authenticated parent: the session carries which school this is.
        $studentId = session('parent_student_id');
        if ($studentId) {
            $slug = session('parent_school_slug');
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
            session(['parent_school_slug' => $school->slug]);
        }

        return $next($request);
    }
}
