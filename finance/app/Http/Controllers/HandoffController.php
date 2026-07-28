<?php

namespace App\Http\Controllers;

use App\Models\Central\School;
use App\Models\Headmaster;
use App\Models\Platform\PlatformSchool;
use App\Services\HandoffService;
use App\Traits\HasSchoolContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HandoffController extends Controller
{
    use HasSchoolContext;

    public function __construct(protected HandoffService $handoff) {}

    // -------------------------------------------------------------------------
    // ISSUE — Finance side sends the user to Academics
    // -------------------------------------------------------------------------

    /**
     * Headmaster or owner clicks "Open Academics" inside the Finance headmaster portal.
     * Verifies grant, issues token, redirects to Academics consume endpoint.
     */
    public function issueFromFinance(Request $request)
    {
        $school = $this->getCurrentSchool();
        if (!$school) {
            abort(403, 'No school context.');
        }

        $platformSchool = PlatformSchool::find($school->platform_school_id);
        if (!$platformSchool) {
            return back()->with('error', 'Platform configuration missing for this school.');
        }

        $headmasterId = session('headmaster_id');
        if (!$headmasterId) {
            return back()->with('error', 'Not authenticated as headmaster.');
        }

        $headmaster = Headmaster::find($headmasterId);
        if (!$headmaster) {
            abort(403);
        }

        $role    = 'headmaster';
        $userRef = $headmaster->registration_number;

        if (!$this->handoff->canJump($role, $userRef, $platformSchool)) {
            return back()->with('error', 'Cross-system access not enabled for your account. Contact the administrator.');
        }

        $token = $this->handoff->issueToken(
            $platformSchool->id,
            $userRef,
            $role,
            'finance',
            'academics',
            ['headmaster_name' => $headmaster->name]
        );

        $url = $this->handoff->consumeUrl('academics', $token);

        if (empty(trim($url, '/?'))) {
            return back()->with('error', 'Academics system URL is not configured.');
        }

        return redirect()->away($url);
    }

    // -------------------------------------------------------------------------
    // CONSUME — Finance receives users coming from Academics
    // -------------------------------------------------------------------------

    /**
     * Academics sends a headmaster/owner here. We validate the token
     * and establish a Finance session without re-login.
     *
     * Parents are not part of this handoff: Academics has no parent
     * account concept at all, and Finance's own parent portal now logs
     * parents in directly with their own portal_email + password rather
     * than via any cross-system token.
     */
    public function consumeFromAcademics(Request $request)
    {
        $token  = $request->query('token');
        $record = $this->handoff->consumeToken($token);

        if (!$record) {
            return redirect()->route('headmaster.login')
                ->with('error', 'Invalid or expired access link. Please log in.');
        }

        // Load the Finance school via platform_school_id
        $financeSchool = School::where('platform_school_id', $record->school_id)->first();
        if (!$financeSchool || !$financeSchool->is_active) {
            return redirect()->route('headmaster.login')
                ->with('error', 'School not found or inactive.');
        }

        // Set tenant context
        session(['current_school_slug' => $financeSchool->slug]);

        $headmaster = Headmaster::where('registration_number', $record->user_ref)
            ->where('is_active', true)
            ->first();

        if (!$headmaster) {
            return redirect()->route('headmaster.login')
                ->with('error', 'Headmaster account not found in Finance system.');
        }

        session([
            'headmaster_id'   => $headmaster->id,
            'headmaster_name' => $headmaster->name,
            'handoff_from'    => 'academics',
        ]);

        return redirect()->route('headmaster.dashboard')
            ->with('success', 'Welcome — you arrived from Academics.');
    }
}
