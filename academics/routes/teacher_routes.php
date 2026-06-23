<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AssignmentController;
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
use App\Http\Controllers\ExamController;
use App\Http\Controllers\ExamMarkController;
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
use App\Http\Controllers\HeadMasterController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\tables\Basic as TablesBasic;
use App\Http\Controllers\TeacherController;
use App\Http\Middleware\InitializeTenantDatabase;
use App\Http\Middleware\LogRequests;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Hash;



Route::middleware([InitializeTenantDatabase::class, LogRequests::class])
  ->prefix('teachers')->group(function () {
    Route::get('/', [DashboardController::class, 'teacherDashboard'])->name('teachers.dashboard');
    Route::get('/view', [TeacherController::class, 'viewTeachers'])->name('teachers.view');
    Route::get('/notes', [TeacherController::class, 'viewNotes'])->name('teachers.notes');
    Route::post('/add-notes', [TeacherController::class, 'addNotes'])->name('teacher.notes.store');
    Route::delete('/notes/delete/{id}', [TeacherController::class, 'deleteNotes'])->name('teacher.notes.delete');
    Route::get('/notes/download/{id}', [TeacherController::class, 'downloadNotes'])->name('teacher.notes.download');
    Route::get('/assign', [TeacherController::class, 'viewTeachersPerClass'])->name('teachers.viewTeachersPerClass');
    Route::get('/{teacher}/assign', [TeacherController::class, 'showAssignForm'])->name('teachers.assign.form');
    Route::post('/assign', [TeacherController::class, 'storeTeacherAssignment'])->name('teachers.assign.store');
    Route::get('/class/{id}/subjects', [TeacherController::class, 'getSubjects'])->name('classes.subjects');


    Route::get('/class/{class}/subjects', [TeacherController::class, 'getSubjectsByClass']);
    Route::get('/edit/{id}/json', [TeacherController::class, 'getTeacherAssignmentsJson']);
  });

// Teacher student management routes
Route::middleware([InitializeTenantDatabase::class, LogRequests::class])
  ->prefix('teachers/students')
  ->group(function () {
    Route::get('/', [StudentController::class, 'manageStudents'])->name('teachers.students.index');
    Route::get('/template', [StudentController::class, 'downloadStudentTemplate'])->name('teachers.students.template');
    Route::post('/add', [StudentController::class, 'addStudent'])->name('teachers.students.store');
    Route::post('/import', [StudentController::class, 'importBulk'])->name('teachers.students.import');
    Route::get('/{id}/edit', [StudentController::class, 'editStudent'])->name('teachers.students.edit');
    Route::put('/update/{id}', [StudentController::class, 'updateStudent'])->name('teachers.students.update');
    Route::delete('/delete/{id}', [StudentController::class, 'deleteStudent'])->name('teachers.students.delete');
    Route::post('/{student}/update-comment', [StudentController::class, 'updateComment'])->name('teachers.students.comment.update');
  });


Route::middleware([InitializeTenantDatabase::class, LogRequests::class])
  ->prefix('/exams')
  ->group(function () {

    // Exams
    Route::get('/', [ExamController::class, 'index'])->name('exams.index');
    Route::get('/create', [ExamController::class, 'create'])->name('exams.create');
    Route::post('/store', [ExamController::class, 'store'])->name('exams.store');
    Route::delete('/destroy/{id}', [ExamController::class, 'destroy'])->name('exams.destroy');

    // Marks
    Route::get('/marks/enter', [ExamMarkController::class, 'selectClassExamSubject'])->name('examMarks.select');
    Route::post('/marks/submit', [ExamMarkController::class, 'submitMarks'])->name('examMarks.submit');
    Route::get('/marks/load-students', [ExamMarkController::class, 'loadStudents'])->name('examMarks.loadStudents');

    //results
    Route::get('/results', [TeacherController::class, 'viewResultSheet'])->name('examMarks.results');
    Route::get('/exam-marks/download', [ExamMarkController::class, 'downloadPdf'])->name('examMarks.download');
  });



