<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
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
use App\Http\Controllers\ClassController;
use App\Http\Controllers\ClassSubjectController;
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
use App\Http\Controllers\GradeController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\StaffPromotionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\tables\Basic as TablesBasic;
use App\Http\Middleware\InitializeTenantDatabase;
use App\Http\Middleware\LogRequests;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Hash;



Route::middleware([InitializeTenantDatabase::class, LogRequests::class, 'tenant.role:Owner'])
  ->prefix('owner')
  ->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'ownerDashboard'])->name('owner.dashboard');
    Route::get('/manage-staff', [OwnerController::class, 'manageStaff'])->name('owner.manageStaff');
    Route::get('/view-staffs', [OwnerController::class, 'viewStaffs'])->name('staffs.view');


    Route::post('/staffs/{id}/toggle-status', [OwnerController::class, 'toggleStatus'])->name('staffs.toggleStatus');
    Route::get('/staffs/{id}/info', [OwnerController::class, 'getStaffInfo']);

    Route::get('/owner/get-classes', [OwnerController::class, 'getClasses'])->name('owner.get-classes');
    // Route::get('/school-grades', [OwnerController::class, 'schoolGrades'])->name('owner.schoolGrades');
    Route::post('/add-staff', [OwnerController::class, 'addStaff'])->name('staffs.store');
    Route::put('/update-staff/{id}', [OwnerController::class, 'updateStaff'])->name('staffs.update');
    Route::delete('/delete-staff/{id}', [OwnerController::class, 'deleteStaff'])->name('staffs.delete');

    Route::get('/promotions', [PromotionController::class, 'index'])->name('owner.promotions.index');
    Route::get('/promotions/students/{classId}', [PromotionController::class, 'getStudents'])->name('owner.promotions.students');
    Route::post('/promotions/promote', [PromotionController::class, 'promote'])->name('owner.promotions.promote');

    Route::get('/staff-promotion', [StaffPromotionController::class, 'index'])->name('owner.staff-promotion.index');
    Route::get('/staff-promotion/staff/{roleId}', [StaffPromotionController::class, 'getStaff'])->name('owner.staff-promotion.staff');
    Route::post('/staff-promotion/promote', [StaffPromotionController::class, 'promote'])->name('owner.staff-promotion.promote');

    Route::post('/update-user-status/{id}', [OwnerController::class, 'updateUserStatus'])->name('owner.update.user.status');
  });


Route::middleware([InitializeTenantDatabase::class, LogRequests::class, 'tenant.role:Owner'])
  ->prefix('owner')->group(function () {
    Route::get('/grades/type/{type}', [GradeController::class, 'showGradesByType'])->name('grades.showByType');

    Route::get('/grades', [GradeController::class, 'index'])->name('owner.grades.index');
    Route::post('/grades/store', [GradeController::class, 'store'])->name('owner.grades.store');
    Route::delete('/grades/{id}/delete', [GradeController::class, 'destroy'])->name('owner.grades.destroy');
  });


Route::middleware([InitializeTenantDatabase::class, LogRequests::class, 'tenant.role:Owner,Headmaster'])
  ->prefix('owner/classes')
  ->group(function () {
    Route::get('/', [ClassController::class, 'index'])->name('classes.index');
    Route::post('/add', [ClassController::class, 'store'])->name('classes.store');
    Route::put('/update/{id}', [ClassController::class, 'update'])->name('classes.update');
    Route::delete('/delete/{id}', [ClassController::class, 'delete'])->name('classes.delete');

    Route::post('{id}/assign-subjects', [ClassSubjectController::class, 'saveAssignedSubjects'])->name('classes.subjects.save');
    Route::post('/add-stream', [ClassController::class, 'storeStream'])->name('streams.store');
    Route::put('/update-stream/{id}', [ClassController::class, 'updateStream'])->name('streams.update');
    Route::delete('/delete-stream/{id}', [ClassController::class, 'deleteStream'])->name('streams.delete');
  });


Route::middleware([InitializeTenantDatabase::class, LogRequests::class, 'tenant.role:Owner,Accountant'])
  ->prefix('owner/students')
  ->group(function () {
    Route::get('/', [StudentController::class, 'manageStudents'])->name('students.index');
    Route::get('/export', [OwnerController::class, 'exportStudents'])->name('students.export');

    Route::get('/template', [StudentController::class, 'downloadStudentTemplate'])->name('students.template');
    Route::post('/add', [StudentController::class, 'addStudent'])->name('students.store');
    Route::post('/import', [StudentController::class, 'importBulk'])->name('students.import');
    Route::get('/{id}/edit', [StudentController::class, 'editStudent'])->name('students.edit');
    Route::put('/update/{id}', [StudentController::class, 'updateStudent'])->name('students.update');
    Route::delete('/delete/{id}', [StudentController::class, 'deleteStudent'])->name('students.delete');
    Route::post('/{student}/update-comment', [StudentController::class, 'updateComment'])->name('students.comment.update');
  });



