<?php

namespace App\Http\Middleware;

use App\Models\Central\School;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates accountant-facing features that are off by default per school -
 * headmaster setup, parent portal password setup - behind a boolean the
 * Finance super admin controls from the school's detail page
 * (App\Http\Controllers\SuperAdmin\SchoolController::togglePlatformFlag).
 *
 * Reconciliation is NOT gated here - it's per-accountant, not per-school
 * (SchoolAccountant::can_edit_history, via the can.edit.history middleware).
 *
 * Usage: ->middleware('school.feature:headmaster_management_enabled')
 */
class EnsureSchoolFeatureEnabled
{
    protected const LABELS = [
        'headmaster_management_enabled' => 'Headmaster setup',
        'parent_portal_management_enabled' => 'Parent portal password setup',
    ];

    public function handle(Request $request, Closure $next, string $flag): Response
    {
        $school = School::resolveForRequest($request);

        if ($school && $school->{$flag}) {
            return $next($request);
        }

        $label = self::LABELS[$flag] ?? 'This feature';
        $message = "{$label} has not been enabled for your school. Ask your Darasa Finance administrator to turn it on.";

        if ($request->expectsJson() || str_starts_with($request->path(), 'api/')) {
            return response()->json(['error' => $message], 403);
        }

        return redirect()->route('accountant.dashboard')->with('error', $message);
    }
}
