<?php

namespace App\Http\Controllers;

use App\Helpers\RegistrationHelper;
use App\Models\School;
use App\Models\SchoolRole;
use App\Models\SchoolUser;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class OwnerController extends Controller
{

  public function updateUserStatus(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    // Get tenant DB name from session
    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found in session.');
    }

    // Configure dynamic tenant connection
    Config::set('database.connections.tenant', [
      'driver' => 'mysql',
      'host' => env('DB_HOST', '127.0.0.1'),
      'port' => env('DB_PORT', '3306'),
      'database' => $tenantDb,
      'username' => env('DB_USERNAME', 'root'),
      'password' => env('DB_PASSWORD', ''),
      'charset' => 'utf8mb4',
      'collation' => 'utf8mb4_unicode_ci',
      'prefix' => '',
      'strict' => true,
    ]);

    DB::purge('tenant');
    DB::reconnect('tenant');

    // Fetch the school user by ID
    $user = DB::connection('tenant')->table('schoolUsers')->where('id', $id)->first();

    if (!$user) {
      return back()->with('error', 'Staff not found.');
    }

    // Toggle status if requested
    if ($request->has('toggle_status')) {
      $newStatus = $user->status === 'active' ? 'disabled' : 'active';

      DB::connection('tenant')->table('schoolUsers')->where('id', $id)->update([
        'status' => $newStatus,
      ]);

      return redirect()->back()->with('success', 'Staff status updated to ' . ucfirst($newStatus) . '.');
    }

    return back()->with('error', 'Invalid request.');
  }



  public function ownerDashboard(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return response()->json(['error' => 'Unauthenticated'], 401);

    $schoolDb = session('school_db');
    $userId = session('user_id');

    if (!$schoolDb) return response()->json(['error' => 'Invalid school database'], 400);

    $this->configureTenantConnection($schoolDb);

    $user = DB::connection('tenant')->table('schoolUsers')->where('id', $userId)->first();
    $username = $user ? $user->username : 'User';

    $teachersCount = DB::connection('tenant')->table('schoolUsers')->whereIn('role_id', [5, 6])->count();
    $studentsCount = DB::connection('tenant')->table('schoolUsers')->where('role_id', 7)->count();
    $classesCount = DB::connection('tenant')->table('classes')->count();
    $smsCount = DB::connection('tenant')->table('sms_messages')->count();
    $announcementsCount = DB::connection('tenant')->table('announcements')->count();
    $subjectsCount = DB::connection('tenant')->table('subjects')->count();

    $dashboardData = [
      'username' => $username,
      'teachersCount' => $teachersCount,
      'studentsCount' => $studentsCount,
      'classesCount' => $classesCount,
      'smsCount' => $smsCount,
      'announcementsCount' => $announcementsCount,
      'subjectsCount' => $subjectsCount,
    ];

    return view('content.dashboard.owner.home', compact('dashboardData'));
  }



  public function addStaff(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    // Get tenant DB name and school info from session
    $tenantDb = session('school_db');
    // Fetch package from central schools table using school code
    $schoolCode = session('school_code') ?? '000';
    $package = DB::connection('mysql')->table('schools')
      ->where('school_code', $schoolCode)
      ->value('package') ?? 'premium'; // default to 'premium' if not found


    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found in session.');
    }

    // Set tenant DB dynamically
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Validation rules
    $rules = [
      'name' => 'required|string|max:255',
      'email' => 'required|email|unique:tenant.schoolUsers,email',
      'phone' => ['required', 'string', 'regex:/^0\d{9}$/'],
      'role_id' => ['required', Rule::exists('tenant.school_roles', 'id')],
      'password' => 'required|string|confirmed|min:6',
      'gender' => 'required'
    ];

    // If Class Teacher, validate class ID too
    if ($request->input('role_id') == 5) {
      $rules['class_id'] = ['required', Rule::exists('tenant.classes', 'id')];
    }

    $request->validate($rules);

    $roleId = $request->input('role_id');

    // Define role limits per package
    $roleLimits = [
      'core' => [
        3 => 1, // Headmaster
        8 => 1, // Academic
      ],
      'standard' => [
        3 => 1,
        4 => 5,
      ],
      'premium' => [
        3 => 2,
        4 => 10,
      ]
    ];

    // Enforce role limits if set for current package
    if (isset($roleLimits[$package][$roleId])) {
      $limit = $roleLimits[$package][$roleId];

      $currentCount = DB::connection('tenant')->table('schoolUsers')
        ->where('role_id', $roleId)
        ->count();

      if ($currentCount >= $limit) {
        $roleNames = [
          3 => 'Headmaster',
          4 => 'Academic Staff',
        ];
        $roleName = $roleNames[$roleId] ?? 'staff with this role';
        return back()->with('error', "Maximum number of {$roleName} for {$package} package is {$limit}.");
      }
    }

    // Generate registration number
    try {
      $registrationNo = RegistrationHelper::generateRegistrationNo($roleId, $schoolCode);
    } catch (\Exception $e) {
      return back()->with('error', $e->getMessage());
    }

    // Insert staff into schoolusers
    $userId = DB::connection('tenant')->table('schoolUsers')->insertGetId([
      'registration_no' => $registrationNo,
      'username' => $request->name,
      'email' => $request->email,
      'phone_number' => $request->phone,
      'role_id' => $roleId,
      'password' => Hash::make($request->password),
      'created_at' => now(),
      'updated_at' => now(),
      'gender' => $request->gender

    ]);

    // If role is Class Teacher or Teacher, insert into teachers
    if (in_array($roleId, [5, 6])) {
      DB::connection('tenant')->table('teachers')->insert([
        'user_id' => $userId,
        'name' => $request->name,
        'phone' => $request->phone,
        'created_at' => now(),
        'updated_at' => now(),
      ]);
    }

    // If Class Teacher, update class_teacher_id in classes table
    if ($roleId == 5) {
      $classId = $request->input('class_id');
      DB::connection('tenant')->table('classes')
        ->where('id', $classId)
        ->update(['class_teacher_id' => $userId]);
    }

    return redirect()->back()
      ->with('success', "Staff created successfully with registration number: $registrationNo")
      ->with('form', 'add');
  }








  public function manageStaff(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      abort(500, 'Tenant database not set in session.');
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Comments here have always said "exclude id 6" (Student), but the code
    // excluded 7 ("Academic" per RoleSeeder's real order) instead - so actual
    // students showed up in Manage Staff while Academic-role staff were
    // wrongly hidden from it. Look the id up by name instead of hardcoding
    // either number.
    $studentRoleId = SchoolRole::where('name', 'Student')->value('id');

    // Fetch staff users with role_id >= 3, excluding students
    $staffs = SchoolUser::on('tenant')
      ->where('role_id', '>=', 3)
      ->where('role_id', '!=', $studentRoleId)
      ->with('role_name')  // if role relationship is defined
      ->paginate(5);

    // Fetch all roles with id >= 3, excluding Student
    $roles = DB::connection('tenant')
      ->table('school_roles')
      ->where('id', '>=', 3)
      ->where('id', '!=', $studentRoleId)
      ->get();

    return view('content.dashboard.owner.manage_staff', compact('staffs', 'roles'));
  }




  public function deleteStaff(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    // Get tenant DB name from session
    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return redirect()->back()->with('error', 'Tenant database not found in session.');
    }

    // Set tenant DB dynamically
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Try to find the user in tenant DB
    $user = DB::connection('tenant')->table('schoolUsers')->where('id', $id)->first();

    if (!$user) {
      return redirect()->back()->with('error', 'User not found in tenant database.');
    }

    // Delete the user
    DB::connection('tenant')->table('schoolUsers')->where('id', $id)->delete();

    return redirect()->back()->with('success', 'Staff deleted successfully.');
  }



  public function updateStaff(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found in session.');
    }

    $package = School::where('database_url', $tenantDb)->value('package') ?? 'premium';


    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $staff = SchoolUser::on('tenant')->findOrFail($id);

    $rules = [
      'name' => 'required|string|max:255',
      'email' => "required|email|unique:tenant.schoolUsers,email,{$id}",
      'phone' => ['required', 'string', 'regex:/^0\d{9}$/'],
      'role_id' => 'required|exists:tenant.school_roles,id',
      'password' => 'nullable|string|confirmed|min:6',
      'gender' => 'required'
    ];

    if ($request->role_id == 5) {
      $rules['class_id'] = 'required|exists:tenant.classes,id';
    }

    $request->validate($rules);

    // Check if role is changing and enforce package limit
    $newRoleId = $request->role_id;
    $oldRoleId = $staff->role_id;

    $roleLimits = [
      'core' => [3 => 1, 8 => 1],
      'standard' => [3 => 1, 4 => 5],
      'premium' => [3 => 2, 4 => 10],
    ];

    if ($newRoleId != $oldRoleId && isset($roleLimits[$package][$newRoleId])) {
      $limit = $roleLimits[$package][$newRoleId];
      $currentCount = DB::connection('tenant')->table('schoolUsers')
        ->where('role_id', $newRoleId)
        ->count();

      if ($currentCount >= $limit) {
        $roleNames = [3 => 'Headmaster', 4 => 'Academic Staff'];
        $roleName = $roleNames[$newRoleId] ?? 'staff with this role';
        return back()->with('error', "Cannot assign role. Limit reached for {$roleName} in {$package} package.");
      }
    }

    $staff->username = $request->name;
    $staff->email = $request->email;
    $staff->phone_number = $request->phone;
    $staff->role_id = $newRoleId;
    $staff->gender = $request->gender;

    if ($request->filled('password')) {
      $staff->password = Hash::make($request->password);
    }

    $staff->save();

    if ($newRoleId == 5) {
      $classId = $request->class_id;

      DB::connection('tenant')->table('classes')
        ->where('class_teacher_id', $staff->id)
        ->update(['class_teacher_id' => null]);

      DB::connection('tenant')->table('classes')
        ->where('id', $classId)
        ->update(['class_teacher_id' => $staff->id]);
    } else {
      DB::connection('tenant')->table('classes')
        ->where('class_teacher_id', $staff->id)
        ->update(['class_teacher_id' => null]);
    }

    return redirect()->back()->with('success', 'Staff updated successfully.');
  }



  public function viewStaffs(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found in session.');
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $query = SchoolUser::on('tenant')
      ->with('role_name') // Eager load roles
      ->leftJoin('school_roles', 'schoolUsers.role_id', '=', 'school_roles.id') // Join for filtering
      ->select('schoolUsers.*'); // Ensure only SchoolUser columns are selected

    if ($request->filled('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('schoolUsers.username', 'like', "%$search%")
          ->orWhere('schoolUsers.registration_no', 'like', "%$search%")
          ->orWhere('schoolUsers.email', 'like', "%$search%")
          ->orWhere('schoolUsers.phone_number', 'like', "%$search%")
          ->orWhere('school_roles.name', 'like', "%$search%");
      });
    }

    if ($request->filled('role_id')) {
      $query->where('schoolUsers.role_id', $request->role_id);
    }

    $staffs = $query->orderBy('schoolUsers.id', 'desc')
      ->paginate(5)
      ->appends($request->query());

    $roles = DB::connection('tenant')->table('school_roles')->where('id', '>=', 3)->get();

    return view('content.dashboard.owner.manage_staff', compact('staffs', 'roles'));
  }

  public function toggleStatus($id)
  {
    $staff = Staff::findOrFail($id);

    // You can add authorization here

    $staff->status = ($staff->status === 'active') ? 'inactive' : 'active';
    $staff->save();

    return redirect()->back()->with('success', 'Staff status updated successfully.');
  }

  public function getStaffInfo($id)
  {
    $staff = Staff::with('role_name')->find($id);

    if (!$staff) {
      return response()->json(['error' => 'Staff not found'], 404);
    }

    return response()->json([
      'username' => $staff->username,
      'registration_no' => $staff->registration_no,
      'email' => $staff->email,
      'phone_number' => $staff->phone_number,
      'role_name' => ucfirst($staff->role_name->name),
      'status' => ucfirst($staff->status),
    ]);
  }



  public function getClasses(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return response()->json([], 400);
    }

    // Set tenant database connection dynamically
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $classes = DB::connection('tenant')->table('classes')->select('id', 'name')->get();

    return response()->json($classes);
  }


  public function schoolGrades(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found in session.');
    }

    // Configure tenant DB
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Fetch grades
    $grades = DB::connection('tenant')
      ->table('grades')
      ->orderBy('min_mark', 'asc')
      ->get();

    // You can keep the existing code if you still want to list staff as well.
    // (Optional: if you're only showing grades, this can be removed.)
    $query = SchoolUser::on('tenant')
      ->with('role_name')
      ->leftJoin('school_roles', 'schoolUsers.role_id', '=', 'school_roles.id')
      ->select('schoolUsers.*');

    if ($request->filled('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->where('schoolUsers.username', 'like', "%$search%")
          ->orWhere('schoolUsers.registration_no', 'like', "%$search%")
          ->orWhere('schoolUsers.email', 'like', "%$search%")
          ->orWhere('schoolUsers.phone_number', 'like', "%$search%")
          ->orWhere('school_roles.name', 'like', "%$search%");
      });
    }

    if ($request->filled('role_id')) {
      $query->where('schoolUsers.role_id', $request->role_id);
    }

    $staffs = $query->orderBy('schoolUsers.id', 'desc')
      ->paginate(5)
      ->appends($request->query());

    $roles = DB::connection('tenant')->table('school_roles')->where('id', '>=', 3)->get();

    return view('content.dashboard.owner.classes.manage_grades', compact('staffs', 'roles', 'grades'));
  }

  public function schoolPasswords(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $search = $request->input('search');
    $this->switchTenantDB();

    $role   = session('role');
    $userId = session('user_id');

    $query = SchoolUser::on('tenant');
    $isSearch = true; // default: show search bar

    if (in_array($role, ['Owner', 'Headmaster', 'Academic'])) {
      // Full access, but still include own account
      $query->where(function ($q) use ($userId) {
        $q->where('id', $userId)
          ->orWhereRaw('1=1'); // all users
      });
    } elseif ($role === 'ClassTeacher') {
      $teacher = Teacher::on('tenant')->where('user_id', $userId)->first();

      if ($teacher) {
        $teacherId = $teacher->id;

        $assignedClassIds = DB::connection('tenant')
          ->table('subject_teacher')
          ->where('teacher_id', $teacherId)
          ->pluck('class_id')
          ->unique()
          ->toArray();

        $studentIds = Student::on('tenant')
          ->whereIn('class_id', $assignedClassIds)
          ->pluck('user_id')
          ->toArray();

        // ✅ include both assigned students and the teacher’s own account
        $allowedIds = array_merge($studentIds, [$userId]);

        $query->whereIn('id', $allowedIds);
      } else {
        $query->where('id', $userId); // fallback: at least allow own account
      }
    } elseif ($role === 'Teacher' || $role === 'Student') {
      // Only their own account → hide search
      $query->where('id', $userId);
      $isSearch = false;
    } else {
      // Unsupported role → just their own account
      $query->where('id', $userId);
      $isSearch = false;
    }

    // Apply search only if allowed
    if ($isSearch && $search) {
      $query->where(function ($q) use ($search) {
        $q->where('username', 'like', "%{$search}%")
          ->orWhere('email', 'like', "%{$search}%")
          ->orWhere('registration_no', 'like', "%{$search}%")
          ->orWhere('phone_number', 'like', "%{$search}%");
      });
    }

    $users = $query->paginate(5);

    return view('content.dashboard.owner.school_passwords', compact('users', 'search', 'isSearch'));
  }




  public function changePassword(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $this->switchTenantDB();

    $request->validate([
      'user_id' => 'required',
      'new_password' => 'required|min:6|confirmed',
    ]);

    DB::connection('tenant')->table('schoolUsers')
      ->where('registration_no', $request->user_id)
      ->update(['password' => Hash::make($request->new_password)]);

    return back()->with('success', 'Password updated successfully.');
  }


  private function switchTenantDB()
  {
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');
  }

  protected function configureTenantConnection(string $databaseName)
  {
    $school = \App\Models\School::where('database_url', $databaseName)->first();
    if ($school) {
      $school->useAsTenant();
    }
  }

    /**
     * Export students to Excel/CSV
     */
    public function exportStudents(Request $request)
    {
        $classId = $request->input('class_id');
        $filters = [
            'gender' => $request->input('gender'),
            'search' => $request->input('search'),
        ];

        return \Excel::download(
            new \App\Exports\StudentsExport($classId, $filters),
            'students_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }
}

