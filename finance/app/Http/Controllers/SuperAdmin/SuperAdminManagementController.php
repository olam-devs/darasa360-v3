<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformSuperAdmin as SuperAdmin;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class SuperAdminManagementController extends Controller
{
    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * List all super admins.
     */
    public function index()
    {
        $superAdmins = SuperAdmin::latest()->paginate(20);
        $currentAdminId = auth('superadmin')->id();
        return view('superadmin.admins.index', compact('superAdmins', 'currentAdminId'));
    }

    /**
     * Store a new super admin.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:platform.platform_super_admins,email',
            'password' => 'required|string|min:8|confirmed',
            'master_password' => 'required|string|min:8',
        ]);

        $admin = SuperAdmin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
            'master_password' => $request->master_password,
            'is_active' => true,
        ]);

        $currentAdmin = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction(
            $currentAdmin,
            'super_admin_created',
            "Created new super admin: {$admin->name} ({$admin->email})"
        );

        return back()->with('success', "Super admin '{$admin->name}' created successfully!");
    }

    /**
     * Update a super admin's details.
     */
    public function update(Request $request, SuperAdmin $superAdmin)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:platform.platform_super_admins,email,' . $superAdmin->id,
        ]);

        $superAdmin->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        $currentAdmin = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction(
            $currentAdmin,
            'super_admin_updated',
            "Updated super admin: {$superAdmin->name} ({$superAdmin->email})"
        );

        return back()->with('success', "Super admin '{$superAdmin->name}' updated successfully!");
    }

    /**
     * Toggle active status (cannot deactivate self).
     */
    public function toggleStatus(SuperAdmin $superAdmin)
    {
        $currentAdmin = auth('superadmin')->user();

        if ($superAdmin->id === $currentAdmin->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $newStatus = !$superAdmin->is_active;
        $superAdmin->update(['is_active' => $newStatus]);

        $status = $newStatus ? 'activated' : 'deactivated';
        $this->activityLogger->logSuperAdminAction(
            $currentAdmin,
            'super_admin_status_toggle',
            "Super admin {$superAdmin->name} {$status}"
        );

        return back()->with('success', "Super admin '{$superAdmin->name}' has been {$status}!");
    }

    /**
     * Reset password for any super admin.
     */
    public function resetPassword(Request $request, SuperAdmin $superAdmin)
    {
        $request->validate([
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $superAdmin->update([
            'password' => $request->new_password,
        ]);

        $currentAdmin = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction(
            $currentAdmin,
            'super_admin_password_reset',
            "Reset password for super admin: {$superAdmin->name} ({$superAdmin->email})"
        );

        return back()->with('success', "Password reset successfully for '{$superAdmin->email}'!");
    }
}
