<?php

namespace App\Http\Controllers;

use App\Models\Headmaster;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class HeadmasterManagementController extends Controller
{
    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * Get the current school ID from the tenant context.
     */
    protected function getSchoolId(): ?int
    {
        $school = app('current_school', null);
        return $school ? $school->id : null;
    }
    /**
     * Display headmaster management page.
     */
    public function index()
    {
        $headmasters = Headmaster::latest()->get();
        $settings = \App\Models\SchoolSetting::getSettings();
        
        return view('admin.accountant.modules.headmasters', compact('headmasters', 'settings'));
    }

    /**
     * Store a new headmaster.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'registration_number' => 'required|string|unique:headmasters,registration_number',
            'email' => 'nullable|email|unique:headmasters,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $headmaster = Headmaster::create([
            'name' => $request->name,
            'registration_number' => $request->registration_number,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->filled('password') ? $request->password : null,
            'is_active' => true,
        ]);

        // Log the action
        $schoolId = $this->getSchoolId();
        if ($schoolId && auth()->check()) {
            $this->activityLogger->logHeadmasterAction(
                $schoolId,
                auth()->user()->name,
                auth()->id(),
                'created',
                "Created headmaster: {$headmaster->name} ({$headmaster->registration_number})"
            );
        }

        return back()->with('success', 'Headmaster added successfully!');
    }

    /**
     * Update headmaster.
     */
    public function update(Request $request, Headmaster $headmaster)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'registration_number' => 'required|string|unique:headmasters,registration_number,' . $headmaster->id,
            'email' => 'nullable|email|unique:headmasters,email,' . $headmaster->id,
            'phone' => 'nullable|string|max:20',
        ]);

        $oldName = $headmaster->name;
        $headmaster->update($request->only(['name', 'registration_number', 'email', 'phone']));

        // Log the action
        $schoolId = $this->getSchoolId();
        if ($schoolId && auth()->check()) {
            $this->activityLogger->logHeadmasterAction(
                $schoolId,
                auth()->user()->name,
                auth()->id(),
                'updated',
                "Updated headmaster: {$headmaster->name}"
            );
        }

        return back()->with('success', 'Headmaster updated successfully!');
    }

    /**
     * Reset headmaster portal password.
     */
    public function resetPassword(Request $request, Headmaster $headmaster)
    {
        $request->validate([
            'new_password' => 'required|string|min:6|confirmed',
        ]);

        $headmaster->update(['password' => $request->new_password]);

        $schoolId = $this->getSchoolId();
        if ($schoolId && auth()->check()) {
            $this->activityLogger->logHeadmasterAction(
                $schoolId, auth()->user()->name, auth()->id(),
                'password_reset', "Reset portal password for headmaster: {$headmaster->name}"
            );
        }

        return back()->with('success', "Password reset for {$headmaster->name}.");
    }

    /**
     * Toggle headmaster status.
     */
    public function toggleStatus(Headmaster $headmaster)
    {
        $headmaster->update(['is_active' => !$headmaster->is_active]);

        $status = $headmaster->is_active ? 'activated' : 'deactivated';

        // Log the action
        $schoolId = $this->getSchoolId();
        if ($schoolId && auth()->check()) {
            $this->activityLogger->logHeadmasterAction(
                $schoolId,
                auth()->user()->name,
                auth()->id(),
                'status_toggle',
                "Headmaster {$headmaster->name} {$status}"
            );
        }

        return back()->with('success', "Headmaster has been {$status}!");
    }

    /**
     * Delete headmaster.
     */
    public function destroy(Headmaster $headmaster)
    {
        $name = $headmaster->name;
        $headmaster->delete();

        // Log the action
        $schoolId = $this->getSchoolId();
        if ($schoolId && auth()->check()) {
            $this->activityLogger->logHeadmasterAction(
                $schoolId,
                auth()->user()->name,
                auth()->id(),
                'deleted',
                "Deleted headmaster: {$name}"
            );
        }

        return back()->with('success', 'Headmaster deleted successfully!');
    }
}
