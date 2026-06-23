<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Central\School;
use App\Services\TenantDatabaseManager;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ImpersonationController extends Controller
{
    protected TenantDatabaseManager $tenantManager;
    protected ActivityLogger $activityLogger;

    public function __construct(
        TenantDatabaseManager $tenantManager,
        ActivityLogger $activityLogger
    ) {
        $this->tenantManager = $tenantManager;
        $this->activityLogger = $activityLogger;
    }

    /**
     * Start impersonation session for a school.
     */
    public function impersonate(Request $request, School $school)
    {
        $request->validate([
            'master_password' => 'required',
        ]);

        $superAdmin = auth('superadmin')->user();

        // Verify master password
        if (!Hash::check($request->master_password, $superAdmin->master_password)) {
            return back()->with('error', 'Invalid master password!');
        }

        // Check if school is active
        if (!$school->is_active) {
            return back()->with('error', 'Cannot impersonate inactive school!');
        }

        // Log the impersonation
        $this->activityLogger->logImpersonationStart($superAdmin, $school);

        // Store impersonation data in session
        session([
            'impersonating' => true,
            'impersonating_super_admin_id' => $superAdmin->id,
            'current_school_slug' => $school->slug,
            'current_school_id' => $school->id,
        ]);

        // Redirect to school's accountant dashboard
        return redirect()->route('accountant.dashboard')
            ->with('success', "Now viewing {$school->name}'s dashboard");
    }

    /**
     * End impersonation session.
     */
    public function stopImpersonation(Request $request)
    {
        if (!session('impersonating')) {
            return redirect()->route('superadmin.dashboard');
        }

        $schoolId = session('current_school_id');
        $superAdminId = session('impersonating_super_admin_id');

        if ($schoolId && $superAdminId) {
            $school = School::find($schoolId);
            $superAdmin = \App\Models\Platform\PlatformSuperAdmin::find($superAdminId);

            if ($school && $superAdmin) {
                $this->activityLogger->logImpersonationEnd($superAdmin, $school);
            }
        }

        // Clear impersonation session
        session()->forget(['impersonating', 'impersonating_super_admin_id', 'current_school_slug', 'current_school_id']);

        return redirect()->route('superadmin.dashboard')
            ->with('success', 'Impersonation ended');
    }
}
