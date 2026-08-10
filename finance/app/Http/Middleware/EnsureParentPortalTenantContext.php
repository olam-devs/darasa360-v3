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
 * (to find the student by portal_email). Real per-school login URLs now
 * exist (/parent/login/{schoolSlug}, see routes/auth.php) - the route
 * parameter is authoritative when present. Bare /parent/login (no slug,
 * old bookmarks/links) still falls back to HasSchoolContext's legacy "if
 * exactly one school exists, use it" guess, which silently shows the wrong
 * school's branding/credentials the moment a second real school exists.
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
            // No slug given (old-style /parent/login) - legacy fallback.
            $school = $this->getCurrentSchool();
        }

        if ($school) {
            $this->tenantManager->switchToSchool($school);
            session(['parent_school_slug' => $school->slug]);
        }

        return $next($request);
    }
}