Route::middleware([InitializeTenantDatabase::class, LogRequests::class, 'tenant.role:Owner'])
  ->prefix('/owner/subjects')->group(function () {
    Route::get('/', [SubjectController::class, 'listSubjects'])->name('subjects.list');
    Route::get('/create', [SubjectController::class, 'showAddSubjectForm'])->name('subjects.create');
    Route::post('/', [SubjectController::class, 'saveNewSubject'])->name('subjects.store');
    Route::get('/{subject}/edit', [SubjectController::class, 'showEditSubjectForm'])->name('subjects.edit');
    Route::put('/{subject}', [SubjectController::class, 'updateSubject'])->name('subjects.update');
    Route::delete('/{subject}', [SubjectController::class, 'deleteSubject'])->name('subjects.delete');
  });


Route::middleware([InitializeTenantDatabase::class, LogRequests::class])
  ->prefix('/owner/attendance')->group(function () {
    Route::get('/', [AttendanceController::class, 'listAttendanceTypes'])->name('attendance.home');
    Route::post('/create', [AttendanceController::class, 'store'])->name('owner.attendance.create');
    Route::put('/attendance/{id}/update', [AttendanceController::class, 'update'])->name('owner.attendance.update');
    Route::delete('/attendance/{id}/delete', [AttendanceController::class, 'destroy'])->name('owner.attendance.delete');
    Route::get('/daily-attendance', [AttendanceController::class, 'TeacherAttendanceTypes'])->name('owner.daily-attendance');
    Route::post('/take-attendance', [AttendanceController::class, 'takeAttendance'])->name('ownertakeAttendance');
    Route::post('/teacher/submit-attendance', [AttendanceController::class, 'submitAttendance'])->name('teacher.submitAttendance');
    Route::get('/download-template/{id}', [AttendanceController::class, 'downloadAttendanceTemplate'])->name('owner.attendance.download');
    Route::post('/upload-template/{id}', [AttendanceController::class, 'uploadAttendanceTemplate'])->name('owner.attendance.upload');
  });


Route::middleware([InitializeTenantDatabase::class, LogRequests::class, 'tenant.role:Owner'])
  ->prefix('/owner/passwords')->group(function () {
    Route::get('/', [OwnerController::class, 'schoolPasswords'])->name('schooladmin.passwords');
    Route::post('/admin/change-password', [OwnerController::class, 'changePassword'])->name('schooladmin.passwords.change');
  });

// Suggestions
Route::middleware([LogRequests::class])->group(function () {
  Route::get('/suggestions', [SuggestionController::class, 'suggestionPage'])->name('suggestionPage');
  Route::post('/suggestions', [SuggestionController::class, 'store'])->name('suggestions.store');
  Route::post('/suggestions/mark-seen/{id}', [SuggestionController::class, 'markSeen'])->name('suggestions.markSeen');
  Route::delete('/suggestions/{id}', [SuggestionController::class, 'destroy'])->name('suggestions.destroy');
  Route::post('/suggestions/{id}/reply', [SuggestionController::class, 'reply'])->name('suggestions.reply');
  Route::get('/get-subjects-by-class/{classId}', [SuggestionController::class, 'getSubjectsByClass'])->name('get.subjects.by.class');
});

// Support Tickets
Route::middleware([InitializeTenantDatabase::class, LogRequests::class])->group(function () {
  Route::get('/support', [SupportTicketController::class, 'index'])->name('support.index');
  Route::post('/support', [SupportTicketController::class, 'store'])->name('support.store');
  Route::get('/support/{id}', [SupportTicketController::class, 'show'])->name('support.show');
  Route::post('/support/{id}/comment', [SupportTicketController::class, 'addComment'])->name('support.comment');
  Route::post('/support/{id}/status', [SupportTicketController::class, 'updateStatus'])->name('support.status');
  Route::post('/support/{id}/assign', [SupportTicketController::class, 'assign'])->name('support.assign');
  Route::post('/support/{id}/priority', [SupportTicketController::class, 'updatePriority'])->name('support.priority');
  Route::delete('/support/{id}', [SupportTicketController::class, 'destroy'])->name('support.destroy');

  // Escalation routes
  Route::post('/support/{id}/escalate', [SupportTicketController::class, 'escalateTicket'])->name('support.escalate');
  Route::get('/support/escalated/list', [SupportTicketController::class, 'escalatedTickets'])->name('support.escalated');
});

// Assignments Routes (for owner portal)
Route::middleware([InitializeTenantDatabase::class, LogRequests::class])
  ->prefix('assignments')
  ->group(function () {
    Route::get('/', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::post('/store', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('/{assignment}/edit', [AssignmentController::class, 'edit'])->name('assignments.edit');
    Route::get('/download/{id}', [AssignmentController::class, 'download'])->name('assignments.download');
    Route::get('/class-subjects/{classId}', [AssignmentController::class, 'getSubjectsForClass']);
    Route::put('/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
  });

// Calendar (Accessible by Owner)
Route::middleware([LogRequests::class])->group(function () {
  Route::get('/calendar', [CalendarController::class, 'modernCalendar'])->name('calendar.index');
});

// SMS Routes (for owner/admin portal)
Route::middleware([InitializeTenantDatabase::class, LogRequests::class])
  ->prefix('/sms')->name('sms.')->group(function () {
    Route::get('/send', [\App\Http\Controllers\SmsController::class, 'showForm'])->name('send');
    Route::post('/send', [\App\Http\Controllers\SmsController::class, 'sendSms'])->name('send.process');
  });
