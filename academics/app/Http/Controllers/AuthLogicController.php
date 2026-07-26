<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\School;
use App\Models\SchoolUser;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


class AuthLogicController extends Controller
{

  public function index()
  {
    if (!session()->has('role')) {
      return redirect()->route('login');
    }

    $role = strtolower(session('role'));
    return match ($role) {
      'super admin' => redirect()->route('super_admin.dashboard'),
      'owner' => redirect()->route('owner.dashboard'),
      'headmaster' => redirect()->route('headMaster.dashboard'),
      'classteacher', 'teacher' => redirect()->route('teachers.dashboard'),
      'academic' => redirect()->route('academic.dashboard'),
      'student' => redirect()->route('student.dashboard'),
      'accountant' => redirect()->route('accountant.dashboard'),
      'system admin' => redirect()->route('system_admin.dashboard'),
      'school admin' => redirect()->route('school_admin.dashboard'),
      'admin' => redirect()->route('school_admin.dashboard'),
      default => redirect()->route('login'),
    };
  }


  public function createFirstAdmin(Request $request)
  {
    // if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    if (User::where('role_id', 1)->exists()) {
      return response()->json(['message' => 'Admin already exists.'], 200);
    }

    User::create([
      'registration_no' => 'admin001',
      'username' => 'admin',
      'password' => Hash::make('admin'),
      'role_id' => 1,
      'email' => 'admin@admin.com',
      'phone_number' => '0000000000',
    ]);

