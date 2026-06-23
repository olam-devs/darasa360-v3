<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\dashboard\Analytics;
use App\Http\Controllers\layouts\WithoutMenu;
use App\Http\Controllers\layouts\WithoutNavbar;
use App\Http\Controllers\layouts\Fluid;
use App\Http\Controllers\layouts\Container;
use App\Http\Controllers\layouts\Blank;
use App\Http\Controllers\pages\AccountSettingsAccount;
use App\Http\Controllers\pages\AccountSettingsNotifications;
use App\Http\Controllers\pages\AccountSettingsConnections;
use App\Http\Controllers\pages\MiscError;
use App\Http\Controllers\pages\MiscUnderMaintenance;
use App\Http\Controllers\authentications\LoginBasic;
use App\Http\Controllers\authentications\RegisterBasic;
use App\Http\Controllers\authentications\ForgotPasswordBasic;
use App\Http\Controllers\AuthLogicController;
use App\Http\Controllers\cards\CardBasic;
use App\Http\Controllers\user_interface\Accordion;
use App\Http\Controllers\user_interface\Alerts;
use App\Http\Controllers\user_interface\Badges;
use App\Http\Controllers\user_interface\Buttons;
use App\Http\Controllers\user_interface\Carousel;
use App\Http\Controllers\user_interface\Collapse;
use App\Http\Controllers\user_interface\Dropdowns;
use App\Http\Controllers\user_interface\Footer;
use App\Http\Controllers\user_interface\ListGroups;
use App\Http\Controllers\user_interface\Modals;
use App\Http\Controllers\user_interface\Navbar;
use App\Http\Controllers\user_interface\Offcanvas;
use App\Http\Controllers\user_interface\PaginationBreadcrumbs;
use App\Http\Controllers\user_interface\Progress;
use App\Http\Controllers\user_interface\Spinners;
use App\Http\Controllers\user_interface\TabsPills;
use App\Http\Controllers\user_interface\Toasts;
use App\Http\Controllers\user_interface\TooltipsPopovers;
use App\Http\Controllers\user_interface\Typography;
use App\Http\Controllers\extended_ui\PerfectScrollbar;
use App\Http\Controllers\extended_ui\TextDivider;
use App\Http\Controllers\icons\Boxicons;
use App\Http\Controllers\form_elements\BasicInput;
use App\Http\Controllers\form_elements\InputGroups;
use App\Http\Controllers\form_layouts\VerticalForm;
use App\Http\Controllers\form_layouts\HorizontalForm;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\tables\Basic as TablesBasic;
use App\Http\Middleware\LogRequests;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;






Route::middleware([LogRequests::class])
  ->prefix('admins')->group(function () {

    Route::get('/manage-logs', [AdminController::class, 'manageLogs'])->name('admin.manageLogs');
    Route::get('/manage-logs/school/{school_id}', [AdminController::class, 'manageLogsBySchool'])->name('admin.manageLogsBySchool');

    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
    Route::get('/manage-admins', [AdminController::class, 'manageAdmins']);
    Route::get('/manage-schools', [AdminController::class, 'manageSchools'])->name('admin.manageSchools');
    Route::patch('/schools/{id}/update-package', [AdminController::class, 'updatePackage'])->name('schools.update.package');
    Route::get('/school/{school}/stats', [AdminController::class, 'schoolStats'])->name('superadmin.school.stats');
    Route::post('/update-admin/{id}', [AdminController::class, 'updateAdmin'])->name('admins.update');
    Route::post('/store-admin', [AdminController::class, 'store']);
    Route::delete('/delete-admin/{id}', [AdminController::class, 'delete']);

    Route::get('/manage-schools', [AdminController::class, 'manageSchools'])->name('admin.manageSchools');
    Route::post('/add-school', [AdminController::class, 'addSchool'])->name('admin.addSchool');
    Route::post('/update-school/{id}', [AdminController::class, 'updateSchool'])->name('admin.updateSchool');
    Route::delete('/delete-school/{id}', [AdminController::class, 'deleteSchool'])->name('admin.deleteSchool');
    Route::post('/admins/schools/{id}/toggle-status', [AdminController::class, 'toggleSchoolStatus'])->name('admin.toggleSchoolStatus');

    Route::get('/schools/{id}/admin-info', [AdminController::class, 'fetchAdminInfo']);

    Route::get('/manage-locations', [AdminController::class, 'manageLocation'])->name('admin.location');
    Route::post('/locations', [AdminController::class, 'addLocation'])->name('locations.store');
    Route::put('/locations/{id}', [AdminController::class, 'updateLocation'])->name('locations.update');
    Route::delete('/locations/{id}', [AdminController::class, 'deleteLocation'])->name('locations.delete');


    Route::get('/password-management', [AdminController::class, 'managePasswords'])->name('admin.passwordManagement');
    Route::post('/change-password', [AdminController::class, 'changePassword'])->name('admin.changePassword');

    Route::get('/sms-management', [SmsController::class, 'index'])->name('super.sms.index');
    Route::post('/super/sms/update/{school_id}', [SmsController::class, 'update'])->name('super.sms.update');
  });



Route::middleware([LogRequests::class])
  ->prefix('sms')->group(function () {
    Route::get('/send', [SmsController::class, 'showForm'])->name('sms.send.form');
    Route::post('/send', [SmsController::class, 'sendSms'])->name('sms.send.process');
    Route::get('/get-students/{classId}', [SmsController::class, 'getStudents']);
    Route::get('/search-students/{classId}', [SmsController::class, 'searchStudents']);
  });

Route::get('/logout', function () {
  session()->flush();
  Auth::logout();
  return redirect('/login');
})->name('logout');


/*
|--------------------------------------------------------------------------
| Super Admin Routes (New)
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
        Route::post('/super-admin', [SuperAdminController::class, 'createSuperAdmin'])->name('create_super');
    });
});
