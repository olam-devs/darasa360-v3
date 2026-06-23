<?php

use Illuminate\Support\Facades\Route;

// Debug route - REMOVE IN PRODUCTION
Route::get('/debug/calendar-session', function() {
    $userId = session('user_id');
    $role = session('role');
    $schoolDb = session('school_db');

    // Check if user exists in schoolUsers
    $userExists = false;
    $actualUser = null;
    $allUsers = [];

    try {
        $userExists = DB::connection('tenant')->table('schoolUsers')->where('id', $userId)->exists();

        if ($userExists) {
            $actualUser = DB::connection('tenant')->table('schoolUsers')
                ->where('id', $userId)
                ->first();
        }

        $allUsers = DB::connection('tenant')->table('schoolUsers')
            ->select('id', 'username', 'registration_no')
            ->limit(10)
            ->get();

    } catch (\Exception $e) {
        $error = $e->getMessage();
    }

    return response()->json([
        'session' => [
            'user_id' => $userId,
            'role' => $role,
            'school_db' => $schoolDb,
            'all_session' => session()->all()
        ],
        'database_check' => [
            'user_exists' => $userExists,
            'actual_user' => $actualUser,
            'all_users' => $allUsers,
            'error' => $error ?? null
        ]
    ]);
});
