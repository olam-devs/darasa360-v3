<?php

namespace App\Http\Controllers;

use App\Helpers\RegistrationHelper;
use App\Models\Location;
use App\Models\Log as ModelsLog;
use App\Models\LogModal;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\TenantLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{

  private function switchTenantDB()
  {
    $tenantDb = session('school_db');
    if (!$tenantDb) {
      abort(404, 'Tenant DB not found in session.');
    }
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');
  }


  public function dashboard(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $user = Auth::user();
    $schools = School::Where('is_deleted', false)
      ->paginate(10);
    return view('content.dashboard.super_admin.home', compact('schools'));
  }

  public function updatePackage(Request $request, $id)
  {
    $request->validate([
      'package' => 'required|in:core,standard,premium',
    ]);

    $school = School::findOrFail($id);
    $school->package = $request->input('package');
    $school->save();

    return back()->with('success', 'School package updated to ' . ucfirst($request->package));
  }

  public function schoolStats(Request $request, $schoolId)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    // Find the school from primary DB
    $school = School::find($schoolId);
    if (!$school) {
      return response()->json(['error' => 'School not found'], 404);
    }

    // Dynamically configure tenant DB connection using the helper method
    $this->configureTenantConnection($school->database_url ?? $school->db_name);

    try {
      // Query tenant DB tables via 'tenant' connection
      $totalStudents = DB::connection('tenant')->table('students')->count();
      $totalUsers = DB::connection('tenant')->table('schoolUsers')->count();
      $totalSmsSent = DB::connection('tenant')->table('sms_messages')->count();
      $totalAssignments = DB::connection('tenant')->table('assignments')->count();
      $totalGrades = DB::connection('tenant')->table('grades')->count();

      return response()->json([
        'total_students' => $totalStudents,
        'total_teachers' => $totalUsers,
        'total_sms_sent' => $totalSmsSent,
        'total_assignments' => $totalAssignments,
        'total_grades' => $totalGrades,
      ]);
    } catch (\Exception $e) {
      Log::error("Error fetching stats for school ID {$schoolId}: " . $e->getMessage());
      return response()->json(['error' => $e->getMessage()], 500);
    } finally {
      // Optionally reset connection to default after query
      DB::setDefaultConnection(config('database.default'));
    }
  }

  public function fetchAdminInfo($id)
  {
    $school = School::find($id);
    if (!$school) {
      return response()->json(['error' => 'School not found'], 404);
    }

    try {
      // Switch DB connection to tenant DB
      $this->configureTenantConnection($school->database_url);

      // Fetch the admin user with role_id 2 in tenant DB
      $admin = SchoolUser::on('tenant')->where('role_id', 2)->first();

      if (!$admin) {
        return response()->json(['error' => 'Admin not found in tenant database'], 404);
      }

      // Return JSON data to frontend
      return response()->json([
        'username' => $admin->username,
        'email' => $admin->email,
        'phone_number' => $admin->phone_number,
        'registration_no' => $admin->registration_no,
      ]);
    } catch (\Exception $e) {
      return response()->json(['error' => 'Failed to fetch admin info: ' . $e->getMessage()], 500);
    }
  }


  public function manageAdmins(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $admins = User::with('role_name')
      ->whereHas('role_name', function ($query) {
        $query->where('name', 'Admin');
      })
      ->paginate(5);

    return view('content.dashboard.super_admin.manage_admins', compact('admins'));
  }

  public function store(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $validated = $request->validate([
      'username' => 'required|string|max:255|unique:users,username',
      'email' => 'required|email|unique:users,email',
      'phone_number' => ['required', 'string', 'regex:/^0\d{9}$/'],
      'password' => 'required|min:6|confirmed',
    ], [
      'phone_number.regex' => 'Phone number must be exactly 10 digits and start with 0.',
    ]);

    $superAdminRole = Role::where('name', 'Admin')->firstOrFail();

    // Create Superadmin user without registration_no first
    $user = User::create([
      'username' => $validated['username'],
      'email' => $validated['email'],
      'phone_number' => $validated['phone_number'],
      'role_id' => $superAdminRole->id,
      'password' => bcrypt($validated['password']),
    ]);

    // Use fixed code for superadmin, e.g., "SA"
    $superAdminCode = '';

    // Generate registration_no for superadmin
    $registrationNo = RegistrationHelper::generateRegistrationNo($superAdminRole->id, $superAdminCode);

    // Update user with registration_no
    $user->registration_no = $registrationNo;
    $user->save();

    return redirect()->back()->with('success', 'Superadmin created successfully with Registration No: ' . $registrationNo);
  }





  public function delete(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $admin = User::findOrFail($id);
    $admin->delete();

    return redirect()->back()->with('success', 'Admin deleted.');
  }

  public function updateAdmin(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $admin = User::findOrFail($id);

    $request->validate([
      'username' => 'required|string|max:255|unique:users,username,' . $admin->id,
      'email' => 'required|email|unique:users,email,' . $admin->id,
      'phone_number' => [
        'required',
        'string',
        'regex:/^0\d{9}$/'
      ],
    ], [
      'phone_number.regex' => 'Phone number must be exactly 10 digits and start with 0.',
    ]);



    $admin->update([
      'username' => $request->username,
      'email' => $request->email,
      'phone_number' => $request->phone_number,
    ]);

    return redirect()->back()->with('success', 'Admin updated successfully.');
  }


  public function manageSchools(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $schools = School::with(['administrator', 'location'])
      ->where('is_deleted', false)
      ->paginate(5);

    $locations = Location::all();
    $adminInfo = null;

    // Handle case where school_admin param is passed
    if ($request->has('school_admin')) {
      $schoolId = $request->input('school_admin');


      $school = School::find($schoolId);

      if ($school) {
        try {
          // Connect to tenant DB
          $this->configureTenantConnection($school->database_url);

          // Fetch admin user from users table (tenant DB)
          $adminInfo = SchoolUser::on('tenant')
            ->where('role_id', '2')
            ->first();
        } catch (\Exception $e) {
          return back()->with('error', 'Failed to fetch admin info from school DB: ' . $e->getMessage());
        }
      }
    }

    return view('content.dashboard.super_admin.manage_schools', compact('schools', 'locations', 'adminInfo'));
  }


  public function addSchool(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $request->validate([
      'package' => 'required|in:core,standard,premium',
      'name' => 'required|string|max:255',
      'message_name' => 'required|string|max:255',
      'db_name' => 'required|string|max:64|regex:/^[a-zA-Z0-9_]+$/',
      'db_username' => 'required|string|max:32|regex:/^[a-zA-Z0-9_]+$/',
      'location_id' => 'required|exists:locations,id',
      'user_id' => 'nullable|exists:users,id',
      'admin_username' => 'required|string|max:255',
      'admin_email' => 'required|email|max:255',
      'admin_phone' => ['required', 'string', 'regex:/^0\d{9}$/'],
      'admin_password' => 'required|string|min:6|confirmed',
    ], [
      'admin_phone.regex' => 'Admin phone must be exactly 10 digits and start with 0.',
    ]);

    // Additional validation for database credentials
    if (!\App\Helpers\DatabaseHelper::isValidDatabaseName($request->input('db_name'))) {
      return back()->withErrors(['db_name' => 'Invalid database name format'])->withInput();
    }

    if (!\App\Helpers\DatabaseHelper::isValidUsername($request->input('db_username'))) {
      return back()->withErrors(['db_username' => 'Invalid database username format'])->withInput();
    }

    // Check if database already exists
    if (\App\Helpers\DatabaseHelper::databaseExists($request->input('db_name'))) {
      return back()->withErrors(['db_name' => 'Database already exists'])->withInput();
    }

    // Check if MySQL user already exists
    if (\App\Helpers\DatabaseHelper::userExists($request->input('db_username'))) {
      return back()->withErrors(['db_username' => 'Database user already exists'])->withInput();
    }

    $schoolName = $request->input('name');
    $messageName = $request->input('message_name');
    $dbName = $request->input('db_name');
    $dbUsername = $request->input('db_username');
    $locationId = $request->input('location_id');
    $userId = $request->input('user_id');
    $package = $request->input('package');

    // database_url is stored (and read everywhere else in this app) as a
    // bare database name, not a mysql:// URL - despite what buildDatabaseUrl()
    // would suggest.
    $databaseUrl = $dbName;

    $location = Location::findOrFail($locationId);
    $locationCode = (int) $location->code;

    $lastSchoolCode = School::where('location_id', $locationId)
      ->orderByDesc('school_code')
      ->value('school_code');

    $schoolCode = $lastSchoolCode ? $lastSchoolCode + 1 : $locationCode + 1;

    $school = School::create([
      'name' => $schoolName,
      'message_name' => $messageName,
      'database_url' => $databaseUrl,
      'package' => $package,
      'location_id' => $locationId,
      'user_id' => $userId,
      'school_code' => $schoolCode,
      'status' => 1,
      'is_deleted' => 0,
    ]);

    try {
      // Create the database + a dedicated MySQL user via the DirectAdmin API.
      // Raw SQL (CREATE DATABASE/CREATE USER/GRANT) doesn't work for this
      // app's DB user on this shared host - DatabaseHelper::createDatabaseUser()
      // used to attempt that and silently swallow the failure. This mirrors
      // the Finance app's already-working DirectAdminService.
      $prefix = config('directadmin.db_prefix', '');
      $nameWithoutPrefix = str_starts_with($dbName, $prefix) && $prefix !== ''
        ? substr($dbName, strlen($prefix))
        : $dbName;

      $created = app(\App\Services\DirectAdminService::class)->createDatabase($nameWithoutPrefix);
      if (!$created) {
        throw new \Exception('Failed to provision the database via DirectAdmin. Check storage/logs/laravel.log for the API response.');
      }

      // DA always applies its own account prefix regardless of what was
      // typed in the form, so make sure the stored name matches what was
      // actually created rather than trusting the raw form input.
      $school->database_url = $prefix . $nameWithoutPrefix;
      $school->db_username = $created['db_user'];
      $school->db_password = $created['db_password'];
      $school->save();

      // Test the connection using the school's own freshly-created credentials
      $school->useAsTenant();
      try {
        DB::connection('tenant')->getPdo();
      } catch (\Exception $e) {
        throw new \Exception('Database was created but the connection test failed: ' . $e->getMessage());
      }

      // Run migrations on the new database
      \Artisan::call('migrate', [
        '--path' => 'database/migrations/school',
        '--database' => 'tenant',
        '--force' => true,
      ]);

      Log::info("Migrations completed for database: $dbName");

      // Insert default roles
      $roles = ['Admin', 'Owner', 'Headmaster', 'Accountant', 'ClassTeacher', 'Teacher', 'Student', 'Academic'];
      foreach ($roles as $index => $roleName) {
        DB::connection('tenant')->table('school_roles')->insert([
          'id' => $index + 1,
          'name' => $roleName,
          'created_at' => now(),
          'updated_at' => now(),
        ]);
      }

      // Insert default grades
      $gradeSets = [
        'standard' => [[75, 100, 'A'], [65, 74, 'B'], [50, 64, 'C'], [40, 49, 'D'], [0, 39, 'F']],
        'division' => [[75, 100, 'A'], [65, 74, 'B'], [50, 64, 'C'], [40, 49, 'D'], [0, 39, 'F']],
        'individual' => [[75, 100, 'A'], [65, 74, 'B'], [50, 64, 'C'], [40, 49, 'D'], [0, 39, 'F']],
      ];

      foreach ($gradeSets as $type => $grades) {
        foreach ($grades as [$min, $max, $name]) {
          DB::connection('tenant')->table('grades')->insert([
            'name' => $name,
            'type' => $type,
            'min_mark' => $min,
            'max_mark' => $max,
            'created_at' => now(),
            'updated_at' => now(),
          ]);
        }
      }

      // Insert admin user
      $adminRoleId = 2;
      $registrationNo = RegistrationHelper::generateRegistrationNo($adminRoleId, $schoolCode, $dbName);

      SchoolUser::create([
        'username' => $request->input('admin_username'),
        'email' => $request->input('admin_email'),
        'phone_number' => $request->input('admin_phone'),
        'password' => Hash::make($request->input('admin_password')),
        'registration_no' => $registrationNo,
        'role_id' => $adminRoleId,
        'created_at' => now(),
        'updated_at' => now(),
      ]);

      return redirect('/admins/manage-schools')->with('success', 'School and admin created successfully.');
    } catch (\Exception $e) {
      if (!empty($school->db_username)) {
        try {
          app(\App\Services\DirectAdminService::class)->dropDatabase($school->database_url);
        } catch (\Exception $cleanupEx) {
          // Ignore cleanup errors
        }
      }
      $school->delete();
      Log::error('Add School Error', ['message' => $e->getMessage()]);
      return response()->json(['error' => 'Failed to create school: ' . $e->getMessage()], 500);
    }
  }



  /**
   * Truncate all tables in the tenant database except migrations.
   */
  protected function truncateAllTenantTables($tenantDb)
  {
    // Ensure tenant connection is configured
    $this->configureTenantConnection($tenantDb);

    DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=0');

    $tables = DB::connection('tenant')->select("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'");
    $tableKey = "Tables_in_{$tenantDb}";

    foreach ($tables as $table) {
      $tableName = $table->$tableKey;

      // Skip migrations table if you want to keep migrations
      if ($tableName === 'migrations') {
        continue;
      }

      DB::connection('tenant')->table($tableName)->truncate();
    }

    DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=1');
  }





  /**
   * Duplicate database schema only, no data
   */
  public function duplicateDatabaseSchema($sourceDb, $targetDb)
  {
    try {
      Log::info('Starting schema duplication', compact('sourceDb', 'targetDb'));

      DB::statement("CREATE DATABASE IF NOT EXISTS `$targetDb` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

      $tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", [$sourceDb]);

      foreach ($tables as $table) {
        $tableName = $table->table_name ?? $table->TABLE_NAME;

        // Skip sessions table
        if (strtolower($tableName) === 'sessions') {
          Log::info("Skipping table: $tableName");
          continue;
        }

        if ($tableName) {
          Log::info("Copying table structure for: $tableName");

          $createTable = DB::select("SHOW CREATE TABLE `$sourceDb`.`$tableName`");
          $createSQL = $createTable[0]->{'Create Table'} ?? null;

          if ($createSQL) {
            DB::statement("USE `$targetDb`");
            DB::statement($createSQL);
            DB::statement("ALTER TABLE `$targetDb`.`$tableName` AUTO_INCREMENT = 1");
          } else {
            Log::warning("Could not retrieve CREATE TABLE for $tableName");
          }
        }
      }

      Log::info("Schema duplication from `$sourceDb` to `$targetDb` completed successfully.");
    } catch (\Exception $e) {
      Log::error('Schema duplication failed', ['error' => $e->getMessage()]);
      throw $e;
    }
  }







  public function updateSchool(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    // Get school from central DB
    $school = School::findOrFail($id);

    if ($request->has('toggle_status')) {
      $school->status = $school->status === 'active' ? 'inactive' : 'active';
      $school->save();

      return redirect()->route('admin.manageSchools')->with('success', 'School status updated.');
    }

    // Validate input (added message_name)
    $request->validate([
      'package' => 'required|in:core,standard,premium',
      'name' => 'required|string|max:255',
      'message_name' => 'required|string|max:255',   // <-- Added validation
      'location_id' => 'required|exists:locations,id',
      'database_url' => 'required|string|max:255',

      // Admin update fields
      'admin_username' => 'required|string|max:255',
      'admin_email' => 'required|email|max:255',
      'admin_phone' => ['required', 'string', 'regex:/^0\d{9}$/'],
      'admin_password' => 'nullable|confirmed|min:6',
    ], [
      'admin_phone.regex' => 'Admin phone must be exactly 10 digits and start with 0.',
    ]);

    // Update school in central DB, including message_name
    $school->update([
      'name' => $request->input('name'),
      'package' => $request->input('package'),
      'message_name' => $request->input('message_name'),   // <-- Update message_name
      'location_id' => $request->input('location_id'),
      'database_url' => $request->input('database_url'),
    ]);

    // Configure dynamic tenant connection
    Config::set('database.connections.tenant', [
      'driver' => 'mysql',
      'host' => env('DB_HOST', '127.0.0.1'),
      'port' => env('DB_PORT', '3306'),
      'database' => $school->database_url,
      'username' => env('DB_USERNAME', 'root'),
      'password' => env('DB_PASSWORD', ''),
      'charset' => 'utf8mb4',
      'collation' => 'utf8mb4_unicode_ci',
      'prefix' => '',
      'strict' => true,
    ]);

    DB::purge('tenant');
    DB::reconnect('tenant');

    // Get admin from tenant's `schoolusers` table (role_id = 2)
    $admin = DB::connection('tenant')
      ->table('schoolUsers')
      ->where('role_id', 2)
      ->first();

    if ($admin) {
      // Prepare update data
      $updateData = [
        'username' => $request->input('admin_username'),
        'email' => $request->input('admin_email'),
        'phone_number' => $request->input('admin_phone'),
      ];

      if ($request->filled('admin_password')) {
        $updateData['password'] = Hash::make($request->input('admin_password'));
      }

      // Update admin in tenant DB
      DB::connection('tenant')
        ->table('schoolUsers')
        ->where('id', $admin->id)
        ->update($updateData);
    }

    return redirect()->route('admin.manageSchools')->with('success', 'School updated. Tenant admin details also updated.');
  }





  public function deleteSchool(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $school = School::findOrFail($id);


    // Mark as deleted
    $school->update(['is_deleted' => true]);
    return redirect()->route('admin.manageSchools')->with('success', 'School marked as deleted.');
  }

  public function toggleSchoolStatus(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $school = School::findOrFail($id);

    // Toggle between 'active' and 'inactive'
    $school->status = $school->status === 'active' ? 'inactive' : 'active';

    $school->save();

    return redirect()->back()->with('success', 'School status updated successfully.');
  }

  public function manageLogs(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $schools = School::all(['id', 'name']);
    $logs = collect();

    if ($request->filled('school_id')) {
      // Find school by ID
      $school = School::findOrFail($request->school_id);

      // Switch to tenant DB dynamically, similar to updateUserStatus
      Config::set('database.connections.tenant', [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => $school->database_url, // adjust if different field name
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
      ]);

      DB::purge('tenant');
      DB::reconnect('tenant');

      // Now fetch logs from tenant connection
      $logs = TenantLog::with(['user' => function ($query) {
        $query->select('id', 'username'); // from schoolusers table/model
      }])
        ->latest()
        ->paginate(10);
    } else {
      // Fetch logs from central DB normally
      $logs = LogModal::with([
        'user' => function ($query) {
          $query->select('id', 'username'); // from central users
        },
        'school' => function ($query) {
          $query->select('id', 'name');
        }
      ])
        ->latest()
        ->paginate(10);
    }

    return view('content.dashboard.super_admin.manage_logs', compact('logs', 'schools'));
  }



  public function manageLogsBySchool(Request $request, $school_id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $school = School::findOrFail($school_id);

    $this->switchTenantDB($school_id);

    $logs = TenantLog::with(['user' => function ($query) {
      $query->select('id', 'name');
    }])->latest()->paginate(10);

    $schools = School::all(['id', 'name']);

    return view('content.dashboard.super_admin.manage_logs', compact('logs', 'schools'));
  }


  public function manageLocation(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $locations = Location::paginate(10);
    return view('content.dashboard.super_admin.manage_locations', compact('locations'));
  }

  public function addLocation(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $request->validate([
      'name' => 'required|string|max:255',
      'code' => 'required|string|max:20|unique:locations,code',
    ]);

    Location::create($request->only('name', 'code'));

    return redirect()->back()->with('success', 'Location created successfully.')->with('form', 'add_location');
  }

  public function updateLocation(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $location = Location::findOrFail($id);

    $request->validate([
      'name' => 'required|string|max:255',
      'code' => 'required|string|max:20|unique:locations,code,' . $location->id,
    ]);

    $location->update($request->only('name', 'code'));

    return redirect()->back()->with('success', 'Location updated successfully.');
  }

  public function deleteLocation(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $location = Location::findOrFail($id);
    $location->delete();

    return redirect()->back()->with('success', 'Location deleted successfully.');
  }



  public function managePasswords(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $search = $request->input('search');

    // Step 1: Check central DB normally
    $users = User::with('school')
      ->when($search, function ($query, $search) {
        $query->where(function ($q) use ($search) {
          $q->where('username', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhere('registration_no', 'like', "%{$search}%")
            ->orWhere('phone_number', 'like', "%{$search}%")
            ->orWhereHas('school', function ($q2) use ($search) {
              $q2->where('name', 'like', "%{$search}%");
            });
        });
      })
      ->paginate(5);

    $tenantUser = null;
    $matchedSchool = null;

    // Step 2: If registration_no is like S101XXXXX, try tenant query
    if ($search && preg_match('/^S(\d{3})\d{5}$/i', $search, $matches)) {
      $schoolCode = $matches[1]; // e.g. 101

      // Step 3: Find matching school by code
      $matchedSchool = School::where('school_code', $schoolCode)->first();

      if ($matchedSchool) {
        // Step 4: Connect to tenant DB
        Config::set('database.connections.tenant', [
          'driver' => 'mysql',
          'host' => env('DB_HOST', '127.0.0.1'),
          'port' => env('DB_PORT', '3306'),
          'database' => $matchedSchool->database_url,
          'username' => env('DB_USERNAME', 'root'),
          'password' => env('DB_PASSWORD', ''),
          'charset' => 'utf8mb4',
          'collation' => 'utf8mb4_unicode_ci',
          'prefix' => '',
          'strict' => true,
        ]);

        DB::purge('tenant');
        DB::reconnect('tenant');

        // Step 5: Query tenant user by registration_no
        $tenantUser = DB::connection('tenant')
          ->table('schoolUsers')
          ->where('registration_no', $search)
          ->first();
      }
    }

    return view('content.dashboard.super_admin.manage_passwords', [
      'users' => $users,
      'search' => $search,
      'tenantUser' => $tenantUser,
      'matchedSchool' => $matchedSchool,
    ]);
  }

  public function changePassword(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $request->validate([
      'user_id' => 'required',
      'user_db' => 'required|in:central,tenant',
      'new_password' => 'required|string|min:6|confirmed',
    ]);

    $userId = $request->input('user_id');
    $userDb = $request->input('user_db');
    $newPassword = $request->input('new_password');

    try {
      if ($userDb === 'central') {
        // Central DB user validation and update
        $user = \App\Models\User::find($userId);

        if (!$user) {
          throw ValidationException::withMessages([
            'user_id' => ['User not found in central database.'],
          ]);
        }

        $user->password = bcrypt($newPassword);
        $user->save();
      } elseif ($userDb === 'tenant') {
        // Tenant DB user validation and update
        $tenantDb = $request->input('tenant_db');

        if (empty($tenantDb)) {
          throw ValidationException::withMessages([
            'tenant_db' => ['Tenant database not specified.'],
          ]);
        }

        // Setup tenant connection dynamically
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

        $tenantUser = DB::connection('tenant')->table('schoolUsers')->where('registration_no', $userId)->first();

        if (!$tenantUser) {
          throw ValidationException::withMessages([
            'user_id' => ['User not found in tenant database.'],
          ]);
        }

        DB::connection('tenant')->table('schoolUsers')
          ->where('registration_no', $userId)
          ->update([
            'password' => bcrypt($newPassword),
            'updated_at' => now(),
          ]);

        // Reset default connection after operation
        DB::setDefaultConnection(config('database.default'));
      } else {
        throw ValidationException::withMessages([
          'user_db' => ['Invalid user_db value.'],
        ]);
      }
    } catch (ValidationException $e) {
      // Redirect back with validation errors, keep modal open, pass user info to modal
      return back()
        ->withErrors($e->validator ?? $e->getMessage())
        ->withInput()
        ->with('change_password_error', true)
        ->with('change_password_user_id', $userId)
        ->with('change_password_user_name', $userDb === 'central' ? (\App\Models\User::find($userId)?->username ?? '') : ($tenantUser->username ?? ''))
        ->with('change_password_user_db', $userDb)
        ->with('change_password_tenant_db', $request->input('tenant_db'));
    }

    return redirect()->route('admin.passwordManagement')
      ->with('success', 'Password changed successfully.');
  }

  protected function configureTenantConnection($databaseName)
  {
    $school = \App\Models\School::where('database_url', $databaseName)->first();
    if ($school) {
      $school->useAsTenant();
    }
  }
}
