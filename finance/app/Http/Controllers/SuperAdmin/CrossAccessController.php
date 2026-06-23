<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Central\School;
use App\Models\Platform\PlatformCrossAccess;
use App\Models\Platform\PlatformSchool;
use Illuminate\Http\Request;

class CrossAccessController extends Controller
{
    public function index(School $school)
    {
        $platformSchool = $this->resolvePlatformSchool($school);

        $grants = PlatformCrossAccess::where('school_id', $platformSchool->id)
            ->orderBy('role')
            ->orderBy('user_ref')
            ->get();

        return view('superadmin.platform.grants', compact('school', 'platformSchool', 'grants'));
    }

    public function store(Request $request, School $school)
    {
        $request->validate([
            'user_ref' => ['required', 'string', 'max:20'],
            'role'     => ['required', 'in:headmaster,owner'],
            'level'    => ['required', 'in:readonly,full'],
        ]);

        $platformSchool = $this->resolvePlatformSchool($school);

        PlatformCrossAccess::updateOrCreate(
            [
                'school_id' => $platformSchool->id,
                'user_ref'  => strtoupper(trim($request->user_ref)),
                'role'      => $request->role,
            ],
            [
                'target_system' => 'academics',
                'level'         => $request->level,
                'is_active'     => true,
            ]
        );

        return back()->with('success', 'Cross-access grant saved.');
    }

    public function toggle(Request $request, School $school, PlatformCrossAccess $grant)
    {
        $this->resolvePlatformSchool($school);

        $grant->update(['is_active' => !$grant->is_active]);

        $state = $grant->is_active ? 'activated' : 'deactivated';

        return back()->with('success', "Grant for {$grant->user_ref} {$state}.");
    }

    public function destroy(School $school, PlatformCrossAccess $grant)
    {
        $this->resolvePlatformSchool($school);

        $grant->delete();

        return back()->with('success', 'Grant removed.');
    }

    protected function resolvePlatformSchool(School $school): PlatformSchool
    {
        if (!$school->platform_school_id) {
            abort(404, 'School not registered on platform.');
        }

        return PlatformSchool::findOrFail($school->platform_school_id);
    }
}
