<?php

use App\Http\Controllers\AcademicController;
use App\Http\Controllers\AccountantController;
use App\Http\Controllers\AccountantReceiptController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AssignmentController;
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
use App\Http\Controllers\HeadMasterController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\OwnerController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\tables\Basic as TablesBasic;
use App\Http\Controllers\TeacherController;
use App\Http\Middleware\InitializeTenantDatabase;
use App\Http\Middleware\LogRequests;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\Hash;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Accounting;

Route::middleware([InitializeTenantDatabase::class, LogRequests::class, 'tenant.role:Headmaster'])
  ->prefix('/headmaster')->group(function () {
    Route::get('/', [DashboardController::class, 'headmasterDashboard'])->name('headMaster.dashboard');
    Route::get('/manage-staff', [OwnerController::class, 'manageStaff'])->name('headMaster.manageStaff');
  });

// Headmaster student management routes
Route::middleware([InitializeTenantDatabase::class, LogRequests::class, 'tenant.role:Headmaster'])
  ->prefix('headmaster/students')
  ->group(function () {
    Route::get('/', [StudentController::class, 'manageStudents'])->name('headmaster.students.index');
    Route::get('/template', [StudentController::class, 'downloadStudentTemplate'])->name('headmaster.students.template');
    Route::post('/add', [StudentController::class, 'addStudent'])->name('headmaster.students.store');
    Route::post('/import', [StudentController::class, 'importBulk'])->name('headmaster.students.import');
    Route::get('/{id}/edit', [StudentController::class, 'editStudent'])->name('headmaster.students.edit');
    Route::put('/update/{id}', [StudentController::class, 'updateStudent'])->name('headmaster.students.update');
    Route::delete('/delete/{id}', [StudentController::class, 'deleteStudent'])->name('headmaster.students.delete');
    Route::post('/{student}/update-comment', [StudentController::class, 'updateComment'])->name('headmaster.students.comment.update');
  });



