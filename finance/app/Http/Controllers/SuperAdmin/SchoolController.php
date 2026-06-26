<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Central\School;
use App\Models\Central\SchoolAccountant;
use App\Models\Central\ActivityLog;
use App\Services\SchoolProvisioningService;
use App\Services\TenantDatabaseManager;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SchoolController extends Controller
{
    protected SchoolProvisioningService $provisioningService;
    protected ActivityLogger $activityLogger;
    protected TenantDatabaseManager $tenantManager;

    public function __construct(
        SchoolProvisioningService $provisioningService,
        ActivityLogger $activityLogger,
        TenantDatabaseManager $tenantManager
    ) {
        $this->provisioningService = $provisioningService;
        $this->activityLogger = $activityLogger;
        $this->tenantManager = $tenantManager;
    }

    /**
     * Get student count for a school from its database.
     */
    protected function getStudentCount(School $school): int
    {
        try {
            return $this->tenantManager->executeForSchool($school, function() {
                return DB::connection('tenant')->table('students')->count();
            });
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get school settings from tenant database.
     */
    protected function getTenantSchoolSettings(School $school): ?array
    {
        try {
            return $this->tenantManager->executeForSchool($school, function() {
                $settings = DB::connection('tenant')->table('school_settings')->first();
                return $settings ? (array) $settings : null;
            });
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Display a listing of schools.
     */
    public function index(Request $request)
    {
        $query = School::with('accountants');

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Filter by subscription status
        if ($request->has('subscription_status') && $request->subscription_status !== 'all') {
            $query->where('subscription_status', $request->subscription_status);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        $schools = $query->latest()->paginate(15);

        // Get student counts for each school
        foreach ($schools as $school) {
            $school->student_count = $this->getStudentCount($school);
        }

        return view('superadmin.schools.index', compact('schools'));
    }

    /**
     * Show the form for creating a new school.
     */
    public function create()
    {
        return view('superadmin.schools.create');
    }

    /**
     * Store a newly created school.
     */
    public function store(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:schools,slug|regex:/^[a-z0-9-]+$/',
            'contact_email' => 'required|email|unique:schools,contact_email',
            'contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'domain' => 'nullable|string|max:255|unique:schools,domain',
            'max_students' => 'nullable|integer|min:1',
            'subscription_status' => 'required|in:trial,active,suspended,cancelled',
            'subscription_expires_at' => 'nullable|date',
            'has_finance' => 'nullable|boolean',
            'accountant_name' => $request->boolean('has_finance', true) ? 'required|string|max:255' : 'nullable|string|max:255',
            'accountant_email' => $request->boolean('has_finance', true) ? 'required|email|unique:school_accountants,email' : 'nullable|email',
            'accountant_password' => 'nullable|string|min:8',
            'db_host' => 'nullable|string',
            'db_port' => 'nullable|string',
            'db_username' => 'nullable|string',
            'db_password' => 'nullable|string',
            'use_existing_database' => 'nullable|boolean',
            'has_academics' => 'nullable|boolean',
            'academics_db_name' => 'nullable|string|max:255',
        ];

        // Add existing database name validation if using existing database
        if ($request->boolean('use_existing_database')) {
            $rules['existing_database_name'] = 'required|string|max:255';
        }

        $request->validate($rules);

        try {
            $data = $request->all();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Provision the school
            $school = $this->provisioningService->provisionSchool($data);

            return redirect()->route('superadmin.schools.show', $school)
                ->with('success', "School '{$school->name}' has been created successfully!");
        } catch (\Exception $e) {
            \Log::error('School creation failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return back()->withInput()
                ->with('error', 'Failed to create school: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified school.
     */
    public function show(School $school)
    {
        $school->load('accountants', 'activityLogs');

        // Get student count
        $school->student_count = $this->getStudentCount($school);

        // Get tenant school settings (for displaying the actual school name used in tenant)
        $tenantSettings = $this->getTenantSchoolSettings($school);

        // Get latest analytics
        $latestAnalytics = $school->analyticsSummaries()
            ->latest('date')
            ->first();

        // Get recent activities (including accountant actions)
        $recentActivities = ActivityLog::where('school_id', $school->id)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();

        return view('superadmin.schools.show', compact('school', 'tenantSettings', 'latestAnalytics', 'recentActivities'));
    }

    /**
     * Show the form for editing the specified school.
     */
    public function edit(School $school)
    {
        return view('superadmin.schools.edit', compact('school'));
    }

    /**
     * Update the specified school.
     */
    public function update(Request $request, School $school)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'contact_email' => 'required|email|unique:schools,contact_email,' . $school->id,
            'contact_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'domain' => 'nullable|string|max:255|unique:schools,domain,' . $school->id,
            'max_students' => 'nullable|integer|min:1',
            'subscription_status' => 'required|in:trial,active,suspended,cancelled',
            'subscription_expires_at' => 'nullable|date',
        ]);

        $school->update($request->all());

        return redirect()->route('superadmin.schools.show', $school)
            ->with('success', 'School updated successfully!');
    }

    /**
     * Toggle school active status.
     */
    public function toggleStatus(School $school)
    {
        $newStatus = !$school->is_active;
        $school->update(['is_active' => $newStatus]);

        // Log the activity
        $this->activityLogger->logSchoolStatusToggle($school, $newStatus);

        $status = $newStatus ? 'activated' : 'deactivated';
        return back()->with('success', "School has been {$status}!");
    }

    /**
     * Remove the specified school.
     */
    public function destroy(School $school)
    {
        try {
            // Deprovision the school (deletes database and record)
            $this->provisioningService->deprovisionSchool($school);

            return redirect()->route('superadmin.schools.index')
                ->with('success', 'School deleted successfully!');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete school: ' . $e->getMessage());
        }
    }

    /**
     * Reset accountant password.
     */
    public function resetAccountantPassword(Request $request, School $school)
    {
        $request->validate([
            'accountant_id' => 'required|exists:school_accountants,id',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $accountant = SchoolAccountant::findOrFail($request->accountant_id);

        // Verify accountant belongs to this school
        if ($accountant->school_id !== $school->id) {
            return back()->with('error', 'Invalid accountant for this school!');
        }

        $accountant->update([
            'password' => Hash::make($request->new_password),
        ]);

        // Log the activity
        $superAdmin = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction(
            $superAdmin,
            'password_reset',
            "Reset password for accountant: {$accountant->email}",
            $school
        );

        return back()->with('success', "Password reset successfully for {$accountant->email}!");
    }

    /**
     * Update SMS credits for a school.
     */
    public function updateSmsCredits(Request $request, School $school)
    {
        $request->validate([
            'sms_credits' => 'required|integer|min:0',
            'action' => 'required|in:set,add',
        ]);

        $superAdmin = auth('superadmin')->user();
        $oldCredits = $school->sms_credits_assigned;

        if ($request->action === 'set') {
            $school->update(['sms_credits_assigned' => $request->sms_credits]);
            $description = "Set SMS credits from {$oldCredits} to {$request->sms_credits}";
        } else {
            $school->addSmsCredits($request->sms_credits);
            $description = "Added {$request->sms_credits} SMS credits (total: " . ($oldCredits + $request->sms_credits) . ")";
        }

        // Log the activity
        $this->activityLogger->logSuperAdminAction(
            $superAdmin,
            'sms_credits_update',
            $description,
            $school
        );

        return back()->with('success', $description);
    }

    /**
     * Reallocate SMS credits between Finance and Academics for a school.
     * direction: 'to_academics' or 'to_finance'
     */
    public function reallotSmsCredits(Request $request, School $school)
    {
        $request->validate([
            'amount'    => 'required|integer|min:1',
            'direction' => 'required|in:to_academics,to_finance',
        ]);

        $amount = (int) $request->amount;

        // Resolve Academics DB name via platform_schools
        $platformSchool = \App\Models\Platform\PlatformSchool::where('id', $school->platform_school_id)->first();
        $academicsDb = $platformSchool?->academics_db_name ?? null;

        if (!$academicsDb) {
            return back()->with('error', 'This school has no Academics database configured — cannot reallocate.');
        }

        // Connect to Academics DB dynamically
        config(['database.connections.academics_reallot' => array_merge(
            config('database.connections.mysql'),
            ['database' => $academicsDb]
        )]);
        DB::purge('academics_reallot');

        DB::transaction(function () use ($request, $school, $amount, $academicsDb) {
            if ($request->direction === 'to_academics') {
                $available = $school->sms_credits_assigned - $school->sms_credits_used;
                if ($amount > $available) {
                    throw new \Exception("Finance only has {$available} credits available (assigned minus used).");
                }
                // Deduct from Finance
                $school->decrement('sms_credits_assigned', $amount);
                // Add to Academics sms_balances (upsert in case row missing)
                DB::connection('academics_reallot')->table('sms_balances')
                    ->updateOrInsert(
                        ['school_id' => $school->id],
                        ['sms_allocated' => DB::raw("sms_allocated + {$amount}"), 'updated_at' => now()]
                    );
            } else {
                // to_finance: take from Academics
                $acRow = DB::connection('academics_reallot')->table('sms_balances')
                    ->where('school_id', $school->id)->first();
                $acAvailable = $acRow ? ($acRow->sms_allocated - $acRow->sms_used) : 0;
                if ($amount > $acAvailable) {
                    throw new \Exception("Academics only has {$acAvailable} credits available.");
                }
                DB::connection('academics_reallot')->table('sms_balances')
                    ->where('school_id', $school->id)
                    ->decrement('sms_allocated', $amount);
                $school->increment('sms_credits_assigned', $amount);
            }
        });

        $label = $request->direction === 'to_academics' ? 'Finance → Academics' : 'Academics → Finance';
        $this->activityLogger->logSuperAdminAction(
            auth('superadmin')->user(),
            'sms_reallocation',
            "Reallocated {$amount} SMS credits ({$label}) for school #{$school->id}",
            $school
        );

        return back()->with('success', "Reallocated {$amount} SMS credits ({$label}) successfully.");
    }

    /**
     * Add a new accountant to a school.
     */
    public function addAccountant(Request $request, School $school)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:school_accountants,email',
            'password' => 'required|string|min:8',
            'can_edit_history' => 'nullable|boolean',
            'can_view_logs' => 'nullable|boolean',
        ]);

        $accountant = SchoolAccountant::create([
            'school_id' => $school->id,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'is_active' => true,
            'is_primary' => $school->accountants()->count() === 0,
            'can_edit_history' => $request->boolean('can_edit_history'),
            'can_view_logs' => $request->boolean('can_view_logs'),
        ]);

        $this->syncAccountantPermissionsToTenant($school, $accountant);

        // Log the activity
        $superAdmin = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction(
            $superAdmin,
            'accountant_added',
            "Added new accountant: {$accountant->name} ({$accountant->email})",
            $school
        );

        return back()->with('success', "Accountant {$accountant->name} added successfully!");
    }

    /**
     * Update an accountant.
     */
    public function updateAccountant(Request $request, School $school, SchoolAccountant $accountant)
    {
        // Verify accountant belongs to this school
        if ($accountant->school_id !== $school->id) {
            return back()->with('error', 'Invalid accountant for this school!');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:school_accountants,email,' . $accountant->id,
            'is_active' => 'boolean',
            'can_edit_history' => 'nullable|boolean',
            'can_view_logs' => 'nullable|boolean',
        ]);

        $accountant->update([
            'name' => $request->name,
            'email' => $request->email,
            'is_active' => $request->boolean('is_active', true),
            'can_edit_history' => $request->boolean('can_edit_history'),
            'can_view_logs' => $request->boolean('can_view_logs'),
        ]);

        $this->syncAccountantPermissionsToTenant($school, $accountant);

        // Log the activity
        $superAdmin = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction(
            $superAdmin,
            'accountant_updated',
            "Updated accountant: {$accountant->name}",
            $school
        );

        return back()->with('success', "Accountant {$accountant->name} updated successfully!");
    }

    /**
     * Toggle accountant active status.
     */
    public function toggleAccountantStatus(School $school, SchoolAccountant $accountant)
    {
        // Verify accountant belongs to this school
        if ($accountant->school_id !== $school->id) {
            return back()->with('error', 'Invalid accountant for this school!');
        }

        $newStatus = !$accountant->is_active;
        $accountant->update(['is_active' => $newStatus]);

        // Log the activity
        $superAdmin = auth('superadmin')->user();
        $status = $newStatus ? 'activated' : 'deactivated';
        $this->activityLogger->logSuperAdminAction(
            $superAdmin,
            'accountant_status_toggle',
            "Accountant {$accountant->name} {$status}",
            $school
        );

        return back()->with('success', "Accountant {$accountant->name} has been {$status}!");
    }

    /**
     * Delete an accountant.
     */
    public function deleteAccountant(School $school, SchoolAccountant $accountant)
    {
        // Verify accountant belongs to this school
        if ($accountant->school_id !== $school->id) {
            return back()->with('error', 'Invalid accountant for this school!');
        }

        // Don't allow deleting the last accountant
        if ($school->accountants()->count() <= 1) {
            return back()->with('error', 'Cannot delete the last accountant for this school!');
        }

        $name = $accountant->name;
        $accountant->delete();

        // Log the activity
        $superAdmin = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction(
            $superAdmin,
            'accountant_deleted',
            "Deleted accountant: {$name}",
            $school
        );

        return back()->with('success', "Accountant {$name} has been deleted!");
    }

    /**
     * Toggle a platform flag: has_academics, cross_jump_enabled, parent_cross_access.
     */
    public function togglePlatformFlag(Request $request, School $school)
    {
        $allowed = ['has_academics', 'cross_jump_enabled', 'parent_cross_access'];
        $flag = $request->input('flag');

        if (!in_array($flag, $allowed, true)) {
            return back()->with('error', 'Invalid flag.');
        }

        // cross_jump_enabled requires both systems to be enabled
        if ($flag === 'cross_jump_enabled' && !$school->has_academics) {
            return back()->with('error', 'Academics must be enabled before turning on cross-system jump.');
        }

        $newValue = !$school->$flag;
        $school->update([$flag => $newValue]);

        // Mirror to platform_schools
        if ($school->platform_school_id) {
            \App\Models\Platform\PlatformSchool::where('id', $school->platform_school_id)
                ->update([$flag => $newValue]);
        }

        $label = str_replace('_', ' ', $flag);
        $status = $newValue ? 'enabled' : 'disabled';
        $superAdmin = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction($superAdmin, "toggle_{$flag}", "School {$school->name}: {$label} {$status}", $school);

        return back()->with('success', ucfirst($label) . " has been {$status} for {$school->name}.");
    }

    /**
     * Sync school name from tenant database.
     */
    public function syncNameToTenant(School $school)
    {
        try {
            $this->provisioningService->syncSettingsToTenant($school);

            $superAdmin = auth('superadmin')->user();
            $this->activityLogger->logSuperAdminAction(
                $superAdmin,
                'school_name_synced_to_tenant',
                "Synced school name to tenant: '{$school->name}'",
                $school
            );

            return back()->with('success', "School name '{$school->name}' synced to tenant database.");
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to sync to tenant: ' . $e->getMessage());
        }
    }

    public function syncNameFromTenant(School $school)
    {
        $tenantSettings = $this->getTenantSchoolSettings($school);

        if (!$tenantSettings || !isset($tenantSettings['school_name'])) {
            return back()->with('error', 'Could not fetch school name from tenant database.');
        }

        $oldName = $school->name;
        $newName = $tenantSettings['school_name'];

        $school->update(['name' => $newName]);

        // Log the activity
        $superAdmin = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction(
            $superAdmin,
            'school_name_synced',
            "Synced school name from tenant: '{$oldName}' -> '{$newName}'",
            $school
        );

        return back()->with('success', "School name synced from tenant: {$newName}");
    }

    /**
     * Mirror accountant permission flags to the tenant users table (by email).
     */
    protected function syncAccountantPermissionsToTenant(School $school, SchoolAccountant $accountant): void
    {
        try {
            $this->tenantManager->executeForSchool($school, function () use ($accountant) {
                DB::connection('tenant')->table('users')
                    ->where('email', $accountant->email)
                    ->update([
                        'can_edit_history' => (bool) $accountant->can_edit_history,
                        'can_view_logs' => (bool) $accountant->can_view_logs,
                        'updated_at' => now(),
                    ]);
            });
        } catch (\Throwable $e) {
            // Non-fatal: central record is authoritative; tenant may be single-DB setup.
        }
    }
}
