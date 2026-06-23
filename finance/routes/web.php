<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AdvancePaymentController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\BankAccountController;
use App\Http\Controllers\BankAPIController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\BookFeeCategoryController;
use App\Http\Controllers\BookMonthlyCutController;
use App\Http\Controllers\BookTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeeItemController;
use App\Http\Controllers\HeadmasterManagementController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\ParentController;
use App\Http\Controllers\ParentMessengerController;
use App\Http\Controllers\PortalAssistantController;
use App\Http\Controllers\ParticularController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ScholarshipController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SuspenseAccountController;
use App\Http\Controllers\VoucherController;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('accountant.dashboard');
});

// Public Bank Webhook Endpoint (no auth required - called by bank)
Route::post('/api/bank-webhook', [BankAPIController::class, 'webhook'])->name('api.bank-webhook');

// Parent SMS/WhatsApp messenger (connect school office number via Twilio/Meta → this endpoint)
Route::post('/api/messenger/parent', [ParentMessengerController::class, 'handle'])->name('api.messenger.parent');

// Portal assistant (accountant auth, headmaster session, or parent session)
Route::middleware(['portal.session'])->group(function () {
    Route::get('api/assistant/intents', [PortalAssistantController::class, 'intents'])->name('api.assistant.intents');
    Route::post('api/assistant/ask', [PortalAssistantController::class, 'ask'])->name('api.assistant.ask');
    Route::post('api/assistant/chat', [PortalAssistantController::class, 'chat'])->name('api.assistant.chat');
    Route::post('api/assistant/report', [PortalAssistantController::class, 'report'])->name('api.assistant.report');
});
// Admin: view chatbot reports (accountant/superadmin auth)
Route::middleware(['auth'])->group(function () {
    Route::get('api/assistant/reports', [PortalAssistantController::class, 'reports'])->name('api.assistant.reports');
    Route::post('api/assistant/reports/{id}/read', [PortalAssistantController::class, 'markReportRead'])->name('api.assistant.reports.read');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/accountant-dashboard', function () {
        $settings = SchoolSetting::getSettings();

        return view('admin.accountant.dashboard', compact('settings'));
    })->name('accountant.dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['check.role:superadmin,accountant'])->prefix('accountant')->name('accountant.')->group(function () {
        // Dedicated Module Pages
        Route::get('/books', function () {
            $settings = SchoolSetting::getSettings();

            return view('admin.accountant.modules.books', compact('settings'));
        })->name('books');

        Route::get('/advance-payments', [AdvancePaymentController::class, 'page'])->name('advance-payments');

        Route::get('/reconciliation', [ReconciliationController::class, 'page'])->name('reconciliation');
        Route::get('/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'page'])->name('activity-logs');

        Route::get('/particulars', function () {
            $settings = SchoolSetting::getSettings();

            return view('admin.accountant.modules.particulars', compact('settings'));
        })->name('particulars');

        Route::get('/fee-entry', function () {
            $settings = SchoolSetting::getSettings();

            return view('admin.accountant.modules.fee-entry', compact('settings'));
        })->name('fee-entry');

        Route::get('/ledgers', function () {
            $settings = SchoolSetting::getSettings();

            return view('admin.accountant.modules.ledgers', compact('settings'));
        })->name('ledgers');

        Route::get('/particular-ledger', function () {
            $settings = SchoolSetting::getSettings();

            return view('admin.accountant.modules.particular-ledger', compact('settings'));
        })->name('particular-ledger');

        Route::get('/overdue', function () {
            $settings = SchoolSetting::getSettings();

            return view('admin.accountant.modules.overdue', compact('settings'));
        })->name('overdue');

        Route::get('/suspense', function () {
            $settings = SchoolSetting::getSettings();

            return view('admin.accountant.modules.suspense', compact('settings'));
        })->name('suspense');

        Route::get('/payroll', function () {
            $settings = SchoolSetting::getSettings();

            return view('admin.accountant.modules.payroll', compact('settings'));
        })->name('payroll');

        Route::get('/bank-api', function () {
            $settings = SchoolSetting::getSettings();

            return view('admin.accountant.modules.bank-api', compact('settings'));
        })->name('bank-api');

        Route::get('/classes', function () {
            $settings = SchoolSetting::getSettings();

            return view('admin.accountant.modules.classes', compact('settings'));
        })->name('classes');

        Route::get('/students', function () {
            $settings = SchoolSetting::getSettings();

            return view('admin.accountant.modules.students', compact('settings'));
        })->name('students');

        Route::get('/student-profile', [StudentController::class, 'studentProfilePage'])->name('student-profile');

        Route::get('/sms', [SmsController::class, 'indexAccountant'])->name('sms');
        Route::get('/phone-numbers', [SmsController::class, 'managePhoneNumbersAccountant'])->name('phone-numbers');
        Route::get('/sms-logs', [SmsController::class, 'logsAccountant'])->name('sms-logs');
        Route::post('/sms/send-overdue-reminders', [SmsController::class, 'sendOverdueReminders'])->name('sms.send-overdue-reminders');

        // Headmaster Management
        Route::get('/headmasters', [HeadmasterManagementController::class, 'index'])->name('headmasters');
        Route::post('/headmasters', [HeadmasterManagementController::class, 'store'])->name('headmasters.store');
        Route::put('/headmasters/{headmaster}', [HeadmasterManagementController::class, 'update'])->name('headmasters.update');
        Route::post('/headmasters/{headmaster}/toggle', [HeadmasterManagementController::class, 'toggleStatus'])->name('headmasters.toggle');
        Route::post('/headmasters/{headmaster}/reset-password', [HeadmasterManagementController::class, 'resetPassword'])->name('headmasters.reset-password');
        Route::delete('/headmasters/{headmaster}', [HeadmasterManagementController::class, 'destroy'])->name('headmasters.destroy');

        Route::get('/invoices-page', [LedgerController::class, 'invoicesPage'])->name('invoices-page');

        Route::get('/expenses', function () {
            $settings = SchoolSetting::getSettings();

            return view('admin.accountant.modules.expenses', compact('settings'));
        })->name('expenses');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        // Invoice download routes
        Route::get('/invoices/all-students/pdf', [LedgerController::class, 'exportAllStudentsInvoicesPdf'])->name('invoices.all-students.pdf');
        Route::get('/invoices/class/{className}/pdf', [LedgerController::class, 'exportClassInvoicesPdf'])->name('invoices.class.pdf');
        Route::get('/invoices/student/{studentId}/pdf', [LedgerController::class, 'exportStudentInvoicePdf'])->name('invoices.student.pdf');

        // Parent portal password management
        Route::get('/portal-passwords', [StudentController::class, 'portalPasswordsPage'])->name('portal-passwords');
        Route::get('api/students/portal-password/search', [StudentController::class, 'searchStudentsForPassword'])->name('api.students.portal-password.search');
        Route::post('api/students/portal-password/bulk', [StudentController::class, 'bulkSetPortalPassword'])->name('api.students.portal-password.bulk');
        Route::post('api/students/{studentId}/portal-password', [StudentController::class, 'setPortalPassword'])->name('api.students.portal-password.set');
    });

    Route::middleware(['finance.portal'])->group(function () {
        Route::redirect('invoices/create', '/accountant/invoices-page');
        Route::get('invoices', fn () => redirect()->route('accountant.invoices-page'))->name('invoices.index');
        Route::get('invoices/{invoice}', fn () => redirect()->route('accountant.invoices-page'))->name('invoices.show');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPDF'])->name('invoices.pdf');

        Route::resource('payments', PaymentController::class);
        Route::post('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
        Route::get('api/invoice-balance', [PaymentController::class, 'getInvoiceBalance'])->name('api.invoice.balance');

        Route::resource('students', StudentController::class);

        Route::resource('fee-items', FeeItemController::class);

        // Students API routes
        Route::get('api/students', [StudentController::class, 'apiIndex'])->name('api.students.index');
        Route::get('api/students/search', [StudentController::class, 'searchStudents'])->name('api.students.search');
        Route::get('api/classes', [StudentController::class, 'apiClasses'])->name('api.classes.index');

        // Student CSV Import routes
        Route::get('api/students/csv/template', [StudentController::class, 'downloadStudentTemplate'])->name('api.students.csv.template');
        Route::post('api/students/csv/upload', [StudentController::class, 'uploadStudentCsv'])->name('api.students.csv.upload');

        // Student Promotion routes
        Route::get('/student-promotion', [StudentController::class, 'promotionPage'])->name('students.promotion-page');
        Route::get('api/students/for-promotion', [StudentController::class, 'getStudentsForPromotion'])->name('api.students.for-promotion');
        Route::post('api/students/promote', [StudentController::class, 'promoteStudents'])->name('api.students.promote');
        Route::get('api/students/{studentId}/particulars', [StudentController::class, 'getStudentParticulars'])->name('api.students.particulars');
        Route::get('api/students/{studentId}/full-profile', [StudentController::class, 'getStudentFullProfile'])->name('api.students.full-profile');
        Route::get('api/students/{studentId}/particulars/{particularId}/details', [StudentController::class, 'getStudentParticularDetails'])->name('api.students.particular-details');

        // School Classes routes
        Route::get('api/school-classes', [SchoolClassController::class, 'index'])->name('api.school-classes.index');
        Route::post('api/school-classes', [SchoolClassController::class, 'store'])->name('api.school-classes.store');
        Route::get('api/school-classes/{id}', [SchoolClassController::class, 'show'])->name('api.school-classes.show');
        Route::put('api/school-classes/{id}', [SchoolClassController::class, 'update'])->name('api.school-classes.update');
        Route::delete('api/school-classes/{id}', [SchoolClassController::class, 'destroy'])->name('api.school-classes.destroy');
        Route::get('api/school-classes-dropdown', [SchoolClassController::class, 'apiClasses'])->name('api.school-classes.dropdown');
        Route::get('api/classes', [SchoolClassController::class, 'index'])->name('api.classes.index'); // Alias for ledgers page

        // Books routes
        Route::get('api/books', [BookController::class, 'index'])->name('api.books.index');
        Route::post('api/books', [BookController::class, 'store'])->name('api.books.store');
        Route::get('api/books/{id}', [BookController::class, 'show'])->name('api.books.show');
        Route::put('api/books/{id}', [BookController::class, 'update'])->name('api.books.update');
        Route::delete('api/books/{id}', [BookController::class, 'destroy'])->name('api.books.destroy');
        Route::post('api/books/create-cash-book', [BookController::class, 'createCashBook'])->name('api.books.cash-book');

        // Book fee categories (for transaction fee selection)
        Route::get('api/books/{bookId}/fee-categories', [BookFeeCategoryController::class, 'index'])->name('api.books.fee-categories.index');
        Route::post('api/books/{bookId}/fee-categories', [BookFeeCategoryController::class, 'store'])->name('api.books.fee-categories.store');
        Route::put('api/books/{bookId}/fee-categories/{categoryId}', [BookFeeCategoryController::class, 'update'])->name('api.books.fee-categories.update');
        Route::delete('api/books/{bookId}/fee-categories/{categoryId}', [BookFeeCategoryController::class, 'destroy'])->name('api.books.fee-categories.destroy');

        // Book monthly cuts
        Route::get('api/books/{bookId}/monthly-cuts', [BookMonthlyCutController::class, 'index'])->name('api.books.monthly-cuts.index');
        Route::post('api/books/{bookId}/monthly-cuts', [BookMonthlyCutController::class, 'store'])->name('api.books.monthly-cuts.store');
        Route::put('api/books/{bookId}/monthly-cuts/{cutId}', [BookMonthlyCutController::class, 'update'])->name('api.books.monthly-cuts.update');
        Route::delete('api/books/{bookId}/monthly-cuts/{cutId}', [BookMonthlyCutController::class, 'destroy'])->name('api.books.monthly-cuts.destroy');
        Route::post('api/books/{bookId}/monthly-cuts/{cutId}/apply', [BookMonthlyCutController::class, 'apply'])->name('api.books.monthly-cuts.apply');

        // Particulars routes
        Route::get('api/particulars', [ParticularController::class, 'index'])->name('api.particulars.index');
        Route::post('api/particulars', [ParticularController::class, 'store'])->name('api.particulars.store');
        Route::get('api/particulars/{id}', [ParticularController::class, 'show'])->name('api.particulars.show');
        Route::put('api/particulars/{id}', [ParticularController::class, 'update'])->name('api.particulars.update');
        Route::delete('api/particulars/{id}', [ParticularController::class, 'destroy'])->name('api.particulars.destroy');
        Route::post('api/particulars/{id}/assign-students', [ParticularController::class, 'assignStudents'])->name('api.particulars.assign');
        Route::post('api/particulars/{id}/bulk-opening-balance', [ParticularController::class, 'bulkOpeningBalance'])->name('api.particulars.bulk');
        Route::put('api/particulars/{id}/bulk-update-assignments', [ParticularController::class, 'bulkUpdateAssignments'])->name('api.particulars.bulk-update');
        Route::get('api/particulars/{id}/existing-assignments', [ParticularController::class, 'getExistingAssignments'])->name('api.particulars.existing-assignments');
        Route::get('api/particulars/{id}/students-for-new-assignment', [ParticularController::class, 'getStudentsForNewAssignment'])->name('api.particulars.students-for-new');
        Route::post('api/particulars/{particularId}/assignments', [ParticularController::class, 'createAssignment'])->name('api.particulars.create-assignment');
        Route::put('api/particulars/{particularId}/assignments/{studentId}', [ParticularController::class, 'updateAssignment'])->name('api.particulars.update-assignment');
        Route::delete('api/particulars/{particularId}/assignments/{studentId}', [ParticularController::class, 'deleteAssignment'])->name('api.particulars.delete-assignment');

        // Academic Years routes
        Route::get('api/academic-years', [AcademicYearController::class, 'index'])->name('api.academic-years.index');
        Route::get('api/academic-years/active', [AcademicYearController::class, 'active'])->name('api.academic-years.active');
        Route::get('api/academic-years/current', [AcademicYearController::class, 'current'])->name('api.academic-years.current');
        Route::post('api/academic-years', [AcademicYearController::class, 'store'])->name('api.academic-years.store');
        Route::put('api/academic-years/{id}', [AcademicYearController::class, 'update'])->name('api.academic-years.update');
        Route::post('api/academic-years/{id}/set-current', [AcademicYearController::class, 'setCurrent'])->name('api.academic-years.set-current');
        Route::delete('api/academic-years/{id}', [AcademicYearController::class, 'destroy'])->name('api.academic-years.destroy');

        // Vouchers routes
        Route::get('api/vouchers', [VoucherController::class, 'index'])->name('api.vouchers.index');
        Route::post('api/vouchers', [VoucherController::class, 'store'])->name('api.vouchers.store');
        Route::get('api/vouchers/{id}', [VoucherController::class, 'show'])->name('api.vouchers.show');
        Route::put('api/vouchers/{id}', [VoucherController::class, 'update'])->name('api.vouchers.update');
        Route::delete('api/vouchers/{id}', [VoucherController::class, 'destroy'])->name('api.vouchers.destroy');
        Route::post('api/vouchers/{id}/void', [VoucherController::class, 'void'])->name('api.vouchers.void');
        Route::post('api/vouchers/apply-advance', [VoucherController::class, 'applyAdvance'])->name('api.vouchers.apply-advance');
        Route::get('api/vouchers/search/student', [VoucherController::class, 'searchStudent'])->name('api.vouchers.search');

        // Ledgers routes
        Route::get('api/ledgers/student/{studentId}', [LedgerController::class, 'studentLedger'])->name('api.ledgers.student');
        Route::get('api/ledgers/class/{classId}', [LedgerController::class, 'classLedger'])->name('api.ledgers.class');
        Route::get('api/ledgers/book/{bookId}', [LedgerController::class, 'bookLedger'])->name('api.ledgers.book');
        Route::get('api/ledgers/particular/{particularId}', [LedgerController::class, 'particularLedger'])->name('api.ledgers.particular');

        // Ledger Export routes (PDF & CSV)
        Route::get('api/ledgers/student/{studentId}/pdf', [LedgerController::class, 'exportStudentLedgerPdf'])->name('api.ledgers.student.pdf');
        Route::get('api/ledgers/student/{studentId}/csv', [LedgerController::class, 'exportStudentLedgerCsv'])->name('api.ledgers.student.csv');
        Route::get('api/ledgers/class/{classId}/pdf', [LedgerController::class, 'exportClassLedgerPdf'])->name('api.ledgers.class.pdf');
        Route::get('api/ledgers/class/{classId}/csv', [LedgerController::class, 'exportClassLedgerCsv'])->name('api.ledgers.class.csv');
        Route::get('api/ledgers/book/{bookId}/pdf', [LedgerController::class, 'exportBookLedgerPdf'])->name('api.ledgers.book.pdf');
        Route::get('api/ledgers/book/{bookId}/csv', [LedgerController::class, 'exportBookLedgerCsv'])->name('api.ledgers.book.csv');
        Route::get('api/ledgers/all-students/pdf', [LedgerController::class, 'exportAllStudentsLedgersPdf'])->name('api.ledgers.all-students.pdf');
        Route::get('api/ledgers/particular/{particularId}/pdf', [LedgerController::class, 'exportParticularLedgerPdf'])->name('api.ledgers.particular.pdf');

        // Advance payments (PDF list)
        Route::get('api/advance-payments', [AdvancePaymentController::class, 'index'])->name('api.advance-payments.index');
        Route::get('api/advance-payments/pdf', [AdvancePaymentController::class, 'pdf'])->name('api.advance-payments.pdf');
        Route::get('api/advance-payments/csv', [AdvancePaymentController::class, 'csv'])->name('api.advance-payments.csv');

        // Book reconciliation (adjustments + correcting fee/cut vouchers)
        Route::post('api/reconciliation/adjustments', [ReconciliationController::class, 'storeAdjustment'])
            ->middleware('can.edit.history')
            ->name('api.reconciliation.adjustments');
        Route::post('api/reconciliation/bank-fee', [ReconciliationController::class, 'storeBankFee'])
            ->middleware('can.edit.history')
            ->name('api.reconciliation.bank-fee');
        Route::post('api/reconciliation/monthly-cut', [ReconciliationController::class, 'storeMonthlyCut'])
            ->middleware('can.edit.history')
            ->name('api.reconciliation.monthly-cut');
        Route::put('api/reconciliation/vouchers/{id}', [ReconciliationController::class, 'updateVoucher'])
            ->middleware('can.edit.history')
            ->name('api.reconciliation.vouchers.update');

        Route::get('api/activity-logs', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('api.activity-logs.index');
        Route::get('api/accountant-users', [\App\Http\Controllers\AccountantUserController::class, 'index'])->name('api.accountant-users.index');
        Route::put('api/accountant-users/{id}/permissions', [\App\Http\Controllers\AccountantUserController::class, 'updatePermissions'])->name('api.accountant-users.permissions');

        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/income-statement', [ReportController::class, 'incomeStatement'])->name('reports.income-statement');
        Route::get('reports/balance-sheet', [ReportController::class, 'balanceSheet'])->name('reports.balance-sheet');
        Route::get('reports/trial-balance', [ReportController::class, 'trialBalance'])->name('reports.trial-balance');
        Route::get('reports/fee-collection', [ReportController::class, 'feeCollection'])->name('reports.fee-collection');
        Route::get('reports/outstanding-balances', [ReportController::class, 'outstandingBalances'])->name('reports.outstanding');
        Route::get('reports/student-statement/{studentId?}', [ReportController::class, 'studentStatement'])->name('reports.student-statement');

        // Legacy SMS URLs → accountant modules
        Route::get('sms', fn () => redirect()->route('accountant.sms'))->name('sms.index');
        Route::get('sms/logs', fn () => redirect()->route('accountant.sms-logs'))->name('sms.logs');
        Route::get('sms/manage-phones', fn () => redirect()->route('accountant.phone-numbers'))->name('sms.manage-phones');
        Route::post('sms/send', [SmsController::class, 'sendSms'])->name('sms.send');
        Route::get('sms/balance', [SmsController::class, 'checkBalance'])->name('sms.balance');
        Route::get('sms/download-template', [SmsController::class, 'downloadPhoneTemplate'])->name('sms.download-template');
        Route::post('sms/upload-phones', [SmsController::class, 'uploadPhoneNumbers'])->name('sms.upload-phones');
        Route::put('sms/student/{studentId}/phone', [SmsController::class, 'updatePhoneNumber'])->name('sms.update-phone');

        // SMS Template routes
        Route::get('sms/templates', [SmsController::class, 'getTemplates'])->name('sms.templates');
        Route::post('sms/templates', [SmsController::class, 'saveTemplate'])->name('sms.save-template');
        Route::delete('sms/templates/{templateId}', [SmsController::class, 'deleteTemplate'])->name('sms.delete-template');

        // SMS API routes
        Route::get('api/sms/students/class/{className}', [SmsController::class, 'getStudentsByClass'])->name('api.sms.students.class');
        Route::get('api/sms/students/search', [SmsController::class, 'searchStudents'])->name('api.sms.students.search');
        Route::get('api/sms/credits', [SmsController::class, 'getSmsCredits'])->name('api.sms.credits');
        Route::get('api/sms/debug-school', [SmsController::class, 'debugSchool'])->name('api.sms.debug-school');
        Route::post('api/sms/calculate-count', [SmsController::class, 'calculateMessageSms'])->name('api.sms.calculate-count');

        // Suspense Account routes
        Route::get('api/suspense-accounts', [SuspenseAccountController::class, 'index'])->name('api.suspense.index');
        Route::post('api/suspense-accounts', [SuspenseAccountController::class, 'store'])->name('api.suspense.store');
        Route::get('api/suspense-accounts/{id}', [SuspenseAccountController::class, 'show'])->name('api.suspense.show');
        Route::put('api/suspense-accounts/{id}', [SuspenseAccountController::class, 'update'])->name('api.suspense.update');
        Route::delete('api/suspense-accounts/{id}', [SuspenseAccountController::class, 'destroy'])->name('api.suspense.destroy');
        Route::post('api/suspense-accounts/{id}/resolve', [SuspenseAccountController::class, 'resolve'])->name('api.suspense.resolve');
        Route::get('api/suspense-accounts/summary/unresolved', [SuspenseAccountController::class, 'unresolvedSummary'])->name('api.suspense.summary');

        // Payroll - Staff routes
        Route::get('api/staff', [PayrollController::class, 'indexStaff'])->name('api.staff.index');
        Route::post('api/staff', [PayrollController::class, 'storeStaff'])->name('api.staff.store');
        Route::get('api/staff/{id}', [PayrollController::class, 'showStaff'])->name('api.staff.show');
        Route::put('api/staff/{id}', [PayrollController::class, 'updateStaff'])->name('api.staff.update');
        Route::delete('api/staff/{id}', [PayrollController::class, 'destroyStaff'])->name('api.staff.destroy');
        Route::get('api/staff/{id}/payment-history', [PayrollController::class, 'staffPaymentHistory'])->name('api.staff.payment-history');
        Route::get('api/staff/csv/template', [PayrollController::class, 'downloadStaffTemplate'])->name('api.staff.csv.template');
        Route::post('api/staff/csv/upload', [PayrollController::class, 'uploadStaffCsv'])->name('api.staff.csv.upload');

        // Payroll - specific sub-routes BEFORE the {id} wildcard
        Route::get('api/payroll', [PayrollController::class, 'indexPayroll'])->name('api.payroll.index');
        Route::post('api/payroll', [PayrollController::class, 'storePayroll'])->name('api.payroll.store');
        Route::get('api/payroll/reports/monthly', [PayrollController::class, 'monthlyReport'])->name('api.payroll.monthly-report');
        Route::get('api/payroll/deductions-ledger', [PayrollController::class, 'deductionsLedger'])->name('api.payroll.deductions-ledger');
        Route::get('api/payroll/staff/{staffId}/ledger', [PayrollController::class, 'staffLedger'])->name('api.payroll.staff-ledger');
        Route::get('api/payroll/deduction-types', [PayrollController::class, 'indexDeductionTypes'])->name('api.payroll.deduction-types.index');
        Route::post('api/payroll/deduction-types', [PayrollController::class, 'storeDeductionType'])->name('api.payroll.deduction-types.store');
        Route::put('api/payroll/deduction-types/{id}', [PayrollController::class, 'updateDeductionType'])->name('api.payroll.deduction-types.update');
        Route::delete('api/payroll/deduction-types/{id}', [PayrollController::class, 'destroyDeductionType'])->name('api.payroll.deduction-types.destroy');
        // Payroll entry CRUD (wildcard {id} last)
        Route::get('api/payroll/{id}', [PayrollController::class, 'showPayroll'])->name('api.payroll.show');
        Route::put('api/payroll/{id}', [PayrollController::class, 'updatePayroll'])->name('api.payroll.update');
        Route::delete('api/payroll/{id}', [PayrollController::class, 'destroyPayroll'])->name('api.payroll.destroy');

        // Overdue amounts route
        Route::get('api/overdue-amounts', [DashboardController::class, 'getOverdueAmounts'])->name('api.overdue-amounts');

        // Bank API Integration routes
        Route::get('api/bank-transactions', [BankAPIController::class, 'index'])->name('api.bank-transactions.index');
        Route::get('api/bank-transactions/{id}', [BankAPIController::class, 'show'])->name('api.bank-transactions.show');
        Route::post('api/bank-transactions/{id}/retry', [BankAPIController::class, 'retry'])->name('api.bank-transactions.retry');
        Route::get('api/bank-api-settings', [BankAPIController::class, 'getSettings'])->name('api.bank-api-settings.get');
        Route::put('api/bank-api-settings', [BankAPIController::class, 'updateSettings'])->name('api.bank-api-settings.update');
        Route::post('api/bank-webhook/simulate', [BankAPIController::class, 'simulate'])->name('api.bank-webhook.simulate');

        // Analytics routes
        Route::get('api/analytics/custom', [AnalyticsController::class, 'getCustomAnalytics'])->name('api.analytics.custom');
        Route::get('api/analytics/class-stats', [AnalyticsController::class, 'getClassPaymentStatsForRange'])->name('api.analytics.class-stats');
        Route::get('api/analytics/{period}', [AnalyticsController::class, 'getAnalytics'])->name('api.analytics');
        Route::get('api/students/{studentId}/payment-summary', [AnalyticsController::class, 'getStudentPaymentSummary'])->name('api.students.payment-summary');

        // Bank Accounts routes
        Route::get('api/bank-accounts', [BankAccountController::class, 'index'])->name('api.bank-accounts.index');
        Route::post('api/bank-accounts', [BankAccountController::class, 'store'])->name('api.bank-accounts.store');
        Route::put('api/bank-accounts/{id}', [BankAccountController::class, 'update'])->name('api.bank-accounts.update');
        Route::delete('api/bank-accounts/{id}', [BankAccountController::class, 'destroy'])->name('api.bank-accounts.destroy');

        // Expense routes
        Route::get('api/expenses/summary', [ExpenseController::class, 'summary'])->name('api.expenses.summary');
        Route::get('api/expenses/analytics', [ExpenseController::class, 'analytics'])->name('api.expenses.analytics');
        Route::get('api/expenses', [ExpenseController::class, 'index'])->name('api.expenses.index');
        Route::post('api/expenses', [ExpenseController::class, 'store'])->name('api.expenses.store');
        Route::get('api/expenses/{id}', [ExpenseController::class, 'show'])->name('api.expenses.show');
        Route::put('api/expenses/{id}', [ExpenseController::class, 'update'])->name('api.expenses.update');
        Route::delete('api/expenses/{id}', [ExpenseController::class, 'destroy'])->name('api.expenses.destroy');
        Route::post('api/expenses/{id}/process', [ExpenseController::class, 'process'])->name('api.expenses.process');
        Route::post('api/expenses/{id}/cancel', [ExpenseController::class, 'cancel'])->name('api.expenses.cancel');

        // Book Transactions (Deposits & Withdrawals) routes
        Route::get('api/book-transactions/{bookId}', [BookTransactionController::class, 'index'])->name('api.book-transactions.index');
        Route::post('api/book-transactions/deposit', [BookTransactionController::class, 'storeDeposit'])->name('api.book-transactions.deposit');
        Route::post('api/book-transactions/withdrawal', [BookTransactionController::class, 'storeWithdrawal'])->name('api.book-transactions.withdrawal');
        Route::get('api/book-transactions/show/{id}', [BookTransactionController::class, 'show'])->name('api.book-transactions.show');
        Route::post('api/book-transactions/{id}/cancel', [BookTransactionController::class, 'cancel'])->name('api.book-transactions.cancel');
        Route::delete('api/book-transactions/{id}', [BookTransactionController::class, 'destroy'])->name('api.book-transactions.destroy');

        // Scholarship routes
        Route::get('api/scholarships', [ScholarshipController::class, 'index'])->name('api.scholarships.index');
        Route::post('api/scholarships', [ScholarshipController::class, 'store'])->name('api.scholarships.store');
        Route::put('api/scholarships/{id}', [ScholarshipController::class, 'update'])->name('api.scholarships.update');
        Route::post('api/scholarships/{id}/deactivate', [ScholarshipController::class, 'deactivate'])->name('api.scholarships.deactivate');
        Route::get('api/scholarships/student/{studentId}', [ScholarshipController::class, 'studentScholarships'])->name('api.scholarships.student');
        Route::get('api/scholarships/student/{studentId}/details', [ScholarshipController::class, 'studentDetailsForScholarship'])->name('api.scholarships.student.details');
        Route::post('api/scholarships/check', [ScholarshipController::class, 'checkScholarship'])->name('api.scholarships.check');
        Route::get('api/scholarships/summary/by-particular', [ScholarshipController::class, 'summaryByParticular'])->name('api.scholarships.summary');
    });
});

// Parent Portal Routes (using custom session-based auth) - OUTSIDE main auth middleware
Route::prefix('parent')->name('parent.')->middleware('parent.auth')->group(function () {
    Route::get('/dashboard', [ParentController::class, 'dashboard'])->name('dashboard');
    Route::get('/fees', [ParentController::class, 'fees'])->name('fees');
    Route::get('/invoices', [ParentController::class, 'invoices'])->name('invoices');
    Route::get('/invoices/download', [ParentController::class, 'downloadInvoice'])->name('invoices.download');
    Route::get('/messages', [ParentController::class, 'messages'])->name('messages');
    Route::get('/notifications', [ParentController::class, 'notifications'])->name('notifications');
    Route::get('/download-statement', [ParentController::class, 'downloadStatement'])->name('download-statement');
    Route::post('/change-language', [ParentController::class, 'changeLanguage'])->name('change-language');
    Route::post('/jump-to-academics', [\App\Http\Controllers\HandoffController::class, 'issueParentFromFinance'])->name('jump-to-academics');
});

require __DIR__.'/auth.php';