Route::middleware([InitializeTenantDatabase::class, LogRequests::class, 'tenant.role:Academic'])
  ->prefix('/academic')->group(function () {
    Route::get('/', [DashboardController::class, 'academicDashboard'])->name('academic.dashboard');
    Route::get('/notes', [TeacherController::class, 'viewNotes'])->name('academic.notes');
    Route::post('/add-notes', [TeacherController::class, 'addNotes'])->name('academic.notes.store');
    Route::delete('/notes/delete/{id}', [TeacherController::class, 'deleteNotes'])->name('academic.notes.delete');
    Route::get('/notes/download/{id}', [TeacherController::class, 'downloadNotes'])->name('academic.notes.download');

    Route::get('/student-results', [AcademicController::class, 'studentResults'])->name('academic.studentResults');
    Route::post('/get-student-results', [AcademicController::class, 'getStudentResults'])->name('academic.studentResults.process');
    Route::get('/get-student-results', [AcademicController::class, 'studentResults'])->name('academic.get-student-results');



    Route::post('/student-results/download', [AcademicController::class, 'downloadStudentResults'])
      ->name('academic.download.student_results');


    Route::get('/generate-results', [AcademicController::class, 'showGenerateResultsForm'])->name('academic.generateResults.form');

    Route::post('/generate-results', [AcademicController::class, 'generateResults'])->name('academic.generateResults.process');

    Route::post('/results/download', [AcademicController::class, 'downloadExamResults'])
      ->name('academic.download.exam_results');

    // Download generated results as PDF
    // Route::get('/results/download-pdf/{class_id}/{exam_id}/{grading_type}', [AcademicController::class, 'downloadPdf'])->name('results.downloadPdf');

    Route::post('/results/send-to-headteacher', [AcademicController::class, 'sendToHeadteacher'])->name('results.sendToHeadteacher');

    Route::post('/results/send-to-class-teacher', [AcademicController::class, 'sendToClassTeacher'])->name('results.sendToClassTeacher');


    //new
    // Marks verification dashboard
    Route::get('/verification', [AcademicController::class, 'academicVerificationDashboard'])
      ->name('academic.verification');
    Route::get('/verification/exams/{classId}', [AcademicController::class, 'getExamsByClass'])
      ->name('academic.verification.exams');
    Route::post('/verification/fetch', [AcademicController::class, 'fetchVerificationData'])
      ->name('academic.verification.fetch');
    Route::post('/verification/toggle', [AcademicController::class, 'toggleVerification'])
      ->name('academic.toggleVerification');
    Route::post('/verification/batch', [AcademicController::class, 'batchVerification'])
      ->name('academic.batchVerification');

    // Report Card Routes
    Route::get('/report-cards', [ReportCardController::class, 'index'])->name('report-cards.index');
    Route::get('/report-cards/create', [ReportCardController::class, 'create'])->name('report-cards.create');
    Route::post('/report-cards', [ReportCardController::class, 'store'])->name('report-cards.store');
    Route::get('/report-cards/{id}', [ReportCardController::class, 'show'])->name('report-cards.show');
    Route::delete('/report-cards/{id}', [ReportCardController::class, 'destroy'])->name('report-cards.destroy');
    Route::post('/report-cards/{configId}/generate', [ReportCardController::class, 'generateReportCards'])->name('report-cards.generate');
    Route::post('/report-cards/{configId}/publish', [ReportCardController::class, 'publish'])->name('report-cards.publish');
    Route::post('/report-cards/{configId}/send-sms', [ReportCardController::class, 'sendReportSMS'])->name('report-cards.send-sms');

    // Parent Letter Routes (NEW)
    Route::get('/report-cards/{id}/parent-letter', [ReportCardController::class, 'editParentLetter'])->name('report-cards.parent-letter.edit');
    Route::post('/report-cards/{id}/parent-letter', [ReportCardController::class, 'storeParentLetter'])->name('report-cards.parent-letter.store');

    // Comment Routes
    Route::get('/report-cards/{configId}/students/{studentId}/comments', [ReportCardController::class, 'manageComments'])->name('report-cards.comments');
    Route::post('/report-cards/comments', [ReportCardController::class, 'storeComment'])->name('report-cards.comments.store');
    Route::post('/report-cards/{configId}/apply-templates', [ReportCardController::class, 'applyDefaultTemplates'])->name('report-cards.apply-templates');

    // Export Routes
    Route::get('/report-cards/pdf/{reportCardId}', [ReportCardController::class, 'exportPDF'])->name('report-cards.pdf');
    Route::get('/report-cards/{configId}/bulk-pdf', [ReportCardController::class, 'bulkExportPDF'])->name('report-cards.bulk-pdf');
    Route::get('/report-cards/{configId}/export-individual', [ReportCardController::class, 'bulkExportIndividual'])->name('report-cards.export-individual');

    // Enhanced Export Routes (with QR codes and modern design)
    Route::get('/report-cards/pdf-enhanced/{reportCardId}', [ReportCardController::class, 'exportPDFEnhanced'])->name('report-cards.pdf-enhanced');
    Route::get('/report-cards/{configId}/bulk-pdf-enhanced', [ReportCardController::class, 'bulkExportPDFEnhanced'])->name('report-cards.bulk-pdf-enhanced');

    // Mass Distribution Routes
    Route::post('/report-cards/{configId}/mass-distribute', [ReportCardController::class, 'massDistribute'])->name('report-cards.mass-distribute');
    Route::post('/report-cards/distribute-all', [ReportCardController::class, 'distributeAll'])->name('report-cards.distribute-all');
    Route::get('/report-cards/{configId}/distribution-status', [ReportCardController::class, 'distributionStatus'])->name('report-cards.distribution-status');

    // Calendar Routes
    Route::get('/calendar', [CalendarController::class, 'modernCalendar'])->name('academic.calendar.index');
  });

