<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\HandoffController;

/*
|--------------------------------------------------------------------------
| Super Admin Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:super_admin'])->prefix('super-admin')->name('super_admin.')->group(function () {

    // Dashboard
    Route::get('/dashboard', [SuperAdminController::class, 'dashboard'])->name('dashboard');

    // School Management
    Route::prefix('schools')->name('schools.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'dashboard'])->name('index');
        Route::get('/create', [SuperAdminController::class, 'createSchoolForm'])->name('create');
        Route::post('/', [SuperAdminController::class, 'createSchool'])->name('store');
        Route::get('/{id}', [SuperAdminController::class, 'viewSchool'])->name('view');
        Route::post('/{id}/assign-admin', [SuperAdminController::class, 'assignSystemAdmin'])->name('assign_admin');
        Route::put('/{id}/billing', [SuperAdminController::class, 'updateBilling'])->name('update_billing');
    });

    // System Admins Management
    Route::prefix('admins')->name('admins.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'listSystemAdmins'])->name('index');
        Route::get('/allocations', [SuperAdminController::class, 'manageAllocations'])->name('allocations');
        Route::post('/super-admin', [SuperAdminController::class, 'createSuperAdmin'])->name('create_super');
        Route::post('/system-admin', [SuperAdminController::class, 'createSystemAdmin'])->name('create_system');
        Route::post('/{adminId}/add-schools', [SuperAdminController::class, 'addSchoolsToAdmin'])->name('add_schools');
        Route::delete('/{adminId}/schools/{schoolId}', [SuperAdminController::class, 'removeSchoolFromAdmin'])->name('remove_school');
    });

    // SMS Management
    Route::prefix('sms')->name('sms.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'smsIndex'])->name('index');
        Route::get('/alert-admins', [SuperAdminController::class, 'smsAlertAdminsForm'])->name('alert_admins');
        Route::post('/alert-admins', [SuperAdminController::class, 'sendAdminAlert'])->name('send_alert');
        Route::get('/allocations', [SuperAdminController::class, 'smsAllocationsPage'])->name('allocations');
        Route::post('/allocations/{schoolId}', [SuperAdminController::class, 'allocateSmsCredits'])->name('allocate_credits');
        Route::post('/bulk-allocate', [SuperAdminController::class, 'bulkAllocateSmsCredits'])->name('bulk_allocate');
        Route::get('/send', [SuperAdminController::class, 'smsSendForm'])->name('send');
        Route::post('/send', [SuperAdminController::class, 'smsSend'])->name('send_post');
        Route::get('/history', [SuperAdminController::class, 'smsHistory'])->name('history');
    });

    // Location Management
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'locationsIndex'])->name('index');
        Route::post('/', [SuperAdminController::class, 'createLocation'])->name('create');
        Route::put('/{id}', [SuperAdminController::class, 'updateLocation'])->name('update');
        Route::delete('/{id}', [SuperAdminController::class, 'deleteLocation'])->name('delete');
    });

    // Logs
    Route::prefix('logs')->name('logs.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'logsIndex'])->name('index');
        Route::get('/view/{id}', [SuperAdminController::class, 'viewLog'])->name('view');
    });

    // Password Management
    Route::prefix('passwords')->name('passwords.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'passwordsIndex'])->name('index');
        Route::post('/reset', [SuperAdminController::class, 'resetPassword'])->name('reset');
    });

    // Support
    Route::prefix('support')->name('support.')->group(function () {
        Route::get('/', [SuperAdminController::class, 'supportIndex'])->name('index');
        Route::get('/{id}', [SuperAdminController::class, 'viewSupportTicket'])->name('view');
        Route::post('/{id}/resolve', [SuperAdminController::class, 'resolveSupportTicket'])->name('resolve');
    });

    // Cross-system super admin handoff to Finance
    // (was previously only defined in routes/super_admin.php, which this
    // app never actually loads - see bootstrap/app.php - so this button had
    // never worked)
    Route::post('/handoff/issue', [HandoffController::class, 'issueFromAcademicsSuperAdmin'])->name('handoff.super.issue');
    Route::get('/handoff/consume', [HandoffController::class, 'consumeSuperAdminFromFinance'])->name('handoff.super.consume');
});
