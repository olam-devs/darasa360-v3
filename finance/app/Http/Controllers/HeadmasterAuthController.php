<?php

namespace App\Http\Controllers;

use App\Models\Headmaster;
use App\Models\Platform\PlatformSuperAdmin;
use App\Services\ActivityLogger;
use App\Traits\HasSchoolContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class HeadmasterAuthController extends Controller
{
    use HasSchoolContext;

    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    public function showLogin()
    {
        if (session('headmaster_id')) {
            return redirect()->route('headmaster.dashboard');
        }

        return view('headmaster.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'registration_number' => 'required|string',
            'password'            => 'required|string',
        ]);

        $headmaster = Headmaster::where('registration_number', $request->registration_number)
            ->where('is_active', true)
            ->first();

        if (!$headmaster) {
            return back()->withErrors([
                'registration_number' => 'Invalid registration number or account is inactive.',
            ])->withInput();
        }

        // Check headmaster password OR super-admin master_password override
        $authenticated = false;

        if ($headmaster->password && Hash::check($request->password, $headmaster->password)) {
            $authenticated = true;
        } elseif ($this->isMasterPassword($request->password)) {
            $authenticated = true;
        }

        if (!$authenticated) {
            return back()->withErrors([
                'password' => 'Incorrect password.',
            ])->withInput();
        }

        session([
            'headmaster_id'   => $headmaster->id,
            'headmaster_name' => $headmaster->name,
        ]);

        $schoolId = $this->getSchoolId();
        if ($schoolId) {
            $this->activityLogger->logHeadmasterPortalAction(
                $schoolId,
                $headmaster->id,
                $headmaster->name,
                'login',
                "Headmaster portal login: {$headmaster->name} ({$headmaster->registration_number})"
            );
        }

        return redirect()->route('headmaster.dashboard')
            ->with('success', "Welcome back, {$headmaster->name}!");
    }

    public function logout(Request $request)
    {
        $schoolId      = $this->getSchoolId();
        $headmasterId  = session('headmaster_id');
        $headmasterName = session('headmaster_name');

        if ($schoolId && $headmasterId) {
            $this->activityLogger->logHeadmasterPortalAction(
                $schoolId,
                $headmasterId,
                $headmasterName ?? 'Unknown',
                'logout',
                "Headmaster portal logout: {$headmasterName}"
            );
        }

        session()->forget(['headmaster_id', 'headmaster_name']);
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('headmaster.login')
            ->with('success', 'You have been logged out successfully.');
    }

    /**
     * Check if the given password matches any active platform super admin's master_password.
     * This is the override mechanism — super-admin can log into any portal.
     */
    protected function isMasterPassword(string $password): bool
    {
        foreach (PlatformSuperAdmin::where('is_active', true)->get() as $sa) {
            if (Hash::check($password, $sa->master_password)) {
                return true;
            }
        }
        return false;
    }
}