// Academic student management routes
Route::middleware([InitializeTenantDatabase::class, LogRequests::class, 'tenant.role:Academic'])
  ->prefix('academic/students')
  ->group(function () {
    Route::get('/', [StudentController::class, 'manageStudents'])->name('academic.students.index');
    Route::get('/template', [StudentController::class, 'downloadStudentTemplate'])->name('academic.students.template');
    Route::post('/add', [StudentController::class, 'addStudent'])->name('academic.students.store');
    Route::post('/import', [StudentController::class, 'importBulk'])->name('academic.students.import');
    Route::get('/{id}/edit', [StudentController::class, 'editStudent'])->name('academic.students.edit');
    Route::put('/update/{id}', [StudentController::class, 'updateStudent'])->name('academic.students.update');
    Route::delete('/delete/{id}', [StudentController::class, 'deleteStudent'])->name('academic.students.delete');
    Route::post('/{student}/update-comment', [StudentController::class, 'updateComment'])->name('academic.students.comment.update');
  });



Route::middleware([InitializeTenantDatabase::class, LogRequests::class, 'tenant.role:Accountant'])

    ->prefix('/accountant')->group(function () {

        // Accountant dashboard
        Route::get('/', [DashboardController::class, 'accountantDashboard'])
            ->name('accountant.dashboard');
        
        // Manage student fees page
        Route::get('/fees', [AccountantController::class, 'accountantFee'])
            ->name('accountant.fees');
        
        // Store new fee
        Route::get('/classes/{classId}/students', [AccountantController::class, 'getStudentsByClass']);

        Route::post('/fees', [AccountantController::class, 'storeFee'])->name('fees.store');
        Route::put('/fees/{fee}', [AccountantController::class, 'updateFee'])->name('fees.update');
        Route::delete('/fees/{fee}', [AccountantController::class, 'destroyFee'])->name('fees.destroy');

        Route::get('/fees/{feeId}/class/{classId}/students', [AccountantController::class, 'getFeePayments'])->name('fees.payments');
        Route::post('/fees/{feeId}/student/{studentId}/pay', [AccountantController::class, 'storePayment'])->name('fees.payment.store');
        Route::get('/fees/{feeId}/student/{studentId}/history', [AccountantController::class, 'getPaymentHistory'])->name('fees.payment.history');
        Route::get('/payments/{payment}/receipt/download', [AccountantController::class, 'downloadReceipt'])
        ->name('payments.receipt.download');

        // Student payment routes
        // Invoice routes
        Route::get('/accountant/students/{student}/invoice', [AccountantController::class, 'generateStudentInvoice'])->name('accountant.students.invoice');
        Route::post('/accountant/invoices/generate-bulk', [AccountantController::class, 'generateBulkInvoices'])->name('invoices.generate.bulk');


        // Bulk invoice routes
        Route::get('/invoices/bulk-generate/{fee?}', [InvoiceController::class, 'showBulkGeneration'])->name('invoices.bulk.generate');
        Route::get('/fees/{fee}/students-count', [InvoiceController::class, 'getStudentCount']);
        Route::get('/invoices/history', [InvoiceController::class, 'getInvoiceHistory']);
        Route::get('/invoices/{invoice}/download', [InvoiceController::class, 'downloadInvoice']);

        // Report routes
        Route::post('/fees/reports/generate', [ReportController::class, 'generateReports'])->name('fees.reports.generate');
        Route::put('/payments/{payment}/status', [ReportController::class, 'updateStatus']);


        //payment methods routes
         Route::get('/payment-methods', [AccountantController::class, 'accountantPaymentMethods'])
            ->name('accountant.paymentMethods');

          Route::post('/payment-methods/store', [AccountantController::class, 'storePaymentMethod'])
          ->name('accountant.payment.methods.store');
        Route::post('/payment-methods/{id}/update', [AccountantController::class, 'updatePaymentMethod'])->name('accountant.payment.methods.update');
        Route::delete('/payment-methods/{id}', [AccountantController::class, 'deletePaymentMethod'])->name('accountant.payment.methods.delete');


        //routes for pending payment modal

        Route::get('/fees/verify/filter', [AccountantController::class, 'Feesfilter'])->name('fees.verify.filter');
        Route::post('/fees/verify/{id}', [AccountantController::class, 'FeemarkVerified'])->name('fees.verify.mark');

        Route::get('/fees/{fee}/students', [AccountantController::class, 'getFeeStudents']);

        //general info:
           Route::get('/general-info', [ReportController::class, 'showReportDetail'])->name('accountant.general_info');
    Route::post('/general-info/save', [ReportController::class, 'saveReportDetail'])->name('accountant.report.save');
    Route::get('/general-info/delete', [ReportController::class, 'deleteReportDetail'])->name('accountant.report.delete');
        /////////////////////

        // API to get students by class
        Route::get('/students-by-class/{classId}', [AccountantController::class, 'getStudentsByClass']);


        //reports:::
        Route::get('/fees/export/{format}', [AccountantController::class, 'exportFees'])->name('fees.export');

        // Reminders
        Route::post('/fees/reminders/send', [AccountantController::class, 'sendReminders'])->name('fees.reminders.send');

        // Export Students for Finance
        Route::get('/students/export-for-finance', [AccountantController::class, 'exportStudentsForFinance'])->name('accountant.students.exportFinance');

        // Invoices
        Route::post('/fees/invoices/generate', [AccountantController::class, 'generateInvoices'])->name('fees.invoices.generate');
        Route::get('/students/{student}/invoice', [AccountantController::class, 'generateStudentInvoice'])->name('students.invoice');
       
        

    Route::get('/receipts', [AccountantReceiptController::class, 'index'])->name('accountant.receipts');
    Route::post('/receipts/verify', [AccountantReceiptController::class, 'verify'])->name('accountant.receipts.verify');
    Route::post('/receipts/reject', [AccountantReceiptController::class, 'reject'])->name('accountant.receipts.reject');

    // Calendar Routes
    Route::get('/calendar', [CalendarController::class, 'modernCalendar'])->name('accountant.calendar.index');
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

// Assignments Routes (for academic, headmaster, class teacher portals)
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

// Support Tickets — defined once in owner_routes.php

// Calendar & Events (NEW)
Route::middleware([LogRequests::class])->prefix('calendar')->name('calendar.')->group(function () {
  Route::get('/', [CalendarController::class, 'modernCalendar'])->name('index');
  Route::get('/modern', [CalendarController::class, 'modernCalendar'])->name('modern');
  Route::post('/create', [CalendarController::class, 'createCalendar'])->name('create');
  Route::post('/events', [CalendarController::class, 'createEvent'])->name('events.create');
  Route::put('/events/{id}', [CalendarController::class, 'updateEvent'])->name('events.update');
  Route::delete('/events/{id}', [CalendarController::class, 'deleteEvent'])->name('events.delete');
  Route::get('/events/upcoming', [CalendarController::class, 'upcomingEvents'])->name('events.upcoming');
  Route::get('/events/json', [CalendarController::class, 'getEvents'])->name('events.json');
});

// Dashboard (NEW)
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Export Students for Finance
Route::get('/students/export-for-finance', [HeadMasterController::class, 'exportStudentsForFinance'])->name('headmaster.students.exportFinance');

// SMS Routes (for all roles in this file: headmaster, academic, accountant, class teacher)
Route::middleware([InitializeTenantDatabase::class, LogRequests::class])
  ->prefix('/sms')->name('sms.')->group(function () {
    Route::get('/send', [\App\Http\Controllers\SmsController::class, 'showForm'])->name('send');
    Route::post('/send', [\App\Http\Controllers\SmsController::class, 'sendSms'])->name('send.process');
  });