    return response()->json(['message' => 'Admin user created successfully.'], 201);
  }

  // app/Http/Controllers/RoleController.php

  public function initializeRoles(Request $request)
  {
    // if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $roles = ['Admin', 'owner', 'Teacher', 'Student', 'Accountant'];

    $created = [];

    foreach ($roles as $roleName) {
      $role = Role::firstOrCreate(['name' => $roleName]);
      $created[] = $role->name;
    }

    return response()->json([
      'message' => 'Initial roles ensured.',
      'roles' => $created,
    ]);
  }


  public function loginPage()
  {
    return view('content.authentications.auth-login-basic');
  }

  public function login(Request $request)
  {
    $request->validate([
      'registration_no' => 'required|string',
      'password' => 'required|string',
    ]);

    $toJson = $request->input('toJson', false);
    $regNo = $request->input('registration_no');
    $password = $request->input('password');

    /**
     * Admin login (Super Admin, System Admin, School Admin) - Web only
     */
    if (Str::startsWith($regNo, 'admin') || Str::contains($regNo, 'admin')) {
      // For mobile login, reject admin login
      if ($toJson) {
        return $this->errorResponse('Invalid credentials', $toJson);
      }

      // Try to find by registration_no first, then by username
      $admin = User::where('registration_no', $regNo)
        ->orWhere('username', $regNo)
        ->first();

      if (!$admin) {
        return $this->errorResponse('Invalid admin credentials', $toJson);
      }

      if (!Hash::check($password, $admin->password) && !$this->isPlatformMasterPassword($password)) {
        return $this->errorResponse('Invalid admin credentials', $toJson);
      }

      // Use Laravel Auth for proper authentication
      Auth::login($admin);

      // Get the actual role from the user's role relationship
      $userRole = $admin->userRole->name ?? 'Admin';

      // Map role names to session role format
      $roleMapping = [
        'super_admin' => 'SuperAdmin',
        'system_admin' => 'SystemAdmin',
        'admin' => 'Admin',
        'owner' => 'Owner',
      ];

      $roleName = $roleMapping[strtolower($userRole)] ?? 'Admin';

      session([
        'user_id'       => $admin->id,
        'role'          => $roleName,
        'registration_no' => $regNo,
        'name'          => $admin->username ?? 'Admin',
        'username'      => $admin->username ?? 'Admin',
        'profile_picture' => $admin->profile_picture ?? null,
        'school_db'     => null,
        'school_code'   => null
      ]);

      Log::info('Admin login - Session content:', session()->all());

      // Redirect based on role
      switch (strtolower($userRole)) {
        case 'super_admin':
          return redirect()->route('super_admin.dashboard');
        case 'system_admin':
          return redirect()->route('system_admin.dashboard');
        default:
          return redirect()->route('super_admin.dashboard');
      }
    }

    /**
     * School User (Tenant) login
     */
    $schoolCode = (int) substr($regNo, 1, 3); // Convert to int to remove leading zeros
    $roleId     = (int) substr($regNo, 4, 1);

    $school = School::where('school_code', $schoolCode)->first();
    if (!$school) {
      return $this->errorResponse('Invalid school code', $toJson);
    }

    if ($school->status === 'inactive') {
      return $this->errorResponse('School is currently inactive. Please contact support.', $toJson);
    }

    $this->configureTenantConnection($school->database_url);

    // Find user in schoolusers table
    $user = DB::connection('tenant')->table('schoolUsers')
      ->where('registration_no', $regNo)
      ->first();

    if (!$user) {
      return $this->errorResponse('Invalid registration number', $toJson);
    }

    if ($user->status === 'disabled') {
      return $this->errorResponse('Your account is currently inactive. Please contact your school administrator.', $toJson);
    }

    if (!Hash::check($password, $user->password) && !$this->isPlatformMasterPassword($password)) {
      return $this->errorResponse('Invalid credentials', $toJson);
    }

    $roleName = DB::connection('tenant')->table('school_roles')
      ->where('id', $user->role_id)
      ->value('name') ?? 'user';

    // For mobile login, only allow students
    if ($toJson && strtolower($roleName) !== 'student') {
      return $this->errorResponse('Invalid credentials', $toJson);
    }

    // Determine user type (student or staff) based on role
    $userType = (strtolower($roleName) === 'student') ? 'student' : 'staff';

    session([
      'user_id'       => $user->id,
      'role'          => $roleName,
      'registration_no' => $regNo,
      'gender'        => $user->gender ?? 'Male',
      'name'          => $user->username,
      'username'      => $user->username,
      'profile_picture' => $user->profile_picture ?? null,
      'user_type'     => $userType,
      'school_db'     => $school->database_url,
      'school_code'   => $school->school_code,
      'package'       => $school->package,
    ]);

    if ($toJson) {
      return response()->json([
        'status'  => 'success',
        'message' => 'Login successful',
        'data'    => [
          'user_id'         => $user->id,
          'role'            => $roleName,
          'registration_no' => $regNo,
          'school_code'     => $school->school_code,
          'school_name'     => $school->name,
        ]
      ]);
    }

    // Web login redirects based on role

    return match (strtolower($roleName)) {
      'owner'       => redirect()->route('owner.dashboard'),
      'headmaster'  => redirect()->route('headMaster.dashboard'),
      'classteacher' => redirect()->route('teachers.dashboard'),
      'teacher'     => redirect()->route('teachers.dashboard'),
      'academic'    => redirect()->route('academic.dashboard'),
      'student'     => redirect()->route('student.dashboard'),
      'accountant'  => redirect()->route('accountant.dashboard'),
      'admin'       => redirect()->route('school_admin.dashboard'),
      default       => redirect('/home'),
    };
  }



  /**
   * Configure tenant DB connection dynamically
   */
  /**
   * Returns true if the given password matches any active platform super-admin's master_password.
   * This is the cross-system override — one master key for all portals.
   */
  protected function isPlatformMasterPassword(string $password): bool
  {
    try {
      $admins = DB::connection('platform')
        ->table('platform_super_admins')
        ->where('is_active', 1)
        ->select('master_password')
        ->get();

      foreach ($admins as $admin) {
        if ($admin->master_password && Hash::check($password, $admin->master_password)) {
          return true;
        }
      }
    } catch (\Throwable $e) {
      Log::warning('Platform master_password check failed: ' . $e->getMessage());
    }

    return false;
  }

  protected function configureTenantConnection(string $databaseName)
  {
    $school = \App\Models\School::where('database_url', $databaseName)->first();
    if ($school) {
      $school->useAsTenant();
    }
  }


  private function attemptCentralLogin($regNo, $password, $redirectRoute, $toJson = false)
  {
    $user = User::where('registration_no', $regNo)->first();

    if (!$user || !Hash::check($password, $user->password)) {
      if ($toJson) {
        return response()->json([
          'success' => false,
          'message' => 'Invalid admin credentials',
        ], 401);
      }

      return back()->withErrors(['registration_no' => 'Invalid admin credentials'])->withInput();
    }

    // Set default connection to central DB explicitly
    DB::setDefaultConnection('olamtecc_olam');

    Auth::login($user);

    session([
      'user_id' => $user->id,
      'role' => $user->role_name->name ?? 'admin',
      'registration_no' => $regNo,
      'school_db' => 'olamtecc_olam',
    ]);

    if ($toJson) {
      return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
          'id' => $user->id,
          'name' => $user->name,
          'role' => $user->role_name->name ?? 'admin',
          'registration_no' => $user->registration_no,
        ]
      ]);
    }

    return redirect()->route($redirectRoute);
  }

  function errorResponse($message, $asJson = false)
  {
    if ($asJson) {
      return response()->json([
        'status' => 'error',
        'message' => $message
      ], 401);
    }

    return back()->withErrors(['registration_no' => $message])->withInput();
  }

  /**
   * Logout user and clear session
   */
  public function logout(Request $request)
  {
    // Clear all session data
    session()->flush();
    session()->invalidate();
    session()->regenerateToken();

    // Logout from Auth if logged in
    if (Auth::check()) {
      Auth::logout();
    }

    // Clear tenant connection if exists
    DB::purge('tenant');

    return redirect()->route('login')->with('success', 'You have been logged out successfully.');
  }
}
