<?php

use Illuminate\Support\Facades\Route;

Route::get('/debug-role', function() {
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'Not authenticated']);
    }

    return response()->json([
        'user_id' => $user->id,
        'username' => $user->username ?? 'N/A',
        'registration_no' => $user->registration_no ?? 'N/A',
        'role_id' => $user->role_id ?? 'none',
        'role_name' => $user->role->name ?? 'NO ROLE',
        'role_object' => $user->role ? [
            'id' => $user->role->id,
            'name' => $user->role->name,
        ] : null,
        'teacher_record' => \App\Models\Teacher::where('user_id', $user->id)->first() ? 'EXISTS' : 'MISSING',
    ], 200, [], JSON_PRETTY_PRINT);
})->middleware(['web', 'auth', \App\Http\Middleware\InitializeTenantDatabase::class]);