Route::middleware([InitializeTenantDatabase::class, LogRequests::class])
  ->prefix('assignments')
  ->group(function () {
    Route::get('/', [AssignmentController::class, 'index'])->name('assignments.index');
    Route::get('/create', [AssignmentController::class, 'create'])->name('assignments.create');
    Route::post('/store', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::get('/{assignment}/edit', [AssignmentController::class, 'edit'])->name('assignments.edit');
    //  Route::delete('/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
    Route::get('/download/{id}', [AssignmentController::class, 'download'])->name('assignments.download');
    // Route::delete('/delete/{id}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');

    Route::get('/class-subjects/{classId}', [AssignmentController::class, 'getSubjectsForClass']);
    Route::put('/{assignment}', [AssignmentController::class, 'update'])->name('assignments.update');
    Route::delete('/{assignment}', [AssignmentController::class, 'destroy'])->name('assignments.destroy');
  });

Route::middleware([InitializeTenantDatabase::class, LogRequests::class])
  ->prefix('notes')
  ->group(function () {
    Route::get('/', [AssignmentController::class, 'notesHome'])->name('notes.home');
    Route::get('/class-subjects/{class}', [AssignmentController::class, 'getSubjectsForClass'])->name('notes.getSubjects');
    Route::post('/store', [AssignmentController::class, 'notesStore'])->name('notes.store');
    Route::delete('/{notes}', [AssignmentController::class, 'notesDestroy'])->name('notes.destroy');
    Route::get('/download/{id}', [AssignmentController::class, 'notesDownload'])->name('notes.download');
  });


// suggestion

// Route::get('/suggestions', [SuggestionController::class, 'suggestionPage'])->name('suggestionPage');
// Route::post('/suggestions', [SuggestionController::class, 'store'])->name('suggestions.store');
// Route::post('/suggestions/{id}/mark-seen', [SuggestionController::class, 'markSeen'])->name('suggestions.markSeen');

Route::middleware([LogRequests::class])->group(function () {
  Route::get('/suggestions', [SuggestionController::class, 'suggestionPage'])->name('suggestionPage');
  Route::post('/suggestions', [SuggestionController::class, 'store'])->name('suggestions.store');
  // Route::post('/suggestions/{id}/mark-seen', [SuggestionController::class, 'markSeen'])->name('suggestions.markSeen');

  Route::post('/suggestions/mark-seen/{id}', [SuggestionController::class, 'markSeen'])->name('suggestions.markSeen');
  Route::delete('/suggestions/{id}', [SuggestionController::class, 'destroy'])->name('suggestions.destroy');
  Route::get('/get-subjects-by-class/{classId}', [SuggestionController::class, 'getSubjectsByClass'])->name('get.subjects.by.class');

  Route::post('/suggestions/{id}/reply', [SuggestionController::class, 'reply'])->name('suggestions.reply');
});

// Support Tickets — defined once in owner_routes.php

// Modern UI Routes - Calendar (Accessible by Teachers)
Route::middleware([LogRequests::class])->group(function () {
  Route::get('/calendar', [CalendarController::class, 'modernCalendar'])->name('calendar.index');
  Route::get('/exams/calendar', [CalendarController::class, 'modernCalendar'])->name('exams.calendar');
});

// Class Teacher Report Card Routes
Route::middleware([InitializeTenantDatabase::class, LogRequests::class])
  ->prefix('class-teacher/report-cards')
  ->group(function () {
    Route::get('/', [\App\Http\Controllers\ReportCardController::class, 'classTeacherIndex'])->name('classTeacher.reportCards.index');
    Route::get('/{id}', [\App\Http\Controllers\ReportCardController::class, 'classTeacherShow'])->name('classTeacher.reportCards.show');
    Route::post('/{configId}/send-sms', [\App\Http\Controllers\ReportCardController::class, 'sendReportSMS'])->name('classTeacher.reportCards.send-sms');
    Route::get('/view/{reportCardId}', [\App\Http\Controllers\ReportCardController::class, 'classTeacherViewReport'])->name('classTeacher.reportCards.view');
    Route::get('/download/{reportCardId}', [\App\Http\Controllers\ReportCardController::class, 'classTeacherDownloadPDF'])->name('classTeacher.reportCards.download');
  });

// SMS Routes (for teachers and class teachers)
Route::middleware([InitializeTenantDatabase::class, LogRequests::class])
  ->prefix('/sms')->name('sms.')->group(function () {
    Route::get('/send', [\App\Http\Controllers\SmsController::class, 'showForm'])->name('send');
    Route::post('/send', [\App\Http\Controllers\SmsController::class, 'sendSms'])->name('send.process');
  });
