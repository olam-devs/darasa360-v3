<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AttendanceController;
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
use App\Http\Controllers\ParentPaymentController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPaymentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\tables\Basic as TablesBasic;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\TimetableDocumentController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\CalendarController;
use App\Http\Middleware\InitializeTenantDatabase;
use App\Http\Middleware\LogRequests;
use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\Student;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Hash;


Route::middleware([InitializeTenantDatabase::class, LogRequests::class])
  ->prefix('/students')
  ->group(function () {
    Route::get('/', [StudentController::class, 'studentHome'])->name('student.dashboard');
    Route::get('/results', [StudentController::class, 'studentResults'])->name('student.results');
    Route::get('/results/download', [StudentController::class, 'downloadResults'])->name('student.results.download');

    Route::get('/attendance', [StudentController::class, 'attendanceHistory'])->name('student.attendance');

    Route::get('/class-attendance', [StudentController::class, 'classAttendanceHistory'])->name('student.classAttendance');
    Route::get('/assignments', [StudentController::class, 'viewAssignments'])->name('student.assignments');

    Route::get('/attendances/by-class/{classId}', [StudentController::class, 'getClassAttendances']);

    Route::get('/notes', [StudentController::class, 'viewNotes'])->name('student.notes');

    Route::get('/announcements/create', [AnnouncementController::class, 'create'])->name('announcements.create');
    Route::post('/announcements', [AnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/class-announcements', [AnnouncementController::class, 'studentAnnouncements'])->name('announcements.index');
    Route::post('/announcements/{id}/update', [AnnouncementController::class, 'update'])->name('announcements.update');
    Route::get('/download/{id}', [AnnouncementController::class, 'download'])->name('announcements.download');
    Route::resource('announcements', AnnouncementController::class)->except(['edit', 'update', 'show', 'index', 'create', 'store']);
  });

Route::middleware([LogRequests::class])
  ->prefix('timetables')->group(function () {
    Route::get('/', [TimetableDocumentController::class, 'index'])->name('timetables.index');
    Route::post('/timetables', [TimetableDocumentController::class, 'store'])->name('timetables.store');
    Route::get('/timetables/download/{id}', [TimetableDocumentController::class, 'download'])->name('timetables.download');
    Route::delete('/timetables/{id}', [TimetableDocumentController::class, 'destroy'])->name('timetables.destroy');
  });



Route::middleware([LogRequests::class])
  ->prefix('payments')->group(function () {
    Route::get('/', [ParentPaymentController::class, 'index'])->name('index');
   Route::post('/upload', [ParentPaymentController::class, 'uploadPayment'])->name('parent.payments.upload');
    Route::get('/download/{id}', [ParentPaymentController::class, 'downloadReceipt'])->name('parent.payments.download');
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

// Support Tickets — defined once in owner_routes.php

// Calendar (Accessible by Students)
Route::middleware([LogRequests::class])->group(function () {
  Route::get('/calendar', [CalendarController::class, 'modernCalendar'])->name('calendar.index');
});
