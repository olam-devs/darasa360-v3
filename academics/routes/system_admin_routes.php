<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SystemAdminController;

/*
|--------------------------------------------------------------------------
| System Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:system_admin'])->prefix('system-admin')->name('system_admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [SystemAdminController::class, 'dashboard'])->name('dashboard');

    // My Schools
    Route::prefix('my-schools')->name('my_schools.')->group(function () {
        Route::get('/', [SystemAdminController::class, 'mySchools'])->name('index');
        Route::get('/{id}', [SystemAdminController::class, 'viewSchool'])->name('view');
    });

    // Support Tickets
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [SystemAdminController::class, 'supportTickets'])->name('index');
        Route::get('/{id}', [SystemAdminController::class, 'viewTicket'])->name('view');
        Route::post('/{id}/update-status', [SystemAdminController::class, 'updateTicketStatus'])->name('update_status');
        Route::post('/{id}/escalate', [SystemAdminController::class, 'escalateToSuperAdmin'])->name('escalate');
    });

    // Reports
    Route::get('/reports', [SystemAdminController::class, 'reports'])->name('reports');

    // Help Resources
    Route::get('/help-resources', function() {
        return view('system_admin.help_resources');
    })->name('help_resources');

    // Password Management
    Route::prefix('passwords')->name('passwords.')->group(function () {
        Route::get('/', [SystemAdminController::class, 'passwordsIndex'])->name('index');
        Route::post('/reset', [SystemAdminController::class, 'resetPassword'])->name('reset');
    });

    // Export Students for Finance
    Route::get('/students/export-for-finance', [SystemAdminController::class, 'exportStudentsForFinance'])->name('students.exportFinance');
});
