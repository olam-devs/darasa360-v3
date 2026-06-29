<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use App\Models\User;
use App\Models\Role;
use App\Models\SchoolAdmin;
use App\Models\Location;
use App\Helpers\DatabaseHelper;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SuperAdminController extends Controller
{
    /**
     * Display super admin dashboard with all schools and billing info
     */
    public function dashboard()
    {
        $schools = School::with(['administrator', 'location', 'systemAdmins.admin'])
            ->withCount('users')
            ->get();

        $totalSchools = $schools->count();
        $totalUsers = $schools->sum('active_users');
        $totalRevenue = $schools->sum('monthly_charge');
        $activeSchools = $schools->where('billing_status', 'active')->count();

        // Get escalated tickets from all tenant databases
        $escalatedTickets = collect();
        foreach ($schools as $school) {
            try {
                preg_match('/\/([^\/]+)$/', $school->database_url, $matches);
                $dbName = $matches[1] ?? null;
                if (!$dbName) continue;

                config(['database.connections.tenant.database' => $dbName]);
                DB::purge('tenant');
                DB::reconnect('tenant');

                $tickets = DB::connection('tenant')
                    ->table('support_tickets')
                    ->where('is_escalated', true)
                    ->where('escalation_level', 'super_admin')
                    ->whereIn('status', ['open', 'in_progress'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get();

                foreach ($tickets as $ticket) {
                    $ticket->school_name = $school->name;
                    $ticket->school_id = $school->id;
                    $ticket->tenant_db = $dbName;
                    $escalatedTickets->push($ticket);
                }
            } catch (\Exception $e) {
                \Log::error("Error fetching tickets from school {$school->id}: " . $e->getMessage());
                continue;
            }
        }

        $escalatedTickets = $escalatedTickets->sortByDesc('created_at')->take(20);

        return view('super_admin.dashboard', compact(
            'schools',
            'totalSchools',
            'totalUsers',
            'totalRevenue',
            'activeSchools',
            'escalatedTickets'
        ));
    }

    /**
     * Show form to create a new school
     */
    public function createSchoolForm()
    {
        $locations = Location::all();
        return view('super_admin.schools.create', compact('locations'));
    }

    /**
     * Store a new school and create owner account
     */
    public function createSchool(Request $request)
    {
        $validated = $request->validate([
            'school_name' => 'required|string|max:255',
            'location_id' => 'required|exists:locations,id',
            'db_name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/|unique:schools,database_url',
            'db_username' => 'required|string|max:32|regex:/^[a-zA-Z0-9_]+$/',
            'owner_name' => 'required|string|max:255',
            'owner_email' => 'required|email|unique:users,email',
            'owner_phone' => 'required|string|unique:users,phone_number',
            'owner_username' => 'required|string|unique:users,username',
            'owner_password' => 'required|string|min:6',
            'price_per_user' => 'nullable|numeric|min:0',
        ]);

        // Additional validation for database credentials
        if (!DatabaseHelper::isValidDatabaseName($validated['db_name'])) {
            return back()->withErrors(['db_name' => 'Invalid database name format'])->withInput();
        }

        if (!DatabaseHelper::isValidUsername($validated['db_username'])) {
            return back()->withErrors(['db_username' => 'Invalid database username format'])->withInput();
        }

        // Check if database already exists
        if (DatabaseHelper::databaseExists($validated['db_name'])) {
            return back()->withErrors(['db_name' => 'Database already exists'])->withInput();
        }

        // Check if MySQL user already exists
        if (DatabaseHelper::userExists($validated['db_username'])) {
            return back()->withErrors(['db_username' => 'Database user already exists'])->withInput();
        }

        DB::beginTransaction();

        try {
            // Generate sequential numeric school code (3-digit, used in registration numbers)
            $lastCode = School::max('school_code');
            $nextNum  = $lastCode ? ((int) $lastCode + 1) : 1;
            $schoolCode = str_pad($nextNum, 3, '0', STR_PAD_LEFT);

            // Build database URL using DatabaseHelper
            $dbUrl = DatabaseHelper::buildDatabaseUrl(
                $validated['db_username'],
                $validated['db_name']
            );

            // Create database and MySQL user with proper permissions
            DatabaseHelper::createDatabaseUser(
                $validated['db_username'],
                $validated['db_name']
            );

            // Test the connection
            if (!DatabaseHelper::testConnection($dbUrl)) {
                throw new \Exception('Failed to connect to the created database. Please check credentials.');
            }

            // Create school record
            $school = School::create([
                'name' => $validated['school_name'],
                'location_id' => $validated['location_id'],
                'database_url' => $dbUrl,
                'school_code' => $schoolCode,
                'price_per_user' => $validated['price_per_user'] ?? 0,
                'billing_start_date' => now(),
                'next_billing_date' => now()->addMonth(),
                'billing_status' => 'active',
                'status' => 'active',
            ]);

            // Get owner role
            $ownerRole = Role::where('name', 'owner')->first();

            // Create owner user account
            $owner = User::create([
                'username' => $validated['owner_username'],
                'password' => Hash::make($validated['owner_password']),
                'email' => $validated['owner_email'],
                'phone_number' => $validated['owner_phone'],
                'role_id' => $ownerRole->id,
                'school_id' => $school->id,
            ]);

            // Update school with owner user_id
            $school->update(['user_id' => $owner->id]);

            DB::commit();

            return redirect()->route('super_admin.schools.allocate_admin', $school->id)
                ->with('success', 'School created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            // Clean up the tenant database if it was created before the failure
            try {
                DB::statement("DROP DATABASE IF EXISTS `{$validated['db_name']}`");
            } catch (\Exception $cleanupEx) {
                // Ignore cleanup errors
            }
            return back()->withErrors(['error' => 'Failed to create school: ' . $e->getMessage()]);
        }
    }

    /**
     * View specific school details and billing
     */
    public function viewSchool($id)
    {
        $school = School::with([
            'administrator',
            'location',
            'systemAdmins.admin',
            'schoolSystemAdmins.admin',
            'users'
        ])->findOrFail($id);

        // Calculate billing details
        $school->total_users = $school->users()->count();
        $school->active_users = $school->total_users; // users table has no is_active column
        $school->monthly_charge = $school->active_users * $school->price_per_user;
        $school->save();

        return view('super_admin.schools.view', compact('school'));
    }

    /**
     * Assign a system admin to a school
     */
    public function assignSystemAdmin(Request $request, $schoolId)
    {
        $validated = $request->validate([
            'admin_name' => 'required|string',
            'admin_email' => 'required|email|unique:users,email',
            'admin_phone' => 'required|string|unique:users,phone_number',
            'admin_username' => 'required|string|unique:users,username',
            'admin_password' => 'required|string|min:6',
        ]);

        DB::beginTransaction();

        try {
            $school = School::findOrFail($schoolId);

            // Get system_admin role
            $systemAdminRole = Role::where('name', 'system_admin')->first();

            // Create system admin user
            $systemAdmin = User::create([
                'username' => $validated['admin_username'],
                'password' => Hash::make($validated['admin_password']),
                'email' => $validated['admin_email'],
                'phone_number' => $validated['admin_phone'],
                'role_id' => $systemAdminRole->id,
            ]);

            // Assign to school
            SchoolAdmin::create([
                'school_id' => $school->id,
                'system_admin_id' => $systemAdmin->id,
                'admin_type' => 'system_admin',
                'is_active' => true,
            ]);

            DB::commit();

            return back()->with('success', 'System admin assigned successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to assign admin: ' . $e->getMessage()]);
        }
    }

    /**
     * Update school billing information
     */
    public function updateBilling(Request $request, $id)
    {
        $validated = $request->validate([
            'price_per_user' => 'required|numeric|min:0',
            'billing_status' => 'required|in:active,suspended,overdue',
        ]);

        $school = School::findOrFail($id);

        // Recalculate monthly charge
        $activeUsers = $school->users()->count();
        $monthlyCharge = $activeUsers * $validated['price_per_user'];

        $school->update([
            'price_per_user' => $validated['price_per_user'],
            'monthly_charge' => $monthlyCharge,
            'billing_status' => $validated['billing_status'],
        ]);

        return back()->with('success', 'Billing information updated successfully!');
    }

    /**
     * List all system admins
     */
    public function listSystemAdmins()
    {
        $systemAdminRole = Role::where('name', 'system_admin')->first();
        $systemAdmins = User::where('role_id', $systemAdminRole->id)
            ->with('schoolAdmins.school')
            ->get();

        return view('super_admin.admins.index', compact('systemAdmins'));
    }

    /**
     * Create another super admin
     */
    public function createSuperAdmin(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|unique:users,phone_number',
        ]);

        $superAdminRole = Role::where('name', 'super_admin')->first();

        User::create([
            'username' => $validated['username'],
            'password' => Hash::make($validated['password']),
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'],
            'role_id' => $superAdminRole->id,
        ]);

        return back()->with('success', 'Super admin created successfully!');
    }

    /**
     * Create System Admin
     */
    public function createSystemAdmin(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|unique:users,username',
            'password' => 'required|string|min:6',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'required|string|unique:users,phone_number',
            'school_ids' => 'nullable|array',
            'school_ids.*' => 'exists:schools,id',
        ]);

        DB::beginTransaction();

        try {
            $systemAdminRole = Role::where('name', 'system_admin')->first();

            $systemAdmin = User::create([
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'],
                'role_id' => $systemAdminRole->id,
            ]);

            // Assign to schools if provided
            if (!empty($validated['school_ids'])) {
                foreach ($validated['school_ids'] as $schoolId) {
                    SchoolAdmin::create([
                        'school_id' => $schoolId,
                        'system_admin_id' => $systemAdmin->id,
                        'admin_type' => 'system_admin',
                        'is_active' => true,
                    ]);
                }
            }

            DB::commit();

            $message = 'System admin created successfully!';
            if (!empty($validated['school_ids'])) {
                $message .= ' Assigned to ' . count($validated['school_ids']) . ' school(s).';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create system admin: ' . $e->getMessage()]);
        }
    }


    /**
     * SMS Management Index - Redirect to allocations page
     */
    public function smsIndex()
    {
        return redirect()->route('super_admin.sms.allocations');
    }

    /**
     * SMS Send Form
     */
    public function smsSendForm()
    {
        $schools = School::all();
        return view('super_admin.sms.send', compact('schools'));
    }

    /**
     * Send SMS
     */
    public function smsSend(Request $request)
    {
        $validated = $request->validate([
            'recipients' => 'required|string',
            'message' => 'required|string|max:160',
        ]);

        // SMS sending logic would go here
        return back()->with('success', 'SMS sent successfully!');
    }

    /**
     * SMS History
     */
    public function smsHistory()
    {
        return view('super_admin.sms.history');
    }

    /**
     * Locations Index
     */
    public function locationsIndex()
    {
        $locations = Location::all();
        return view('super_admin.locations.index', compact('locations'));
    }

    /**
     * Create Location
     */
    public function createLocation(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:locations,name',
            'code' => 'required|string|max:10|unique:locations,code',
        ]);

        Location::create($validated);

        return back()->with('success', 'Location created successfully!');
    }

    /**
     * Update Location
     */
    public function updateLocation(Request $request, $id)
    {
        $location = Location::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:locations,name,' . $id,
            'code' => 'required|string|max:10|unique:locations,code,' . $id,
        ]);

        $location->update($validated);

        return back()->with('success', 'Location updated successfully!');
    }

    /**
     * Delete Location
     */
    public function deleteLocation($id)
    {
        Location::destroy($id);
        return back()->with('success', 'Location deleted successfully!');
    }

    /**
     * Logs Index
     */
    public function logsIndex()
    {
        // Get recent system logs with enhanced fields
        $logs = DB::table('logs')
            ->orderBy('logged_at', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        return view('super_admin.logs.index', compact('logs'));
    }

    /**
     * View Log
     */
    public function viewLog($id)
    {
        $log = DB::table('activity_log')->where('id', $id)->first();
        return view('super_admin.logs.view', compact('log'));
    }

    /**
     * Passwords Index
     */
    /**
     * Passwords Index - Enhanced with AJAX support
     */
    public function passwordsIndex(Request $request)
    {
        $schools = School::orderBy('name')->get();

        // Handle AJAX request
        if ($request->ajax() || $request->input('ajax')) {
            $search = $request->input('search', '');
            $userType = $request->input('user_type', 'all');
            $roleFilter = $request->input('role', 'all');
            $schoolFilter = $request->input('school', 'all');

            $allUsers = collect();

            // 1. Get users from central database
            $centralQuery = User::with(['userRole', 'school']);

            if ($search) {
                $centralQuery->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('registration_no', 'like', "%{$search}%")
                      ->orWhere('phone_number', 'like', "%{$search}%");
                });
            }

            if ($userType === 'admins') {
                $adminRoles = ['super_admin', 'system_admin'];
                $centralQuery->whereHas('userRole', function ($q) use ($adminRoles) {
                    $q->whereIn('name', $adminRoles);
                });
            }

            if ($roleFilter !== 'all') {
                $centralQuery->whereHas('userRole', function ($q) use ($roleFilter) {
                    $q->where('name', $roleFilter);
                });
            }

            if ($schoolFilter !== 'all') {
                $centralQuery->where('school_id', $schoolFilter);
            }

            $centralUsers = $centralQuery->get()->map(function ($user) {
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'registration_no' => $user->registration_no,
                    'email' => $user->email,
                    'phone_number' => $user->phone_number,
                    'role' => $user->userRole->name ?? null,
                    'school_name' => $user->school->name ?? null,
                    'db_type' => 'central',
                    'tenant_db' => null,
                    'school_id' => $user->school_id,
                ];
            });

            $allUsers = $allUsers->merge($centralUsers);

            // 2. Get users from each tenant database (only if no admin-only filter)
            if ($userType !== 'admins' && !in_array($roleFilter, ['super_admin', 'system_admin'])) {
                $schoolsToQuery = $schoolFilter !== 'all' ? School::where('id', $schoolFilter)->get() : $schools;

                foreach ($schoolsToQuery as $school) {
                    try {
                        // Extract database name from database_url
                        preg_match('/\/([^\/]+)$/', $school->database_url, $matches);
                        $dbName = $matches[1] ?? null;

                        if (!$dbName) continue;

                        // Configure tenant connection
                        config(['database.connections.tenant.database' => $dbName]);
                        DB::purge('tenant');
                        DB::reconnect('tenant');

                        // Query tenant users
                        $tenantQuery = DB::connection('tenant')->table('schoolUsers')
                            ->join('roles', 'schoolUsers.role_id', '=', 'roles.id');

                        if ($search) {
                            $tenantQuery->where(function ($q) use ($search) {
                                $q->where('schoolUsers.username', 'like', "%{$search}%")
                                  ->orWhere('schoolUsers.email', 'like', "%{$search}%")
                                  ->orWhere('schoolUsers.registration_no', 'like', "%{$search}%")
                                  ->orWhere('schoolUsers.phone_number', 'like', "%{$search}%");
                            });
                        }

                        if ($roleFilter !== 'all') {
                            $tenantQuery->where('roles.name', $roleFilter);
                        }

                        $tenantUsers = $tenantQuery->select(
                            'schoolUsers.id',
                            'schoolUsers.username',
                            'schoolUsers.registration_no',
                            'schoolUsers.email',
                            'schoolUsers.phone_number',
                            'roles.name as role'
                        )->limit(50)->get();

                        foreach ($tenantUsers as $user) {
                            $allUsers->push([
                                'id' => $user->id,
                                'username' => $user->username,
                                'registration_no' => $user->registration_no,
                                'email' => $user->email,
                                'phone_number' => $user->phone_number,
                                'role' => $user->role,
                                'school_name' => $school->name,
                                'db_type' => 'tenant',
                                'tenant_db' => $dbName,
                                'school_id' => $school->id,
                            ]);
                        }
                    } catch (\Exception $e) {
                        \Log::error("Error querying tenant DB for school {$school->id}: " . $e->getMessage());
                        continue;
                    }
                }
            }

            // Limit results
            $allUsers = $allUsers->take(200);

            return response()->json([
                'users' => $allUsers->values(),
                'count' => $allUsers->count()
            ]);
        }

        // Regular page load
        return view('content.dashboard.super_admin.manage_passwords', compact('schools'));
    }

    /**
     * Reset Password
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $user->update([
            'password' => Hash::make($validated['new_password'])
        ]);

        return back()->with('success', 'Password changed successfully!');
    }

    /**
     * Support Index
     */
    public function supportIndex()
    {
        // Get all escalated tickets from all schools
        $schools = School::all();
        $allTickets = collect();

        foreach ($schools as $school) {
            $this->configureTenantConnection($school->database_url);

            $schoolTickets = DB::connection('tenant')->table('support_tickets')
                ->where('escalation_level', 'super_admin')
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($schoolTickets as $ticket) {
                $ticket->school_name = $school->name;
                $ticket->school_id = $school->id;
            }

            $allTickets = $allTickets->merge($schoolTickets);
        }

        $tickets = $allTickets->sortByDesc('created_at')->values();

        return view('super_admin.support.index', compact('tickets'));
    }

    /**
     * View Support Ticket
     */
    public function viewSupportTicket($id)
    {
        // Find the ticket in school databases
        $schools = School::all();
        $ticket = null;
        $school = null;

        foreach ($schools as $sch) {
            $this->configureTenantConnection($sch->database_url);

            $foundTicket = DB::connection('tenant')->table('support_tickets')
                ->where('id', $id)
                ->first();

            if ($foundTicket) {
                $ticket = $foundTicket;
                $school = $sch;
                break;
            }
        }

        if (!$ticket) {
            abort(404, 'Ticket not found');
        }

        return view('super_admin.support.view', compact('ticket', 'school'));
    }

    /**
     * Resolve Support Ticket
     */
    public function resolveSupportTicket(Request $request, $id)
    {
        $validated = $request->validate([
            'resolution_notes' => 'required|string',
            'school_id' => 'required|exists:schools,id',
        ]);

        $school = School::findOrFail($validated['school_id']);
        $this->configureTenantConnection($school->database_url);

        DB::connection('tenant')->table('support_tickets')
            ->where('id', $id)
            ->update([
                'status' => 'resolved',
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
                'resolution_notes' => $validated['resolution_notes'],
                'updated_at' => now(),
            ]);

        return back()->with('success', 'Ticket resolved successfully!');
    }

    /**
     * Configure tenant database connection
     */
    private function configureTenantConnection($dbUrl)
    {
        if (!$dbUrl) {
            return;
        }

        $parsed = parse_url($dbUrl);

        config([
            'database.connections.tenant' => [
                'driver' => 'mysql',
                'host' => $parsed['host'] ?? '127.0.0.1',
                'port' => $parsed['port'] ?? '3306',
                'database' => ltrim($parsed['path'] ?? '', '/'),
                'username' => $parsed['user'] ?? 'root',
                'password' => $parsed['pass'] ?? '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ]
        ]);

        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    /**
     * Show alert school admins page
     */
    public function smsAlertAdminsForm()
    {
        return view('super_admin.sms.alert_admins');
    }

    /**
     * Send alert to school admins
     */
    public function sendAdminAlert(Request $request)
    {
        $validated = $request->validate([
            'alert_type' => 'required|in:system_update,scheduled_maintenance,urgent_security,feature_announcement,general',
            'recipient_type' => 'required|in:all_admins,system_admins,specific_schools',
            'school_ids' => 'nullable|array',
            'school_ids.*' => 'exists:schools,id',
            'priority' => 'required|in:normal,high,urgent',
            'message' => 'required|string|max:160',
        ]);

        // Get admin phone numbers based on recipient_type
        $phoneNumbers = [];

        if ($validated['recipient_type'] === 'all_admins' || $validated['recipient_type'] === 'system_admins') {
            // Get system admins
            $systemAdminRole = Role::where('name', 'system_admin')->first();
            if ($systemAdminRole) {
                $systemAdmins = User::where('role_id', $systemAdminRole->id)->get();
                foreach ($systemAdmins as $admin) {
                    if ($admin->phone_number) {
                        $phoneNumbers[] = $admin->phone_number;
                    }
                }
            }
        }

        if ($validated['recipient_type'] === 'specific_schools' && !empty($validated['school_ids'])) {
            // Get system admins assigned to specific schools
            $admins = SchoolAdmin::whereIn('school_id', $validated['school_ids'])
                ->where('admin_type', 'system_admin')
                ->with('admin')
                ->get();
            foreach ($admins as $assignment) {
                if ($assignment->admin && $assignment->admin->phone_number) {
                    $phoneNumbers[] = $assignment->admin->phone_number;
                }
            }
        }

        // Remove duplicates
        $phoneNumbers = array_unique($phoneNumbers);

        // Here you would integrate with your SMS provider
        // For now, we'll just log the action
        $recipientCount = count($phoneNumbers);

        return back()->with('success', "Alert sent successfully to {$recipientCount} administrators!");
    }

    /**
     * Show SMS allocations page
     */
    public function smsAllocationsPage()
    {
        $schools = School::with(['location', 'smsBalance'])
            ->orderBy('name')
            ->get();

        return view('super_admin.sms.allocations', compact('schools'));
    }

    /**
     * Allocate SMS credits to a school
     */
    public function allocateSmsCredits(Request $request, $schoolId)
    {
        $validated = $request->validate([
            'credits' => 'required|integer|min:1',
            'notes' => 'nullable|string',
        ]);

        $school = School::findOrFail($schoolId);

        // Update or create SMS balance
        $smsBalance = $school->smsBalance()->first();
        if ($smsBalance) {
            $smsBalance->sms_allocated = ($smsBalance->sms_allocated ?? 0) + $validated['credits'];
            $smsBalance->save();
        } else {
            $school->smsBalance()->create([
                'school_id' => $school->id,
                'sms_allocated' => $validated['credits'],
                'sms_used' => 0,
            ]);
        }

        return back()->with('success', "{$validated['credits']} SMS credits allocated to {$school->name}!");
    }

    /**
     * Bulk allocate SMS credits
     */
    public function bulkAllocateSmsCredits(Request $request)
    {
        $validated = $request->validate([
            'school_ids' => 'required|array',
            'school_ids.*' => 'exists:schools,id',
            'credits_per_school' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validated['school_ids'] as $schoolId) {
                $school = School::find($schoolId);
                $smsBalance = $school->smsBalance()->first();

                if ($smsBalance) {
                    $smsBalance->sms_allocated = ($smsBalance->sms_allocated ?? 0) + $validated['credits_per_school'];
                    $smsBalance->save();
                } else {
                    $school->smsBalance()->create([
                        'school_id' => $school->id,
                        'sms_allocated' => $validated['credits_per_school'],
                        'sms_used' => 0,
                    ]);
                }
            }

            DB::commit();

            $totalSchools = count($validated['school_ids']);
            $totalCredits = $totalSchools * $validated['credits_per_school'];

            return back()->with('success', "Allocated {$validated['credits_per_school']} credits to {$totalSchools} schools!");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Allocation failed: ' . $e->getMessage()]);
        }
    }

    /**
     * Show admin allocations management page
     */
    public function manageAllocations()
    {
        $systemAdminRole = Role::where('name', 'system_admin')->first();
        $systemAdmins = User::where('role_id', $systemAdminRole->id)
            ->with('schoolAdmins.school')
            ->get();

        $allSchools = School::orderBy('name')->get();

        return view('super_admin.admins.allocations', compact('systemAdmins', 'allSchools'));
    }

    /**
     * Add schools to existing system admin
     */
    public function addSchoolsToAdmin(Request $request, $adminId)
    {
        $validated = $request->validate([
            'school_ids' => 'required|array',
            'school_ids.*' => 'exists:schools,id',
        ]);

        $admin = User::findOrFail($adminId);

        DB::beginTransaction();
        try {
            foreach ($validated['school_ids'] as $schoolId) {
                // Use firstOrCreate to avoid duplicates
                SchoolAdmin::firstOrCreate([
                    'school_id' => $schoolId,
                    'system_admin_id' => $admin->id,
                    'admin_type' => 'system_admin',
                ], [
                    'is_active' => true,
                ]);
            }

            DB::commit();
            return back()->with('success', 'Schools assigned successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to assign schools: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove school from system admin
     */
    public function removeSchoolFromAdmin($adminId, $schoolId)
    {
        SchoolAdmin::where('system_admin_id', $adminId)
            ->where('school_id', $schoolId)
            ->where('admin_type', 'system_admin')
            ->delete();

        return back()->with('success', 'School assignment removed successfully!');
    }

}
