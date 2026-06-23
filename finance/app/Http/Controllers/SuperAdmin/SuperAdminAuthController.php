<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Platform\PlatformSuperAdmin as SuperAdmin;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class SuperAdminAuthController extends Controller
{
    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * Show the super admin login form.
     */
    public function showLogin()
    {
        if (auth('superadmin')->check()) {
            return redirect()->route('superadmin.dashboard');
        }

        return view('superadmin.auth.login');
    }

    /**
     * Handle super admin login.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $credentials = $request->only('email', 'password');
        
        // Attempt to find super admin
        $superAdmin = SuperAdmin::where('email', $credentials['email'])->first();

        if (!$superAdmin) {
            return back()->withErrors([
                'email' => 'These credentials do not match our records.',
            ])->withInput($request->only('email'));
        }

        // Check if account is active
        if (!$superAdmin->is_active) {
            return back()->withErrors([
                'email' => 'Your account has been deactivated. Please contact support.',
            ])->withInput($request->only('email'));
        }

        // Attempt login
        if (Auth::guard('superadmin')->attempt($credentials, $request->filled('remember'))) {
            $request->session()->regenerate();

            // Log the login
            $this->activityLogger->logSuperAdminLogin($superAdmin);

            return redirect()->intended(route('superadmin.dashboard'));
        }

        return back()->withErrors([
            'email' => 'These credentials do not match our records.',
        ])->withInput($request->only('email'));
    }

    /**
     * Handle super admin logout.
     */
    public function logout(Request $request)
    {
        $superAdmin = auth('superadmin')->user();

        if ($superAdmin) {
            $this->activityLogger->logSuperAdminLogout($superAdmin);
        }

        Auth::guard('superadmin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.login')
            ->with('success', 'You have been logged out successfully.');
    }

    /**
     * Show the profile page.
     */
    public function profile()
    {
        return view('superadmin.profile');
    }

    /**
     * Update the super admin's own password.
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $superAdmin = auth('superadmin')->user();

        if (!Hash::check($request->current_password, $superAdmin->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        $superAdmin->update([
            'password' => $request->password,
        ]);

        $this->activityLogger->logSuperAdminAction(
            $superAdmin,
            'password_changed',
            'Super admin changed their own password'
        );

        return back()->with('success', 'Password updated successfully!');
    }
}
