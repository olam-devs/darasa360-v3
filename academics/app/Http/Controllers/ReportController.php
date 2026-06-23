<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    // Generate reports
    public function generateReports(Request $request)
    {
        $this->switchToTenant();

        $request->validate([
            'report_type' => 'required|string',
            'format' => 'required|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'class_id' => 'nullable|exists:tenant.classes,id'
        ]);

        $reportType = $request->report_type;
        $format = $request->format;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $classId = $request->class_id;

        try {
            $reportData = $this->generateReportData($reportType, $startDate, $endDate, $classId);
        
            // return $reportData;
            if ($request->send_to) {
                $this->sendReportToRecipients($reportData, $request->send_to, $format, $reportType);
            }

            return $this->exportReport($reportData, $format, $reportType);

        } catch (\Exception $e) {
            Log::error('Report generation failed: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate report: ' . $e->getMessage()], 500);
             return back()->with('error', 'Failed to generate report: ' . $e->getMessage());
        }
    }


    public function updateStatus(Request $request, $paymentId)
{
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $payment = DB::connection('tenant')->table('student_fees')
        ->where('id', $paymentId)
        ->first();

    if (!$payment) {
        return response()->json(['error' => 'Payment not found'], 404);
    }

    DB::connection('tenant')->table('student_fees')
        ->where('id', $paymentId)
        ->update(['status' => $request->status]);

    return back()->with('success', 'Payment status updated successfully.');
}


    // Switch tenant DB
    private function tenantDB()
    {
        $tenantDb = session('school_db');
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');
        return DB::connection('tenant');
    }

    // Display the single report detail
    public function showReportDetail()
    {
        $db = $this->tenantDB();
        $report = $db->table('report_details')->first(); // only one row

        return view('content.dashboard.accountant.general_info', compact('report'));
    }

    // Insert or update the report detail
    public function saveReportDetail(Request $request)
    {
        $db = $this->tenantDB();

        $request->validate([
            'phone' => 'required|string|max:20',
            'email' => 'required|email',
            'address' => 'nullable|string|max:255',
        ]);

        $data = [
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'updated_at' => now(),
        ];

        $existing = $db->table('report_details')->first();

        if ($existing) {
            $db->table('report_details')->where('id', $existing->id)->update($data);
        } else {
            $data['created_at'] = now();
            $db->table('report_details')->insert($data);
        }

        return redirect()->back()->with('success', 'Report detail saved successfully.');
    }

    // Delete the report detail
    public function deleteReportDetail()
    {
        $db = $this->tenantDB();
        $db->table('report_details')->delete(); // delete the only row

        return redirect()->back()->with('success', 'Report detail deleted successfully.');
    }

    // Generate report data based on type
    private function generateReportData($reportType, $startDate, $endDate, $classId)
    {
        $schoolInfo = $this->getSchoolInfo();
        $reportData = [];

        switch ($reportType) {
            case 'class':
                $reportData = $this->generateClassReport($startDate, $endDate, $classId);
                break;

            case 'payment_method':
                $reportData = $this->generatePaymentMethodReport($startDate, $endDate, $classId);
                break;

            case 'term':
                $reportData = $this->generateTermReport($startDate, $endDate, $classId);
                break;

            case 'year':
                $reportData = $this->generateAnnualReport($startDate, $endDate, $classId);
                break;

            case 'overdue':
                $reportData = $this->generateOverdueReport($startDate, $endDate, $classId);
                break;

            default:
                throw new \Exception('Invalid report type');
        }

        return array_merge($reportData, [
            'school_info' => $schoolInfo,
            'report_type' => $reportType,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'class_id' => $classId,
            'generated_at' => now()->format('Y-m-d H:i:s')
        ]);
    }

    // Class Report
