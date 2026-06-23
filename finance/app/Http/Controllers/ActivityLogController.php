<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\SchoolSetting;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function page()
    {
        $user = auth()->user();
        if (! $user || ! (bool) ($user->can_view_logs ?? false)) {
            abort(403, 'You do not have permission to view activity logs.');
        }

        $settings = SchoolSetting::getSettings();

        return view('admin.accountant.modules.activity-logs', compact('settings'));
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        if (! $user || ! (bool) ($user->can_view_logs ?? false)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $query = ActivityLog::query()->orderByDesc('created_at');

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->to_date);
        }

        return response()->json($query->paginate($request->integer('per_page', 30)));
    }
}
