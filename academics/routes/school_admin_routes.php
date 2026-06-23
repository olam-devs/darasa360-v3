<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SchoolAdminController;

/*
|--------------------------------------------------------------------------
| School Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['tenant.role:Admin'])->prefix('school-admin')->name('school_admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [SchoolAdminController::class, 'dashboard'])->name('dashboard');

    // School Overview
    Route::get('/overview', [SchoolAdminController::class, 'overview'])->name('overview');

    // Support
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [SchoolAdminController::class, 'support'])->name('index');
        Route::post('/create', [SchoolAdminController::class, 'createTicket'])->name('create');
        Route::get('/{id}', [SchoolAdminController::class, 'viewTicket'])->name('view');
        Route::post('/{id}/escalate', [SchoolAdminController::class, 'escalateTicket'])->name('escalate');
    });

    // Staff Management
    Route::prefix('staff-management')->name('staff.')->group(function () {
        Route::get('/', [SchoolAdminController::class, 'staffManagement'])->name('index');
        Route::post('/create', [SchoolAdminController::class, 'createStaff'])->name('create');
        Route::get('/{id}', [SchoolAdminController::class, 'viewStaff'])->name('view');
        Route::put('/{id}', [SchoolAdminController::class, 'updateStaff'])->name('update');
        Route::post('/{id}/toggle-status', [SchoolAdminController::class, 'toggleStaffStatus'])->name('toggle_status');
        Route::post('/{id}/reset-password', [SchoolAdminController::class, 'resetStaffPassword'])->name('reset_password');
        Route::delete('/{id}', [SchoolAdminController::class, 'deleteStaff'])->name('delete');
    });

    // System Settings
    Route::get('/system-settings', [SchoolAdminController::class, 'systemSettings'])->name('system_settings');

    // Training Resources
    Route::get('/training', [SchoolAdminController::class, 'trainingResources'])->name('training');

    // Export Students for Finance
    Route::get('/students/export-for-finance', [SchoolAdminController::class, 'exportStudentsForFinance'])->name('students.exportFinance');
});