private function generateClassReport($startDate, $endDate, $classId)
{
    $this->switchToTenant();

    $query = DB::connection('tenant')->table('classes')
        ->leftJoin('students', 'classes.id', '=', 'students.class_id')
        ->leftJoin('student_fees', function($join) use ($startDate, $endDate) {
            $join->on('students.id', '=', 'student_fees.student_id');
            
            // Move date conditions here to preserve LEFT JOIN behavior
            if ($startDate && $endDate) {
                $join->whereBetween('student_fees.payment_date', [$startDate, $endDate]);
            }
        })
        ->leftJoin('fees', 'student_fees.fee_id', '=', 'fees.id')
        ->select(
            'classes.id',
            'classes.name as class_name',
            DB::raw('COUNT(DISTINCT students.id) as total_students'),
            DB::raw('COALESCE(SUM(fees.amount), 0) as total_fees'),
            DB::raw('COALESCE(SUM(student_fees.amount), 0) as total_collected'),
            DB::raw('COUNT(DISTINCT student_fees.id) as total_transactions')
        )
        ->groupBy('classes.id', 'classes.name');

    if ($classId) {
        $query->where('classes.id', $classId);
    }

    $classData = $query->get();

    $summary = [
        'total_classes' => $classData->count(),
        'total_students' => $classData->sum('total_students'),
        'total_fees' => $classData->sum('total_fees'),
        'total_collected' => $classData->sum('total_collected'),
        'collection_rate' => $classData->sum('total_fees') > 0 ? 
            ($classData->sum('total_collected') / $classData->sum('total_fees')) * 100 : 0
    ];

    return [
        'title' => 'Class Fee Collection Report',
        'data' => $classData,
        'summary' => $summary
    ];
}

    // Payment Method Report
    private function generatePaymentMethodReport($startDate, $endDate, $classId)
    {
        $this->switchToTenant();

        $query = DB::connection('tenant')->table('student_fees')
            ->join('students', 'student_fees.student_id', '=', 'students.id')
            ->join('fees', 'student_fees.fee_id', '=', 'fees.id')
            ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
            ->select(
                'student_fees.payment_method',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(student_fees.amount) as total_amount'),
                DB::raw('AVG(student_fees.amount) as average_amount')
            )
            ->groupBy('student_fees.payment_method');

        if ($classId) {
            $query->where('students.class_id', $classId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('student_fees.payment_date', [$startDate, $endDate]);
        }

        $paymentData = $query->get();

        $summary = [
            'total_transactions' => $paymentData->sum('transaction_count'),
            'total_amount' => $paymentData->sum('total_amount'),
            'payment_methods' => $paymentData->count()
        ];

        return [
            'title' => 'Payment Method Analysis Report',
            'data' => $paymentData,
            'summary' => $summary
        ];
    }

    // Term Report
    private function generateTermReport($startDate, $endDate, $classId)
    {
        $this->switchToTenant();

        $currentTerm = 'Term ' . (date('n') <= 4 ? 1 : (date('n') <= 8 ? 2 : 3));
        $currentYear = date('Y');

        $query = DB::connection('tenant')->table('student_fees')
            ->join('students', 'student_fees.student_id', '=', 'students.id')
            ->join('fees', 'student_fees.fee_id', '=', 'fees.id')
            ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
            ->select(
                DB::raw('MONTH(student_fees.payment_date) as month'),
                DB::raw('YEAR(student_fees.payment_date) as year'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(student_fees.amount) as total_amount'),
                DB::raw('COUNT(DISTINCT students.id) as unique_students')
            )
            ->whereYear('student_fees.payment_date', $currentYear)
            ->groupBy('year', 'month');

        if ($classId) {
            $query->where('students.class_id', $classId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('student_fees.payment_date', [$startDate, $endDate]);
        }

        $termData = $query->get();

        $summary = [
            'term' => $currentTerm,
            'year' => $currentYear,
            'total_transactions' => $termData->sum('transaction_count'),
            'total_amount' => $termData->sum('total_amount'),
            'average_monthly' => $termData->avg('total_amount')
        ];

        return [
            'title' => "Term {$currentTerm} {$currentYear} Fee Report",
            'data' => $termData,
            'summary' => $summary
        ];
    }

    // Annual Report
    private function generateAnnualReport($startDate, $endDate, $classId)
    {
        $this->switchToTenant();

        $currentYear = date('Y');

        $query = DB::connection('tenant')->table('student_fees')
            ->join('students', 'student_fees.student_id', '=', 'students.id')
            ->join('fees', 'student_fees.fee_id', '=', 'fees.id')
            ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
            ->select(
                DB::raw('YEAR(student_fees.payment_date) as year'),
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('SUM(student_fees.amount) as total_amount'),
                DB::raw('COUNT(DISTINCT students.id) as unique_students'),
                DB::raw('COUNT(DISTINCT fees.id) as unique_fees')
            )
            ->groupBy('year');

        if ($classId) {
            $query->where('students.class_id', $classId);
        }

        if ($startDate && $endDate) {
            $query->whereBetween('student_fees.payment_date', [$startDate, $endDate]);
        } else {
            $query->whereYear('student_fees.payment_date', $currentYear);
        }

        $annualData = $query->get();

        $summary = [
            'years_covered' => $annualData->count(),
            'total_transactions' => $annualData->sum('transaction_count'),
            'total_amount' => $annualData->sum('total_amount'),
            'average_year' => $annualData->avg('total_amount')
        ];

        return [
            'title' => 'Annual Fee Collection Report',
            'data' => $annualData,
            'summary' => $summary
        ];
    }


    // Overdue Payments Report
private function generateOverdueReport($startDate = null, $endDate = null, $classId = null)
{
    $this->switchToTenant();

    // Get students (optionally filter by class)
    $query = DB::connection('tenant')->table('students')
        ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
        ->select(
            'students.id',
            'students.first_name',
            'students.last_name',
            'students.registration_no',
            'students.phone_number',
            'students.class_id',        // ✅ include class_id
            'classes.name as class_name'
        );

    if ($classId) {
        $query->where('students.class_id', $classId);
    }

    $students = $query->get();

    $results = [];

    foreach ($students as $student) {
        // Get all fees assigned to the student's class
        $fees = DB::connection('tenant')->table('fee_class as fc')
            ->join('fees', 'fc.fee_id', '=', 'fees.id')
            ->where('fc.class_id', $student->class_id)
            ->when($startDate && $endDate, fn($q) => $q->whereBetween('fees.due_date', [$startDate, $endDate]))
            ->when($startDate && !$endDate, fn($q) => $q->where('fees.due_date', '>=', $startDate))
            ->when(!$startDate && $endDate, fn($q) => $q->where('fees.due_date', '<=', $endDate))
            ->select('fees.id', 'fees.amount')
            ->get();

        $totalFees = $fees->sum('amount');

        // Total paid for all fees
        $totalPaid = DB::connection('tenant')->table('student_fees')
            ->where('student_id', $student->id)
            ->whereIn('fee_id', $fees->pluck('id')->toArray())
            ->where('status', 'paid')
            ->sum('amount');

        $balance = $totalFees - $totalPaid;

        if ($balance > 0) {
            $results[] = (object)[
                'id' => $student->id,
                'first_name' => $student->first_name,
                'last_name' => $student->last_name,
                'phone_number' => $student->phone_number,
                'registration_no' => $student->registration_no,
                'class_name' => $student->class_name,
                'balance_due' => $balance,
                'total_paid' => $totalPaid,
                'total_fees' => $totalFees,
            ];
        }
    }

    $summary = [
        'total_overdue_students' => count($results),
        'total_overdue_amount' => array_sum(array_column($results, 'balance_due')),
        'average_overdue' => count($results) ? array_sum(array_column($results, 'balance_due')) / count($results) : 0,
    ];

    return [
        'title' => 'Overdue Payments Report',
        'data' => collect($results),
        'summary' => $summary,
    ];
}



    // Export report in different formats
    private function exportReport($reportData, $format, $reportType)
    {

        $filename = strtolower(str_replace(' ', '-', $reportData['title'])) . '-' . date('Ymd-His');

        switch ($format) {
            case 'pdf':
                $pdf = PDF::loadView('content.dashboard.accountant.modals.reports.template', $reportData);
                return $pdf->download($filename . '.pdf');

            case 'excel':
                return Excel::download(new ReportExport($reportData), $filename . '.xlsx');

            case 'both':
                $pdf = PDF::loadView('content.dashboard.accountant.modals.reports.template', $reportData);
                return $pdf->download($filename . '.pdf');

            default:
                throw new \Exception('Invalid export format');
        }
    }

    // Send report to recipients
    private function sendReportToRecipients($reportData, $recipients, $format, $reportType)
    {
        Log::info("Report sent to: {$recipients}, Type: {$reportType}, Format: {$format}");
    }


    private function getSchoolInfo()
{
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Fetch school info from central table
    $schoolInfo = School::where('school_code', session('school_code'))->first();
    $schoolArray = $schoolInfo ? $schoolInfo->toArray() : [];

    // Fetch report details from tenant database
    $reportDetail = DB::connection('tenant')->table('report_details')->first();

    if ($reportDetail) {
        $schoolArray['report_phone'] = $reportDetail->phone ?? null;
        $schoolArray['report_email'] = $reportDetail->email ?? null;
        $schoolArray['report_address'] = $reportDetail->address ?? null;
    } else {
        $schoolArray['report_phone'] = null;
        $schoolArray['report_email'] = null;
        $schoolArray['report_address'] = null;
    }

    return $schoolArray;
}



    // 🔑 DB Switch Helper
    private function switchToTenant()
    {
        $tenantDb = session('school_db');
        if (!$tenantDb) {
            abort(500, 'Tenant database not set.');
        }
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');
    }
}
