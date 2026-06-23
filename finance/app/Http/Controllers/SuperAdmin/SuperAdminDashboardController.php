<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Central\School;
use App\Models\Central\ActivityLog;
use App\Models\Central\AnalyticsSummary;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuperAdminDashboardController extends Controller
{
    /**
     * Display the super admin dashboard.
     */
    public function index()
    {
        // Get overall statistics
        $totalSchools = School::count();
        $activeSchools = School::active()->count();
        $inactiveSchools = $totalSchools - $activeSchools;
        
        // Get subscription statistics
        $subscribedSchools = School::subscribed()->count();
        $trialSchools = School::where('subscription_status', 'trial')->count();
        $suspendedSchools = School::where('subscription_status', 'suspended')->count();

        // Get total students across all schools (from analytics summary)
        $totalStudents = AnalyticsSummary::whereDate('date', today())
            ->sum('total_students');

        // Get total revenue collected
        $totalRevenue = AnalyticsSummary::whereDate('date', today())
            ->sum('total_fees_collected');

        // Get average collection rate
        $avgCollectionRate = AnalyticsSummary::whereDate('date', today())
            ->avg('collection_rate');

        // Get recent schools
        $recentSchools = School::latest()
            ->take(5)
            ->get();

        // Get recent activity logs
        $recentActivities = ActivityLog::with('school')
            ->latest()
            ->take(10)
            ->get();

        // Get schools with low collection rates
        $lowPerformingSchools = AnalyticsSummary::with('school')
            ->whereDate('date', today())
            ->where('collection_rate', '<', 70)
            ->orderBy('collection_rate', 'asc')
            ->take(5)
            ->get();

        // Get revenue trend (last 7 days)
        $revenueTrend = AnalyticsSummary::select(
                'date',
                DB::raw('SUM(total_fees_collected) as total_revenue')
            )
            ->where('date', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return view('superadmin.dashboard', compact(
            'totalSchools',
            'activeSchools',
            'inactiveSchools',
            'subscribedSchools',
            'trialSchools',
            'suspendedSchools',
            'totalStudents',
            'totalRevenue',
            'avgCollectionRate',
            'recentSchools',
            'recentActivities',
            'lowPerformingSchools',
            'revenueTrend'
        ));
    }

    /**
     * Display the activity logs with filters.
     */
    public function activityLogs(Request $request)
    {
        $query = ActivityLog::with('school')->latest();

        if ($request->filled('user_type') && $request->user_type !== 'all') {
            $query->where('user_type', $request->user_type);
        }

        if ($request->filled('school_id') && $request->school_id !== 'all') {
            $query->where('school_id', $request->school_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        $logs = $query->paginate(50)->withQueryString();
        $schools = School::orderBy('name')->get(['id', 'name']);
        $userTypes = ['super_admin', 'accountant', 'headmaster', 'parent'];

        return view('superadmin.activity-logs', compact('logs', 'schools', 'userTypes'));
    }
}
