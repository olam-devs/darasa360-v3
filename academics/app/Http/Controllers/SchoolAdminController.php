<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\School;
use App\Models\SchoolAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SchoolAdminController extends Controller
{
    /**
     * Resolve school from session (tenant Admin is already scoped to their school via session)
     */
    private function getSchool()
    {
        $schoolCode = session('school_code');
        return School::where('school_code', $schoolCode)->first();
    }

    /**
     * Display school admin dashboard
     */
    public function dashboard()
    {
        $school = $this->getSchool();
        if (!$school) abort(403, 'School not found');

        $userId = session('user_id');

        // Staff & student counts
        $staffRoles = DB::connection('tenant')->table('school_roles')
            ->whereNotIn('name', ['Student', 'Admin'])
            ->pluck('id');

        $studentRole = DB::connection('tenant')->table('school_roles')
            ->where('name', 'Student')
            ->value('id');

        $totalStaff = DB::connection('tenant')->table('schoolUsers')
            ->whereIn('role_id', $staffRoles)
            ->count();

        $totalStudents = DB::connection('tenant')->table('students')->count();

        // Academic stats
        $totalClasses  = 0;
        $totalSubjects = 0;
        try {
            $totalClasses  = DB::connection('tenant')->table('classes')->count();
            $totalSubjects = DB::connection('tenant')->table('subjects')->count();
        } catch (\Exception $e) {}

        // Support tickets
        $openTickets = DB::connection('tenant')->table('support_tickets')
            ->whereIn('status', ['open', 'in_progress', 'pending'])
            ->count();

        $recentTickets = DB::connection('tenant')->table('support_tickets')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        // Upcoming events
        $upcomingEvents = collect();
        try {
            $upcomingEvents = DB::connection('tenant')->table('school_events')
                ->where('start_date', '>=', now())
                ->orderBy('start_date', 'asc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {}

        // Recent announcements
        $announcements = collect();
        try {
            $announcements = DB::connection('tenant')->table('announcements')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
        } catch (\Exception $e) {}

        // Finance summary
        $totalRevenue  = 0;
        $pendingFees   = 0;
        try {
            $totalRevenue = DB::connection('tenant')->table('payments')->sum('amount') ?? 0;
            $totalFees    = DB::connection('tenant')->table('fees')->sum('amount') ?? 0;
            $pendingFees  = $totalFees - $totalRevenue;
        } catch (\Exception $e) {}

        return view('school_admin.dashboard', compact(
            'school', 'totalStaff', 'totalStudents', 'totalClasses', 'totalSubjects',
            'openTickets', 'recentTickets', 'upcomingEvents', 'announcements',
            'totalRevenue', 'pendingFees'
        ));
    }

    /**
     * Display school overview
     */
    public function overview()
    {
        $school = $this->getSchool();
        if (!$school) abort(403, 'School not found');

        $teacherRoles = DB::connection('tenant')->table('school_roles')
            ->whereIn('name', ['Teacher', 'ClassTeacher', 'Headmaster'])
            ->pluck('id');

        $studentRole = DB::connection('tenant')->table('school_roles')
            ->where('name', 'Student')
            ->value('id');

        $stats = [
            'total_users'  => DB::connection('tenant')->table('schoolUsers')->count(),
            'teachers'     => DB::connection('tenant')->table('schoolUsers')->whereIn('role_id', $teacherRoles)->count(),
            'students'     => DB::connection('tenant')->table('schoolUsers')->where('role_id', $studentRole)->count(),
            'active_users' => DB::connection('tenant')->table('schoolUsers')->where('status', 'active')->count(),
        ];

        return view('school_admin.overview', compact('school', 'stats'));
    }

    /**
     * Display support tickets
     */
    public function support()
    {
        $userId = session('user_id');

        $tickets = DB::connection('tenant')->table('support_tickets')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $modules = DB::connection('tenant')->table('support_modules')->get();

        return view('school_admin.support', compact('tickets', 'modules'));
    }

    /**
     * Create new support ticket
     */
    public function createTicket(Request $request)
    {
        $validated = $request->validate([
            'subject'     => 'required|string|max:255',
            'module_id'   => 'required|integer',
            'sub_module'  => 'nullable|string',
            'description' => 'required|string',
            'priority'    => 'required|in:low,medium,high,critical',
        ]);

        $userId = session('user_id');

        DB::connection('tenant')->beginTransaction();
        try {
            $year       = date('Y');
            $lastTicket = DB::connection('tenant')->table('support_tickets')
                ->whereYear('created_at', $year)
                ->orderBy('id', 'desc')
                ->first();

            $number       = $lastTicket ? intval(substr($lastTicket->ticket_number, -4)) + 1 : 1;
            $ticketNumber = 'TKT-' . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);

            DB::connection('tenant')->table('support_tickets')->insertGetId([
                'ticket_number' => $ticketNumber,
                'subject'       => $validated['subject'],
                'module_id'     => $validated['module_id'],
                'sub_module'    => $validated['sub_module'],
                'description'   => $validated['description'],
                'user_id'       => $userId,
                'priority'      => $validated['priority'],
                'status'        => 'open',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);

            DB::connection('tenant')->commit();
            return back()->with('success', 'Support ticket created! Ticket #' . $ticketNumber);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return back()->withErrors(['error' => 'Failed to create ticket: ' . $e->getMessage()]);
        }
    }

    /**
     * View specific ticket
     */
    public function viewTicket($id)
    {
        $userId = session('user_id');

        $ticket = DB::connection('tenant')->table('support_tickets')
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();

        if (!$ticket) abort(404, 'Ticket not found');

        return view('school_admin.view_ticket', compact('ticket'));
    }

    /**
     * Escalate ticket to system admin
     */
    public function escalateTicket(Request $request, $id)
    {
        $validated = $request->validate([
            'escalation_reason' => 'required|string',
        ]);

        $userId     = session('user_id');
        $school     = $this->getSchool();

        if (!$school) {
            return back()->withErrors(['error' => 'School not found']);
        }

        // Find the system admin assigned to this school (master DB)
        $systemAdminAssignment = SchoolAdmin::where('school_id', $school->id)
            ->where('admin_type', 'system_admin')
            ->first();

        if (!$systemAdminAssignment) {
            return back()->withErrors(['error' => 'No system admin assigned to this school. Please contact support.']);
        }

        DB::connection('tenant')->beginTransaction();
        try {
            DB::connection('tenant')->table('support_tickets')
                ->where('id', $id)
                ->where('user_id', $userId)
                ->update([
                    'is_escalated'      => true,
                    'escalation_level'  => 'system_admin',
                    'escalated_at'      => now(),
                    'escalated_by'      => $userId,
                    'escalated_to'      => $systemAdminAssignment->system_admin_id,
                    'escalation_reason' => $validated['escalation_reason'],
                    'status'            => 'pending',
                    'updated_at'        => now(),
                ]);

            DB::connection('tenant')->table('support_ticket_comments')->insert([
                'ticket_id'  => $id,
                'user_id'    => $userId,
                'comment'    => 'Ticket escalated to System Admin. Reason: ' . $validated['escalation_reason'],
                'is_internal'=> false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::connection('tenant')->commit();
            return back()->with('success', 'Ticket escalated to System Admin successfully.');
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return back()->withErrors(['error' => 'Failed to escalate ticket: ' . $e->getMessage()]);
        }
    }

    /**
     * Display staff management
     */
    public function staffManagement()
    {
        $roles = DB::connection('tenant')->table('school_roles')
            ->whereNotIn('name', ['Student', 'Admin'])
            ->get();

        $staffRoles = $roles->pluck('id');

        $staff = DB::connection('tenant')->table('schoolUsers')
            ->leftJoin('school_roles', 'schoolUsers.role_id', '=', 'school_roles.id')
            ->whereIn('schoolUsers.role_id', $staffRoles)
            ->select('schoolUsers.*', 'school_roles.name as role_name')
            ->orderBy('schoolUsers.username')
            ->get();

        return view('school_admin.staff_management', compact('staff', 'roles'));
    }

    /**
     * Display system settings
     */
    public function systemSettings()
    {
        $school = $this->getSchool();
        if (!$school) abort(403, 'School not found');

        return view('school_admin.system_settings', compact('school'));
    }

    /**
     * Training resources
     */
    public function trainingResources()
    {
        return view('school_admin.training_resources');
    }

    /**
     * Create new staff member
     */
    public function createStaff(Request $request)
    {
        $validated = $request->validate([
            'username'        => 'required|string|max:255',
            'registration_no' => 'required|string|max:50',
            'email'           => 'nullable|email',
            'phone_number'    => 'nullable|string|max:20',
            'role_id'         => 'required|integer',
            'password'        => 'required|string|min:6',
            'gender'          => 'nullable|in:Male,Female',
        ]);

        DB::connection('tenant')->beginTransaction();
        try {
            $exists = DB::connection('tenant')->table('schoolUsers')
                ->where('registration_no', $validated['registration_no'])
                ->exists();

            if ($exists) {
                return back()->withErrors(['error' => 'Registration number already exists']);
            }

            $userId = DB::connection('tenant')->table('schoolUsers')->insertGetId([
                'username'        => $validated['username'],
                'registration_no' => $validated['registration_no'],
                'email'           => $validated['email'],
                'phone_number'    => $validated['phone_number'],
                'role_id'         => $validated['role_id'],
                'password'        => Hash::make($validated['password']),
                'gender'          => $validated['gender'] ?? 'Male',
                'status'          => 'active',
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);

            // Teacher/ClassTeacher staff also need a row in 'teachers' - that's
            // the table the "Assign Teachers" page's dropdown reads from
            // (Teacher::on('tenant')->get() in TeacherController). Without
            // this, a Teacher or ClassTeacher created here could never be
            // assigned to a class - the dropdown would just be empty. Look
            // role ids up by name rather than hardcoding them; this codebase
            // has repeatedly hardcoded the wrong numbers for these two roles.
            $teacherRoleIds = DB::connection('tenant')->table('school_roles')
                ->whereIn('name', ['Teacher', 'ClassTeacher'])
                ->pluck('id');

            if ($teacherRoleIds->contains($validated['role_id'])) {
                DB::connection('tenant')->table('teachers')->insert([
                    'user_id'    => $userId,
                    'name'       => $validated['username'],
                    'phone'      => $validated['phone_number'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::connection('tenant')->commit();
            return back()->with('success', 'Staff member created successfully!');
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return back()->withErrors(['error' => 'Failed to create staff: ' . $e->getMessage()]);
        }
    }

    /**
     * View staff member
     */
    public function viewStaff($id)
    {
        $staff = DB::connection('tenant')->table('schoolUsers')
            ->where('id', $id)
            ->first();

        if (!$staff) abort(404, 'Staff member not found');

        $role = DB::connection('tenant')->table('school_roles')
            ->where('id', $staff->role_id)
            ->first();

        $staff->role_name = $role->name ?? 'Unknown';

        return view('school_admin.view_staff', compact('staff'));
    }

    /**
     * Update staff member
     */
    public function updateStaff(Request $request, $id)
    {
        $validated = $request->validate([
            'username'     => 'required|string|max:255',
            'email'        => 'nullable|email',
            'phone_number' => 'nullable|string|max:20',
            'role_id'      => 'required|integer',
            'gender'       => 'nullable|in:Male,Female',
        ]);

        DB::connection('tenant')->beginTransaction();
        try {
            DB::connection('tenant')->table('schoolUsers')
                ->where('id', $id)
                ->update([
                    'username'     => $validated['username'],
                    'email'        => $validated['email'],
                    'phone_number' => $validated['phone_number'],
                    'role_id'      => $validated['role_id'],
                    'gender'       => $validated['gender'],
                    'updated_at'   => now(),
                ]);

            DB::connection('tenant')->commit();
            return back()->with('success', 'Staff member updated successfully!');
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return back()->withErrors(['error' => 'Failed to update staff: ' . $e->getMessage()]);
        }
    }

    /**
     * Toggle staff active/disabled status
     */
    public function toggleStaffStatus($id)
    {
        DB::connection('tenant')->beginTransaction();
        try {
            $staff = DB::connection('tenant')->table('schoolUsers')->where('id', $id)->first();

            if (!$staff) {
                return back()->withErrors(['error' => 'Staff member not found']);
            }

            $newStatus = $staff->status === 'active' ? 'disabled' : 'active';

            DB::connection('tenant')->table('schoolUsers')
                ->where('id', $id)
                ->update(['status' => $newStatus, 'updated_at' => now()]);

            DB::connection('tenant')->commit();
            return back()->with('success', 'Staff status updated successfully!');
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return back()->withErrors(['error' => 'Failed to update status: ' . $e->getMessage()]);
        }
    }

    /**
     * Reset staff password
     */
    public function resetStaffPassword(Request $request, $id)
    {
        $validated = $request->validate([
            'new_password' => 'required|string|min:6',
        ]);

        DB::connection('tenant')->beginTransaction();
        try {
            DB::connection('tenant')->table('schoolUsers')
                ->where('id', $id)
                ->update(['password' => Hash::make($validated['new_password']), 'updated_at' => now()]);

            DB::connection('tenant')->commit();
            return back()->with('success', 'Password reset successfully!');
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return back()->withErrors(['error' => 'Failed to reset password: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete staff member
     */
    public function deleteStaff($id)
    {
        DB::connection('tenant')->beginTransaction();
        try {
            DB::connection('tenant')->table('schoolUsers')->where('id', $id)->delete();

            DB::connection('tenant')->commit();
            return back()->with('success', 'Staff member deleted successfully!');
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return back()->withErrors(['error' => 'Failed to delete staff: ' . $e->getMessage()]);
        }
    }

    /**
     * Export students for finance
     */
    public function exportStudentsForFinance(Request $request)
    {
        $classId = $request->input('class_id');
        $filters = [
            'gender' => $request->input('gender'),
            'search' => $request->input('search'),
        ];

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\StudentsFinanceExport($classId, $filters),
            'students_finance_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }
}
