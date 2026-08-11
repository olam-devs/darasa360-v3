<?php

namespace App\Http\Controllers;

use App\Models\Central\School;
use App\Models\Central\SchoolAccountant;
use App\Services\AccountantPermissionSync;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;

/**
 * Lets a school's designated "main accountant" (SchoolAccountant::is_main_accountant)
 * grant/revoke can_edit_history/can_view_logs for the other accountants at
 * their own school, without needing the super admin each time. Reads/writes
 * the same central SchoolAccountant columns the super admin's Edit modal
 * does - is_main_accountant itself is never editable here, only via
 * SuperAdmin\SchoolController.
 */
class AccountantPermissionController extends Controller
{
    public function __construct(
        protected AccountantPermissionSync $accountantPermissionSync,
        protected ActivityLogger $activityLogger
    ) {
    }

    public function index(Request $request)
    {
        $school = School::resolveForRequest($request);

        if (!$school) {
            return response()->json(['error' => 'Could not resolve your school.'], 500);
        }

        $accountants = $school->accountants()
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'is_active', 'is_main_accountant', 'can_edit_history', 'can_view_logs']);

        return response()->json(['accountants' => $accountants]);
    }

    public function updatePermissions(Request $request, SchoolAccountant $accountant)
    {
        $school = School::resolveForRequest($request);

        if (!$school) {
            return response()->json(['error' => 'Could not resolve your school.'], 500);
        }

        // Must stay scoped to the acting main accountant's own school - never
        // let them touch another school's accountant, even by guessing an id.
        if ($accountant->school_id !== $school->id) {
            return response()->json(['error' => 'Accountant not found for your school.'], 404);
        }

        $validated = $request->validate([
            'can_edit_history' => 'required|boolean',
            'can_view_logs' => 'required|boolean',
        ]);

        $accountant->update([
            'can_edit_history' => $validated['can_edit_history'],
            'can_view_logs' => $validated['can_view_logs'],
        ]);

        $this->accountantPermissionSync->sync($school, $accountant);

        $this->activityLogger->logAccountantAction(
            $request->user(),
            'permissions_updated',
            "Updated permissions for {$accountant->name} ({$accountant->email})"
        );

        return response()->json(['accountant' => $accountant->fresh()->only([
            'id', 'name', 'email', 'is_active', 'is_main_accountant', 'can_edit_history', 'can_view_logs',
        ])]);
    }
}
