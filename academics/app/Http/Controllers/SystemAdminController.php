<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use App\Models\User;
use App\Models\SchoolUser;
use App\Models\SchoolAdmin;
use App\Models\SupportTicket;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SystemAdminController extends Controller
{
    /**
     * Display system admin dashboard
     */
    public function dashboard()
    {
        $systemAdmin = Auth::user();

        // Get schools assigned to this system admin
        $assignedSchools = SchoolAdmin::where('system_admin_id', $systemAdmin->id)
            ->where('admin_type', 'system_admin')
            ->with('school')
            ->get()
            ->pluck('school');

        $totalSchools = $assignedSchools->count();

        // Get escalated support tickets assigned to this system admin
        // Support tickets are in tenant databases, so we need to query each school
        $escalatedTickets = 0;
        $recentTickets = collect();

        // Calculate total users across assigned schools
        $totalUsers = 0;
        foreach ($assignedSchools as $school) {
            // Configure tenant connection for this school
            $this->configureTenantConnection($school->database_url);

            // Count users in this school's tenant database
            $totalUsers += DB::connection('tenant')->table('schoolUsers')->count();

            // Count escalated tickets for this school
            $schoolTickets = DB::connection('tenant')->table('support_tickets')
                ->where('escalated_to', $systemAdmin->id)
                ->where('is_escalated', true)
                ->whereIn('status', ['open', 'in_progress', 'pending'])
                ->count();

            $escalatedTickets += $schoolTickets;

            // Get recent tickets from this school
            $schoolRecentTickets = DB::connection('tenant')->table('support_tickets')
                ->where('escalated_to', $systemAdmin->id)
                ->where('is_escalated', true)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();

            foreach ($schoolRecentTickets as $ticket) {
                $ticket->school_name = $school->name;
            }

            $recentTickets = $recentTickets->merge($schoolRecentTickets);
        }

        // Sort and limit recent tickets
        $recentTickets = $recentTickets->sortByDesc('created_at')->take(10);

        return view('system_admin.dashboard', compact(
            'assignedSchools',
            'totalSchools',
            'totalUsers',
            'escalatedTickets',
            'recentTickets'
        ));
    }

    /**
     * Configure tenant database connection
     */
    private function configureTenantConnection($dbUrl)
    {
        if (!$dbUrl) {
            return;
        }

        // $dbUrl is actually just a bare database name in practice (schools.database_url
        // has never stored a real mysql:// URL despite this method's original parse_url()
        // call implying otherwise), so look the school up by that name directly.
        $school = \App\Models\School::where('database_url', $dbUrl)->first();
        if ($school) {
            $school->useAsTenant();
        }
    }

    /**
     * Display assigned schools
     */
    public function mySchools()
    {
        $systemAdmin = Auth::user();

        $schools = SchoolAdmin::where('system_admin_id', $systemAdmin->id)
            ->where('admin_type', 'system_admin')
            ->with(['school.location', 'school.administrator'])
            ->get()
            ->pluck('school');

        return view('system_admin.my_schools', compact('schools'));
    }

    /**
     * View specific school details
     */
    public function viewSchool($id)
    {
        $systemAdmin = Auth::user();

        // Verify this system admin has access to this school
        $hasAccess = SchoolAdmin::where('system_admin_id', $systemAdmin->id)
            ->where('school_id', $id)
            ->where('admin_type', 'system_admin')
            ->exists();

        if (!$hasAccess) {
            abort(403, 'Unauthorized access to this school');
        }

        $school = School::with([
            'administrator',
            'location'
        ])->findOrFail($id);

        // Configure tenant connection for this school
        $this->configureTenantConnection($school->database_url);

        // Get school statistics from tenant database
        $school->total_users = DB::connection('tenant')->table('schoolUsers')->count();

        // Get role IDs for different user types
        $teacherRoles = DB::connection('tenant')->table('school_roles')
            ->whereIn('name', ['teacher', 'class_teacher', 'head_teacher'])
            ->pluck('id');

        $studentRole = DB::connection('tenant')->table('school_roles')
            ->where('name', 'student')
            ->value('id');

        $school->total_teachers = DB::connection('tenant')->table('schoolUsers')
            ->whereIn('role_id', $teacherRoles)
            ->count();

        $school->total_students = DB::connection('tenant')->table('schoolUsers')
            ->where('role_id', $studentRole)
            ->count();

        return view('system_admin.view_school', compact('school'));
    }

    /**
     * Display support tickets escalated to this system admin
     */
    public function supportTickets()
    {
        $systemAdmin = Auth::user();

        // Get all schools assigned to this system admin
        $assignedSchools = SchoolAdmin::where('system_admin_id', $systemAdmin->id)
            ->where('admin_type', 'system_admin')
            ->with('school')
            ->get();

        $allTickets = collect();

        // Query tickets from each school's tenant database
        foreach ($assignedSchools as $assignment) {
            $school = $assignment->school;
            $this->configureTenantConnection($school->database_url);

            $schoolTickets = DB::connection('tenant')->table('support_tickets')
                ->where('escalated_to', $systemAdmin->id)
                ->where('is_escalated', true)
                ->orderBy('created_at', 'desc')
                ->get();

            foreach ($schoolTickets as $ticket) {
                $ticket->school_name = $school->name;
                $ticket->school_id = $school->id;
            }

            $allTickets = $allTickets->merge($schoolTickets);
        }

        // Sort all tickets by creation date
        $tickets = $allTickets->sortByDesc('created_at')->values();

        return view('system_admin.support_tickets', compact('tickets'));
    }

    /**
     * View specific support ticket
     */
    public function viewTicket($id)
    {
        $systemAdmin = Auth::user();

        // Find which school this ticket belongs to
        $assignedSchools = SchoolAdmin::where('system_admin_id', $systemAdmin->id)
            ->where('admin_type', 'system_admin')
            ->with('school')
            ->get();

        $ticket = null;
        $school = null;

        foreach ($assignedSchools as $assignment) {
            $this->configureTenantConnection($assignment->school->database_url);

            $foundTicket = DB::connection('tenant')->table('support_tickets')
                ->where('id', $id)
                ->where('escalated_to', $systemAdmin->id)
                ->where('is_escalated', true)
                ->first();

            if ($foundTicket) {
                $ticket = $foundTicket;
                $school = $assignment->school;
                break;
            }
        }

        if (!$ticket) {
            abort(404, 'Ticket not found or not accessible');
        }

        $ticket->school_name = $school->name;

        return view('system_admin.view_ticket', compact('ticket', 'school'));
    }

    /**
     * Update support ticket status
     */
    public function updateTicketStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,in_progress,pending,resolved,closed',
            'comment' => 'nullable|string',
            'school_id' => 'required|exists:schools,id'
        ]);

        $systemAdmin = Auth::user();

        // Get the school and configure tenant connection
        $school = School::findOrFail($validated['school_id']);
        $this->configureTenantConnection($school->database_url);

        DB::connection('tenant')->beginTransaction();
        try {
            $updateData = [
                'status' => $validated['status'],
                'updated_at' => now()
            ];

            if ($validated['status'] === 'resolved') {
                $updateData['resolved_by'] = $systemAdmin->id;
                $updateData['resolved_at'] = now();
            }

            DB::connection('tenant')->table('support_tickets')
                ->where('id', $id)
                ->update($updateData);

            if (!empty($validated['comment'])) {
                DB::connection('tenant')->table('support_ticket_comments')->insert([
                    'ticket_id' => $id,
                    'user_id' => $systemAdmin->id,
                    'comment' => $validated['comment'],
                    'is_internal' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::connection('tenant')->commit();
            return back()->with('success', 'Ticket status updated successfully');
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return back()->withErrors(['error' => 'Failed to update ticket: ' . $e->getMessage()]);
        }
    }

    /**
     * Escalate ticket to super admin
     */
    public function escalateToSuperAdmin(Request $request, $id)
    {
        $validated = $request->validate([
            'escalation_reason' => 'required|string',
            'super_admin_id' => 'required|exists:users,id',
            'school_id' => 'required|exists:schools,id'
        ]);

        $systemAdmin = Auth::user();

        // Get the school and configure tenant connection
        $school = School::findOrFail($validated['school_id']);
        $this->configureTenantConnection($school->database_url);

        DB::connection('tenant')->beginTransaction();
        try {
            DB::connection('tenant')->table('support_tickets')
                ->where('id', $id)
                ->where('escalated_to', $systemAdmin->id)
                ->update([
                    'is_escalated' => true,
                    'escalation_level' => 'super_admin',
                    'escalated_at' => now(),
                    'escalated_by' => $systemAdmin->id,
                    'escalated_to' => $validated['super_admin_id'],
                    'escalation_reason' => $validated['escalation_reason'],
                    'status' => 'pending',
                    'updated_at' => now(),
                ]);

            DB::connection('tenant')->table('support_ticket_comments')->insert([
                'ticket_id' => $id,
                'user_id' => $systemAdmin->id,
                'comment' => "Ticket escalated to Super Admin. Reason: " . $validated['escalation_reason'],
                'is_internal' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::connection('tenant')->commit();
            return back()->with('success', 'Ticket escalated to Super Admin successfully');
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return back()->withErrors(['error' => 'Failed to escalate ticket: ' . $e->getMessage()]);
        }
    }


    /**
     * Display reports for assigned schools
     */
    public function reports()
    {
        $systemAdmin = Auth::user();

        $schoolIds = SchoolAdmin::where('system_admin_id', $systemAdmin->id)
            ->where('admin_type', 'system_admin')
            ->pluck('school_id');

        $schools = School::whereIn('id', $schoolIds)
            ->with('location')
            ->get();

        // Generate statistics for each school
        $schoolStats = [];
        foreach ($schools as $school) {
            // Configure tenant connection for this school
            $this->configureTenantConnection($school->database_url);

            $schoolStats[] = [
                'school' => $school,
                'total_users' => DB::connection('tenant')->table('schoolUsers')->count(),
                'active_tickets' => DB::connection('tenant')->table('support_tickets')
                    ->whereIn('status', ['open', 'in_progress', 'pending'])
                    ->count(),
                'resolved_tickets' => DB::connection('tenant')->table('support_tickets')
                    ->where('status', 'resolved')
                    ->count(),
            ];
        }

        return view('system_admin.reports', compact('schoolStats'));
    }

    /**
     * Display password management page for all users in assigned schools
     */
    public function passwordsIndex(Request $request)
    {
        $systemAdmin = Auth::user();
        $search = $request->input('search');

        // Get all schools assigned to this system admin
        $assignedSchools = SchoolAdmin::where('system_admin_id', $systemAdmin->id)
            ->where('admin_type', 'system_admin')
            ->with('school')
            ->get()
            ->pluck('school');

        // Collect all users from central database
        $centralUsers = User::with('school')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('registration_no', 'like', "%{$search}%")
                        ->orWhere('phone_number', 'like', "%{$search}%");
                });
            })
            ->whereIn('school_id', $assignedSchools->pluck('id'))
            ->get();

        // Collect all users from tenant databases
        $tenantUsers = collect();
        foreach ($assignedSchools as $school) {
            $this->configureTenantConnection($school->database_url);

            $schoolUsers = DB::connection('tenant')->table('schoolUsers')
                ->when($search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('username', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('registration_no', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
                })
                ->get();

            foreach ($schoolUsers as $user) {
                $user->school_name = $school->name;
                $user->school_id = $school->id;
                $user->database_url = $school->database_url;
                $user->user_db = 'tenant';
            }

            $tenantUsers = $tenantUsers->merge($schoolUsers);
        }

        // Merge central and tenant users
        $allUsers = $centralUsers->map(function ($user) {
            return (object) [
                'id' => $user->id,
                'username' => $user->username,
                'registration_no' => $user->registration_no,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'school_name' => $user->school->name ?? 'N/A',
                'user_db' => 'central',
                'database_url' => null,
            ];
        })->merge($tenantUsers);

        // Paginate the results manually
        $perPage = 20;
        $currentPage = $request->input('page', 1);
        $offset = ($currentPage - 1) * $perPage;

        $paginatedUsers = $allUsers->slice($offset, $perPage);
        $total = $allUsers->count();

        return view('content.dashboard.super_admin.manage_passwords', [
            'users' => new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedUsers,
                $total,
                $perPage,
                $currentPage,
                ['path' => $request->url(), 'query' => $request->query()]
            ),
            'search' => $search,
            'tenantUser' => null,
            'matchedSchool' => null,
        ]);
    }

    /**
     * Reset user password
     */
    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required',
            'user_db' => 'required|in:central,tenant',
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $systemAdmin = Auth::user();

        try {
            if ($validated['user_db'] === 'central') {
                // Update password in central database
                $user = User::find($validated['user_id']);

                if (!$user) {
                    return back()->withErrors(['error' => 'User not found']);
                }

                // Verify system admin has access to this user's school
                $hasAccess = SchoolAdmin::where('system_admin_id', $systemAdmin->id)
                    ->where('school_id', $user->school_id)
                    ->exists();

                if (!$hasAccess) {
                    return back()->withErrors(['error' => 'Unauthorized access']);
                }

                $user->password = \Illuminate\Support\Facades\Hash::make($validated['new_password']);
                $user->save();
            } else {
                // Update password in tenant database
                $tenantDb = $request->input('tenant_db');
                $this->configureTenantConnection($tenantDb);

                DB::connection('tenant')->table('schoolUsers')
                    ->where('registration_no', $validated['user_id'])
                    ->update([
                        'password' => bcrypt($validated['new_password']),
                        'updated_at' => now(),
                    ]);
            }

            return back()->with('success', 'Password reset successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to reset password: ' . $e->getMessage()]);
        }
    }

    // ── Staff Management (for assigned schools) ──────────────────────────────

    private function getAssignedSchoolOrFail(int $schoolId): School
    {
        $hasAccess = SchoolAdmin::where('system_admin_id', Auth::id())
            ->where('school_id', $schoolId)
            ->where('admin_type', 'system_admin')
            ->exists();

        if (!$hasAccess) abort(403, 'You are not assigned to this school.');

        return School::findOrFail($schoolId);
    }

    private function tenantForSchool(School $school): void
    {
        config([
            'database.connections.tenant' => array_merge(
                config('database.connections.mysql'),
                ['database' => $school->database_url]
            )
        ]);
        DB::purge('tenant');
    }

    public function schoolStaff(int $id)
    {
        $school = $this->getAssignedSchoolOrFail($id);
        $this->tenantForSchool($school);

        $roles = DB::connection('tenant')->table('school_roles')
            ->whereNotIn('name', ['Student', 'Accountant'])->get();

        $staff = DB::connection('tenant')->table('schoolUsers')
            ->leftJoin('school_roles', 'schoolUsers.role_id', '=', 'school_roles.id')
            ->whereNotIn('school_roles.name', ['Student'])
            ->select('schoolUsers.*', 'school_roles.name as role_name')
            ->orderBy('schoolUsers.username')->get();

        return view('system_admin.school_staff', compact('school', 'staff', 'roles'));
    }

    public function createStaff(Request $request, int $id)
    {
        $school = $this->getAssignedSchoolOrFail($id);
        $this->tenantForSchool($school);

        $validated = $request->validate([
            'username'        => 'required|string|max:255',
            'registration_no' => 'required|string|max:50',
            'email'           => 'nullable|email',
            'phone_number'    => 'nullable|string|max:20',
            'role_id'         => 'required|integer',
            'password'        => 'required|string|min:6',
            'gender'          => 'nullable|in:Male,Female',
        ]);

        if (DB::connection('tenant')->table('schoolUsers')->where('registration_no', $validated['registration_no'])->exists()) {
            return back()->withErrors(['error' => 'Registration number already exists.']);
        }

        DB::connection('tenant')->table('schoolUsers')->insert([
            'username'        => $validated['username'],
            'registration_no' => $validated['registration_no'],
            'email'           => $validated['email'],
            'phone_number'    => $validated['phone_number'],
            'role_id'         => $validated['role_id'],
            'password'        => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'gender'          => $validated['gender'] ?? 'Male',
            'status'          => 'active',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        return back()->with('success', 'Staff member created successfully!');
    }

    public function updateStaff(Request $request, int $id, int $staffId)
    {
        $school = $this->getAssignedSchoolOrFail($id);
        $this->tenantForSchool($school);

        $validated = $request->validate([
            'username'     => 'required|string|max:255',
            'email'        => 'nullable|email',
            'phone_number' => 'nullable|string|max:20',
            'role_id'      => 'required|integer',
            'gender'       => 'nullable|in:Male,Female',
        ]);

        DB::connection('tenant')->table('schoolUsers')->where('id', $staffId)->update(array_merge($validated, ['updated_at' => now()]));

        return back()->with('success', 'Staff updated successfully!');
    }

    public function deleteStaff(int $id, int $staffId)
    {
        $school = $this->getAssignedSchoolOrFail($id);
        $this->tenantForSchool($school);
        DB::connection('tenant')->table('schoolUsers')->where('id', $staffId)->delete();
        return back()->with('success', 'Staff member deleted.');
    }

    public function toggleStaffStatus(int $id, int $staffId)
    {
        $school = $this->getAssignedSchoolOrFail($id);
        $this->tenantForSchool($school);

        $user = DB::connection('tenant')->table('schoolUsers')->where('id', $staffId)->first();
        if (!$user) abort(404);

        $newStatus = $user->status === 'active' ? 'disabled' : 'active';
        DB::connection('tenant')->table('schoolUsers')->where('id', $staffId)->update(['status' => $newStatus, 'updated_at' => now()]);

        return back()->with('success', 'Status updated.');
    }

    public function resetStaffPassword(Request $request, int $id, int $staffId)
    {
        $school = $this->getAssignedSchoolOrFail($id);
        $this->tenantForSchool($school);

        $validated = $request->validate(['password' => 'required|string|min:6|confirmed']);

        DB::connection('tenant')->table('schoolUsers')->where('id', $staffId)->update([
            'password'   => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Password reset successfully.');
    }

    /**
     * Export students data for finance system
     */
    public function exportStudentsForFinance(Request $request)
    {
        $schoolId = $request->input('school_id');

        if (!$schoolId) {
            return back()->withErrors(['error' => 'School ID is required']);
        }

        // Get school and configure tenant connection
        $school = \App\Models\School::find($schoolId);
        if (!$school) {
            return back()->withErrors(['error' => 'School not found']);
        }

        $this->configureTenantConnection($school->database_url);

        $classId = $request->input('class_id');
        $filters = [
            'gender' => $request->input('gender'),
            'search' => $request->input('search'),
        ];

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StudentsFinanceExport($classId, $filters),
            'students_finance_' . $school->name . '_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }
}
