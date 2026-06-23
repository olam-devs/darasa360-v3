<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;

class AccountantUserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->whereIn('role', ['accountant', 'superadmin'])
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'can_edit_history', 'can_view_logs']);

        return response()->json(['users' => $users]);
    }

    public function updatePermissions(Request $request, $id)
    {
        $target = User::findOrFail($id);

        if (! in_array($target->role, ['accountant', 'superadmin'], true)) {
            return response()->json(['error' => 'User is not an accountant.'], 422);
        }

        $validated = $request->validate([
            'can_edit_history' => 'required|boolean',
            'can_view_logs' => 'required|boolean',
        ]);

        $target->update([
            'can_edit_history' => $validated['can_edit_history'],
            'can_view_logs' => $validated['can_view_logs'],
        ]);

        ActivityLogger::log(
            'permissions_updated',
            "Updated permissions for {$target->name} ({$target->email})",
            $target,
            $validated
        );

        return response()->json(['user' => $target->fresh()]);
    }
}
