<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HandoffController extends Controller
{
    // -------------------------------------------------------------------------
    // CONSUME — Academics receives users coming from Finance
    // -------------------------------------------------------------------------

    /**
     * Finance sends a headmaster/owner/parent here.
     * Validate the single-use token, establish an Academics session, redirect to dashboard.
     */
    public function consumeFromFinance(Request $request)
    {
        $tokenStr = $request->query('token');

        if (!$tokenStr) {
            return redirect()->route('login')
                ->with('error', 'Missing access token.');
        }

        $record = $this->findAndConsumeToken($tokenStr);

        if (!$record) {
            return redirect()->route('login')
                ->with('error', 'Invalid or expired access link. Please log in.');
        }

        // Resolve school from platform
        $platformSchool = DB::connection('platform')
            ->table('platform_schools')
            ->where('id', $record->school_id)
            ->first();

        if (!$platformSchool || !$platformSchool->academics_db_name) {
            return redirect()->route('login')
                ->with('error', 'Academics system not configured for this school.');
        }

        // Set up tenant DB connection
        $this->configureTenantConnection($platformSchool->academics_db_name);

        // Find the schoolUser by registration_no
        $user = DB::connection('tenant')
            ->table('schoolUsers')
            ->where('registration_no', $record->user_ref)
            ->first();

        if (!$user) {
            return redirect()->route('login')
                ->with('error', 'User not found in Academics system.');
        }

        // Resolve role name
        $roleName = DB::connection('tenant')->table('school_roles')
            ->where('id', $user->role_id)
            ->value('name') ?? 'staff';

        // Establish Academics session (same shape as normal login)
        session([
            'user_id'         => $user->id,
            'role'            => $roleName,
            'registration_no' => $record->user_ref,
            'name'            => $user->username,
            'username'        => $user->username,
            'profile_picture' => $user->profile_picture ?? null,
            'user_type'       => 'staff',
            'school_db'       => $platformSchool->academics_db_name,
            'school_code'     => $platformSchool->code,
            'handoff_from'    => 'finance',
        ]);

        $dashboard = match (strtolower($roleName)) {
            'headmaster'  => 'headMaster.dashboard',
            'owner'       => 'owner.dashboard',
            'student'     => 'student.dashboard',
            default       => 'headMaster.dashboard',
        };

        return redirect()->route($dashboard)
            ->with('success', 'Welcome — you arrived from Finance.');
    }

    // -------------------------------------------------------------------------
    // ISSUE — Academics sends user to Finance
    // -------------------------------------------------------------------------

    /**
     * Headmaster/owner inside Academics clicks "Open Finance".
     */
    public function issueFromAcademics(Request $request)
    {
        $regNo      = session('registration_no');
        $role       = strtolower(session('role', ''));
        $schoolCode = session('school_code');

        if (!$regNo || !$schoolCode) {
            return back()->with('error', 'Session missing. Please log in again.');
        }

        $platformSchool = DB::connection('platform')
            ->table('platform_schools')
            ->where('code', str_pad($schoolCode, 3, '0', STR_PAD_LEFT))
            ->first();

        if (!$platformSchool) {
            return back()->with('error', 'Platform not configured for this school.');
        }

        if (!$platformSchool->has_finance || !$platformSchool->has_academics || !$platformSchool->cross_jump_enabled) {
            return back()->with('error', 'Cross-system jump is not enabled for this school.');
        }

        // Role-based grant check
        $allowed = false;
        if (in_array($role, ['headmaster', 'owner'])) {
            $allowed = DB::connection('platform')
                ->table('platform_cross_access')
                ->where('school_id', $platformSchool->id)
                ->where('user_ref', $regNo)
                ->where('is_active', 1)
                ->exists();
        }

        if (!$allowed) {
            return back()->with('error', 'Cross-system access not granted for your account.');
        }

        $token = $this->createToken(
            $platformSchool->id,
            $regNo,
            $role,
            'academics',
            'finance'
        );

        $financeUrl = rtrim(env('FINANCE_APP_URL', ''), '/') . '/handoff/consume?token=' . urlencode($token);

        if (empty(trim($financeUrl, '/?'))) {
            return back()->with('error', 'Finance system URL is not configured (FINANCE_APP_URL).');
        }

        return redirect()->away($financeUrl);
    }

    // -------------------------------------------------------------------------
    // SUPER ADMIN CROSS-JUMP
    // -------------------------------------------------------------------------

    /**
     * Academics super admin → Finance super admin panel
     */
    public function issueFromAcademicsSuperAdmin(Request $request)
    {
        $financeUrl = rtrim(env('FINANCE_APP_URL', ''), '/');
        if (!$financeUrl) {
            return back()->with('error', 'Finance system URL not configured (FINANCE_APP_URL).');
        }

        $token = \Illuminate\Support\Str::random(64);

        DB::connection('platform')->table('platform_handoff_tokens')->insert([
            'token'         => $token,
            'school_id'     => 0,
            'user_ref'      => session('registration_no', 'super_admin'),
            'role'          => 'super_admin',
            'source_system' => 'academics',
            'target_system' => 'finance',
            'payload'       => json_encode(['type' => 'super_admin_jump']),
            'expires_at'    => now()->addSeconds(90),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return redirect()->away($financeUrl . '/superadmin/handoff/consume?token=' . urlencode($token));
    }

    /**
     * Finance super admin lands here from Finance → Academics jump
     */
    public function consumeSuperAdminFromFinance(Request $request)
    {
        $tokenStr = $request->query('token');
        if (!$tokenStr) {
            return redirect()->route('login')->with('error', 'Missing access token.');
        }

        $record = $this->findAndConsumeToken($tokenStr);
        if (!$record || $record->role !== 'super_admin') {
            return redirect()->route('login')->with('error', 'Invalid or expired super admin link.');
        }

        // Find the Academics super admin user
        $superAdminRole = DB::table('roles')->where('name', 'super_admin')->first();
        $adminUser = DB::table('users')
            ->where('role_id', $superAdminRole->id ?? 0)
            ->first();

        if (!$adminUser) {
            return redirect()->route('login')->with('error', 'Super admin not found in Academics.');
        }

        session([
            'user_id'         => $adminUser->id,
            'role'            => 'super admin',
            'registration_no' => $adminUser->registration_no,
            'name'            => $adminUser->username,
            'username'        => $adminUser->username,
            'school_db'       => null,
            'school_code'     => null,
            'handoff_from'    => 'finance',
        ]);

        return redirect()->route('super_admin.dashboard')
            ->with('success', 'Signed in from Finance super admin panel.');
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    protected function findAndConsumeToken(string $token): ?object
    {
        $record = DB::connection('platform')
            ->table('platform_handoff_tokens')
            ->where('token', $token)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$record) {
            return null;
        }

        DB::connection('platform')
            ->table('platform_handoff_tokens')
            ->where('id', $record->id)
            ->update(['used_at' => now()]);

        return $record;
    }

    protected function createToken(
        int $schoolId,
        string $userRef,
        string $role,
        string $source,
        string $target,
        array $payload = []
    ): string {
        $token = Str::random(64);

        DB::connection('platform')->table('platform_handoff_tokens')->insert([
            'token'         => $token,
            'school_id'     => $schoolId,
            'user_ref'      => $userRef,
            'role'          => $role,
            'source_system' => $source,
            'target_system' => $target,
            'payload'       => json_encode($payload),
            'expires_at'    => now()->addSeconds(90),
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return $token;
    }

    protected function configureTenantConnection(string $databaseName): void
    {
        config([
            'database.connections.tenant' => [
                'driver'    => 'mysql',
                'host'      => env('DB_HOST', '127.0.0.1'),
                'port'      => env('DB_PORT', '3306'),
                'database'  => $databaseName,
                'username'  => env('DB_USERNAME', 'root'),
                'password'  => env('DB_PASSWORD', ''),
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'strict'    => false,
            ],
        ]);

        DB::purge('tenant');
        DB::reconnect('tenant');
    }
}
