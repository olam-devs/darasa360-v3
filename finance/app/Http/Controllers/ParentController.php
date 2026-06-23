<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Voucher;
use App\Models\Particular;
use App\Models\SchoolSetting;
use App\Models\AcademicYear;
use App\Services\ActivityLogger;
use App\Traits\HasSchoolContext;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class ParentController extends Controller
{
    use HasSchoolContext;

    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    private function getStudent()
    {
        $studentId = Session::get('parent_student_id');
        if (!$studentId) {
            abort(403, 'Unauthorized');
        }
        return Student::with('schoolClass')->findOrFail($studentId);
    }

    public function dashboard()
    {
        $student = $this->getStudent();
        $school = SchoolSetting::getSettings();
        $currentAcademicYear = AcademicYear::current();

        // Calculate Totals
        $totalFees = DB::table('particular_student')
            ->where('student_id', $student->id)
            ->sum('sales'); // Total Expected

        $totalPaid = Voucher::where('student_id', $student->id)
            ->sum('credit'); // Total Paid (Receipts)

        $balance = $totalFees - $totalPaid;

        // Recent Transactions (Last 5)
        $recentTransactions = Voucher::where('student_id', $student->id)
            ->with('particular')
            ->orderBy('date', 'desc')
            ->take(5)
            ->get();

        return view('parent.dashboard', compact('student', 'school', 'totalFees', 'totalPaid', 'balance', 'recentTransactions', 'currentAcademicYear'));
    }

    public function fees()
    {
        $student = $this->getStudent();
        $school = SchoolSetting::getSettings();

        // Get all academic years ordered by start_date (oldest first)
        $academicYears = AcademicYear::orderBy('start_date', 'asc')->get();

        // Get Fee Breakdown (Particulars) organized by academic year
        $feeBreakdown = DB::table('particular_student as ps')
            ->join('particulars as p', 'ps.particular_id', '=', 'p.id')
            ->leftJoin('academic_years as ay', 'ps.academic_year_id', '=', 'ay.id')
            ->where('ps.student_id', $student->id)
            ->select('p.name', 'ps.sales as amount', 'ps.credit as paid', 'ps.deadline', 'ay.name as academic_year', 'ay.start_date', 'ps.academic_year_id')
            ->orderBy('ay.start_date', 'asc')
            ->orderBy('p.name', 'asc')
            ->get()
            ->map(function($item) {
                $item->balance = $item->amount - $item->paid;
                $item->academic_year = $item->academic_year ?? 'Unassigned';
                return $item;
            });

        // Group fees by academic year
        $feesByYear = $feeBreakdown->groupBy('academic_year');

        // Full Transaction History
        $transactions = Voucher::where('student_id', $student->id)
            ->with(['particular', 'book'])
            ->orderBy('date', 'desc')
            ->paginate(20);

        return view('parent.fees', compact('student', 'school', 'feeBreakdown', 'feesByYear', 'transactions', 'academicYears'));
    }

    public function invoices()
    {
        $student = $this->getStudent();
        $school = SchoolSetting::getSettings();

        // Get all academic years ordered by start_date (oldest first)
        $academicYears = AcademicYear::orderBy('start_date', 'asc')->get();

        // Build invoice data organized by academic year
        $itemsByYear = [];
        $items = [];
        $totalFees = 0;
        $totalPaid = 0;

        $particulars = $student->particulars;

        foreach ($particulars as $particular) {
            $sales = $particular->pivot->sales;
            $credit = $particular->pivot->credit;
            $balance = $sales - $credit;
            $deadline = $particular->pivot->deadline;
            $academicYearId = $particular->pivot->academic_year_id;

            // Find academic year
            $academicYear = $academicYears->firstWhere('id', $academicYearId);
            $yearName = $academicYear ? $academicYear->name : 'Unassigned';

            if (!isset($itemsByYear[$yearName])) {
                $itemsByYear[$yearName] = [
                    'year_name' => $yearName,
                    'year_id' => $academicYearId,
                    'start_date' => $academicYear ? $academicYear->start_date : null,
                    'items' => [],
                    'subtotal_fees' => 0,
                    'subtotal_paid' => 0,
                    'subtotal_balance' => 0,
                ];
            }

            $itemData = [
                'name' => $particular->name,
                'amount' => $sales,
                'paid' => $credit,
                'balance' => $balance,
                'deadline' => $deadline,
                'is_overdue' => $deadline && \Carbon\Carbon::parse($deadline)->isPast() && $balance > 0,
                'academic_year' => $yearName,
            ];

            $itemsByYear[$yearName]['items'][] = $itemData;
            $itemsByYear[$yearName]['subtotal_fees'] += $sales;
            $itemsByYear[$yearName]['subtotal_paid'] += $credit;
            $itemsByYear[$yearName]['subtotal_balance'] += $balance;

            $items[] = $itemData;
            $totalFees += $sales;
            $totalPaid += $credit;
        }

        // Sort by year start date (oldest first)
        uasort($itemsByYear, function($a, $b) {
            if ($a['start_date'] === null) return 1;
            if ($b['start_date'] === null) return -1;
            return strtotime($a['start_date']) - strtotime($b['start_date']);
        });

        $itemsByYear = array_values($itemsByYear);

        return view('parent.invoices', compact('student', 'school', 'items', 'itemsByYear', 'totalFees', 'totalPaid'));
    }

    public function downloadInvoice()
    {
        $studentId = Session::get('parent_student_id');
        $studentName = Session::get('parent_student_name');

        // Log the download action
        $schoolId = $this->getSchoolId();
        if ($schoolId) {
            $this->activityLogger->logParentAction(
                $schoolId,
                $studentId,
                $studentName ?? 'Unknown',
                'download_invoice',
                "Downloaded invoice PDF for student: {$studentName}"
            );
        }

        // Use the same invoice format as accountant's student invoice page
        $controller = new \App\Http\Controllers\LedgerController();
        $request = new Request();

        return $controller->exportStudentInvoicePdf($studentId, $request);
    }

    public function messages()
    {
        $student = $this->getStudent();
        $school = SchoolSetting::getSettings();

        // Get SMS messages sent to this parent
        $messages = \App\Models\SmsLog::where('student_id', $student->id)
            ->orderBy('sent_at', 'desc')
            ->paginate(20);

        return view('parent.messages', compact('student', 'school', 'messages'));
    }

    public function notifications()
    {
        $student = $this->getStudent();
        $school = SchoolSetting::getSettings();

        // Get auto-generated notifications (overdue reminders, payment confirmations)
        $notifications = [];
        
        // Check for overdue payments
        $overdueParticulars = DB::table('particular_student as ps')
            ->join('particulars as p', 'ps.particular_id', '=', 'p.id')
            ->where('ps.student_id', $student->id)
            ->whereNotNull('ps.deadline')
            ->whereRaw('ps.deadline < NOW()')
            ->whereRaw('ps.sales > ps.credit')
            ->select('p.name', 'ps.sales', 'ps.credit', 'ps.deadline')
            ->get();

        foreach ($overdueParticulars as $particular) {
            $notifications[] = [
                'type' => 'overdue',
                'title' => 'Overdue Payment',
                'message' => "Payment for {$particular->name} is overdue. Amount due: TSh " . number_format($particular->sales - $particular->credit),
                'date' => $particular->deadline,
                'icon' => 'exclamation-triangle',
                'color' => 'red'
            ];
        }

        // Get recent payments as confirmations
        $recentPayments = Voucher::where('student_id', $student->id)
            ->where('credit', '>', 0)
            ->orderBy('date', 'desc')
            ->take(10)
            ->get();

        foreach ($recentPayments as $payment) {
            $notifications[] = [
                'type' => 'payment',
                'title' => 'Payment Received',
                'message' => "Payment of TSh " . number_format($payment->credit) . " received for " . ($payment->particular->name ?? 'fees'),
                'date' => $payment->date,
                'icon' => 'check-circle',
                'color' => 'green'
            ];
        }

        // Sort by date
        usort($notifications, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return view('parent.notifications', compact('student', 'school', 'notifications'));
    }

    public function downloadStatement()
    {
        $studentId = Session::get('parent_student_id');
        $studentName = Session::get('parent_student_name');
        $student = Student::with(['schoolClass', 'particulars'])->findOrFail($studentId);
        $school = SchoolSetting::getSettings();

        // Log the download action
        $schoolIdFromContext = $this->getSchoolId();
        if ($schoolIdFromContext) {
            $this->activityLogger->logParentAction(
                $schoolIdFromContext,
                $studentId,
                $studentName ?? $student->name,
                'download_statement',
                "Downloaded statement PDF for student: {$student->name}"
            );
        }

        // Get all transactions
        $transactions = Voucher::where('student_id', $studentId)
            ->with(['particular', 'book'])
            ->orderBy('date', 'asc')
            ->get();

        // Generate PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('parent.statement-pdf', [
            'student' => $student,
            'school' => $school,
            'transactions' => $transactions
        ]);

        return $pdf->download('statement-' . $student->student_reg_no . '.pdf');
    }

    public function changeLanguage(Request $request)
    {
        $language = $request->input('language', 'en');
        Session::put('parent_language', $language);
        
        return response()->json(['success' => true, 'language' => $language]);
    }
}

