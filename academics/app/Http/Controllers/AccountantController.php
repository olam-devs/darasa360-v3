<?php

namespace App\Http\Controllers;

use App\Exports\BulkInvoicesExport;
use App\Exports\InvoiceExport;
use App\Models\Classroom;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\School;
use App\Models\SchoolUser;
use App\Models\Student;
use App\Models\StudentFee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class AccountantController extends Controller
{
      public function accountantDashboard(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      abort(500, 'Tenant database not set in session.');
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    return view('content.dashboard.accountant.accountant_dashboard');
  }




  public function getStudentsByClass($classId,Request $request)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant'); DB::reconnect('tenant');

    $students = DB::connection('tenant')
        ->table('students')
        ->where('class_id', $classId)
        ->select('id', DB::raw("CONCAT(first_name, ' ', last_name) as full_name"))
        ->orderBy('first_name')
        ->get();

    return response()->json($students);
}



     // Store new fee
     public function storeFee(Request $request)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');


    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant'); DB::reconnect('tenant');

    $request->validate([
    'name' => 'required|string|max:255',
    'assignments' => 'required|array',
    'assignments.*.class_id' => 'required|integer|exists:tenant.classes,id',
    'assignments.*.student_ids' => 'required|array|min:1',
    'amount' => 'required|numeric|min:0',
    'due_date' => 'nullable|date',
]);

DB::connection('tenant')->beginTransaction();
try {
    $feeId = DB::connection('tenant')->table('fees')->insertGetId([
        'name' => $request->name,
        'amount' => $request->amount,
        'due_date' => $request->due_date,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    foreach ($request->assignments as $assignment) {
        $classId = $assignment['class_id'];

        DB::connection('tenant')->table('fee_class')->insert([
            'fee_id' => $feeId,
            'class_id' => $classId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($assignment['student_ids'] as $studentId) {
            DB::connection('tenant')->table('student_fees')->insert([
                'student_id' => $studentId,
                'fee_id' => $feeId,
                'amount' => $request->amount,
                'payment_date' => null,
                'status' => 'not_paid',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    DB::connection('tenant')->commit();
} catch (\Exception $e) {
    DB::connection('tenant')->rollBack();
    return back()->withErrors(['error' => 'Failed to add fee: ' . $e->getMessage()]);
}



    return redirect()->route('accountant.fees')->with('success', 'Fee added successfully.');
}


public function getFeeStudents(Request $request, $feeId)
{
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $classId = $request->query('class_id');
    $search = $request->query('search');

    // Get fee
    $fee = DB::connection('tenant')->table('fees')->where('id', $feeId)->first();
    if (!$fee) {
        return response()->json(['error' => 'Fee not found'], 404);
    }

    // Ensure class is assigned to the fee if classId provided
    if ($classId) {
        $assigned = DB::connection('tenant')->table('fee_class')
            ->where('fee_id', $feeId)
            ->where('class_id', $classId)
            ->exists();

        if (!$assigned) {
            return response()->json([
                'success' => false,
                'message' => 'Class not assigned to this fee'
            ]);
        }
    }

    // Get students
    $students = DB::connection('tenant')->table('students')
        ->join('student_fees', 'students.id', '=', 'student_fees.student_id')
        ->where('student_fees.fee_id', $feeId)
        ->when($classId, fn($q) => $q->where('students.class_id', $classId))
        ->when($search, function ($q) use ($search) {
            $q->where(function ($query) use ($search) {
                $query->where('students.first_name', 'like', "%$search%")
                      ->orWhere('students.middle_name', 'like', "%$search%")
                      ->orWhere('students.last_name', 'like', "%$search%");
            });
        })
        ->select('students.*')
        ->distinct()
        ->get();

    $results = $students->map(function ($student) use ($fee) {
        $payments = DB::connection('tenant')->table('student_fees')
            ->where('student_id', $student->id)
            ->where('fee_id', $fee->id)
            ->get();

        $totalPaid = $payments->where('status', 'paid')->sum('amount');

        return [
            'id' => $student->id,
            'name' => trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name),
            'registration_no' => $student->registration_no,
            'required' => $fee->amount,
            'paid' => $totalPaid,
            'balance' => $fee->amount - $totalPaid,
            'payments' => $payments,
        ];
    });

    // Fetch payment methods
    $paymentMethods = DB::connection('tenant')->table('payment_methods')
        ->select('id', 'name')
        ->get();

    return response()->json([
        'students' => $results,
        'payment_methods' => $paymentMethods,
    ]);
}



    // Update existing fee
public function updateFee(Request $request, $id)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant'); DB::reconnect('tenant');

    $request->validate([
        'name' => 'required|string|max:255',
        'assignments' => 'required|array',
        'assignments.*.class_id' => 'required|integer|exists:tenant.classes,id',
        'assignments.*.student_ids' => 'required|array|min:1',
        'amount' => 'required|numeric|min:0',
        'due_date' => 'nullable|date',
    ]);

    DB::connection('tenant')->beginTransaction();
    try {
        // Update fee info
        DB::connection('tenant')->table('fees')->where('id', $id)->update([
            'name' => $request->name,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'updated_at' => now(),
        ]);

        // Clear old assignments
        DB::connection('tenant')->table('fee_class')->where('fee_id', $id)->delete();
        DB::connection('tenant')->table('student_fees')->where('fee_id', $id)->delete();

        // Re-insert new
        foreach ($request->assignments as $assignment) {
            $classId = $assignment['class_id'];

            DB::connection('tenant')->table('fee_class')->insert([
                'fee_id' => $id,
                'class_id' => $classId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($assignment['student_ids'] as $studentId) {
                DB::connection('tenant')->table('student_fees')->insert([
                    'student_id' => $studentId,
                    'fee_id' => $id,
                    'amount' => $request->amount,
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        DB::connection('tenant')->commit();
    } catch (\Exception $e) {
        DB::connection('tenant')->rollBack();
        return back()->withErrors(['error' => 'Failed to update fee: '.$e->getMessage()]);
    }

    return redirect()->route('accountant.fees')->with('success', 'Fee updated successfully.');
}



    // Delete fee
    public function destroyFee($id,Request $request)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');


        $tenantDb = session('school_db');
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant'); DB::reconnect('tenant');

        DB::connection('tenant')->table('fees')->where('id', $id)->delete();

        return redirect()->route('accountant.fees')->with('success', 'Fee deleted successfully.');
    }


    // Fetch students + payment summary for a fee in a class
public function getFeePayments(Request $request, $feeId, $classId)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');


    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $search = $request->query('search');

    // Get fee
    $fee = DB::connection('tenant')->table('fees')->where('id', $feeId)->first();
    if (!$fee) {
        return response()->json(['error' => 'Fee not found'], 404);
    }

    // Ensure the class is assigned to the fee
    $assigned = DB::connection('tenant')->table('fee_class')
        ->where('fee_id', $feeId)
        ->where('class_id', $classId)
        ->exists();

    if (!$assigned) {
        return response()->json([
            'success' => false,
            'message' => 'Class not assigned to this fee'
        ]);
    }

    // Get students in class
    $students = DB::connection('tenant')->table('students')
    ->join('student_fees', 'students.id', '=', 'student_fees.student_id')
    ->where('student_fees.fee_id', $feeId)
    ->where('students.class_id', $classId)
    ->when($search, function ($q) use ($search) {
        $q->where(function ($query) use ($search) {
            $query->where('students.first_name', 'like', "%$search%")
                  ->orWhere('students.last_name', 'like', "%$search%");
        });
    })
    ->select('students.*')
    ->distinct()
    ->get();


    $results = [];
    foreach ($students as $student) {
        $payments = DB::connection('tenant')->table('student_fees')
            ->where('student_id', $student->id)
            ->where('fee_id', $feeId)
            ->get();

        $totalPaid = $payments->where('status', 'paid')->sum('amount');

        $results[] = [
            'id' => $student->id,
            'name' => $student->first_name . ' ' . $student->last_name,
            'registration_no' => $student->registration_no,
            'required' => $fee->amount,
            'paid' => $totalPaid,
            'balance' => $fee->amount - $totalPaid,
            'payments' => $payments,
        ];
    }

    // ✅ Fetch payment methods
    $paymentMethods = DB::connection('tenant')->table('payment_methods')->select('id', 'name')->get();

    return response()->json([
        'students' => $results,
        'payment_methods' => $paymentMethods,
    ]);
}






public function getPaymentHistory($feeId, $studentId,Request $request)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    try {
        $student = DB::connection('tenant')->table('students')->where('id', $studentId)->first();
        if (!$student) return response()->json(['error' => 'Student not found'], 404);

        $fee = DB::connection('tenant')->table('fees')->where('id', $feeId)->first();
        if (!$fee) return response()->json(['error' => 'Fee not found'], 404);

        $classes = DB::connection('tenant')->table('classes')->get()->keyBy('id');

        $payments = DB::connection('tenant')->table('student_fees')
            ->where('student_id', $studentId)
            ->where('fee_id', $feeId)
            ->orderBy('created_at', 'desc')
            ->get();

        // Match getFeePayments calculation: sum all 'paid' payments regardless of verification
        $totalPaid = $payments->where('status', 'paid')->sum('amount');
        $balance = $fee->amount - $totalPaid;

        $html = view('content.dashboard.accountant.modals.fees.payment-history', compact(
            'student', 
            'fee', 
            'payments', 
            'totalPaid', 
            'balance',
            'classes'
        ))->render();

        return $html;

    } catch (\Exception $e) {
        Log::error('Error fetching payment history: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to load payment history'], 500);
    }
}


// Add this new method for downloading receipts
public function downloadReceipt($paymentId,Request $request)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    try {
        $payment = DB::connection('tenant')->table('student_fees')
            ->where('id', $paymentId)
            ->first();

        if (!$payment) {
            return response()->json(['error' => 'Payment not found'], 404);
        }

        if (!$payment->receipt_path) {
            return response()->json(['error' => 'No receipt available for this payment'], 404);
        }

        // Check if file exists
        $filePath = storage_path('app/public/' . $payment->receipt_path);
        
        if (!file_exists($filePath)) {
            return response()->json(['error' => 'Receipt file not found'], 404);
        }

        // Get file extension and set appropriate headers
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $filename = 'receipt-' . $paymentId . '-' . $payment->student_id . '.' . $extension;

        $headers = [
            'Content-Type' => $this->getMimeType($extension),
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->download($filePath, $filename, $headers);

    } catch (\Exception $e) {
        Log::error('Error downloading receipt: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to download receipt'], 500);
    }
}

private function getMimeType($extension)
{
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ];

    return $mimeTypes[strtolower($extension)] ?? 'application/octet-stream';
}




public function accountantFee(Request $request)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Get fees list with pagination
    $fees = Fee::with('classes')->orderBy('created_at', 'desc')->paginate(10);


    $classes = Classroom::get();
    $optionalServices = DB::connection('tenant')->table('optional_services')->get();

    // -------------------------------------------------
    // Calculate totals using fee_class + student counts
    // -------------------------------------------------
    $totalFees = 0;

    foreach ($fees as $fee) {
        // Get classes assigned to this fee
        $assignedClasses = DB::connection('tenant')->table('fee_class')
            ->where('fee_id', $fee->id)
            ->pluck('class_id');

        if ($assignedClasses->isEmpty()) {
            continue;
        }

        // Count students in those classes
        $studentCount = DB::connection('tenant')->table('students')
            ->whereIn('class_id', $assignedClasses)
            ->count();

        // Add to total fee demand
        $totalFees += ($studentCount * $fee->amount);
    }

    // Total collected = sum of all actual payments (only paid & verified)
    $totalCollected = DB::connection('tenant')->table('student_fees')
        ->whereIn('status', ['paid', 'verified'])
        ->sum('amount');

    // Pending = what should be collected - what has been collected
    $totalPending = $totalFees - $totalCollected;

    // Overdue (improved): loop through fees with due_date < today
    $totalOverdue = 0;
    $overdueFees = DB::connection('tenant')->table('fees')
        ->where('due_date', '<', now())
        ->get();

    foreach ($overdueFees as $fee) {
        $assignedClasses = DB::connection('tenant')->table('fee_class')
            ->where('fee_id', $fee->id)
            ->pluck('class_id');

        if ($assignedClasses->isEmpty()) {
            continue;
        }

        $studentCount = DB::connection('tenant')->table('students')
            ->whereIn('class_id', $assignedClasses)
            ->count();

        $expected = $studentCount * $fee->amount;

        $collectedForFee = DB::connection('tenant')->table('student_fees')
            ->where('fee_id', $fee->id)
            ->whereIn('status', ['paid', 'verified'])
            ->sum('amount');

        $balance = $expected - $collectedForFee;

        if ($balance > 0) {
            $totalOverdue += $balance;
        }
    }

    $pendingCount = DB::connection('tenant')
  ->table('student_fees')
  ->where('status', 'pending')
  ->count();


$pendingPayments = DB::connection('tenant')
    ->table('student_fees')
    ->join('students', 'student_fees.student_id', '=', 'students.id')
    ->join('classes', 'students.class_id', '=', 'classes.id')
    ->join('fees', 'student_fees.fee_id', '=', 'fees.id')
    ->select(
        'student_fees.id',
        DB::raw("CONCAT(students.first_name, ' ', students.last_name) AS full_name"),
        'classes.name as class_name',
        'fees.name as fee_name',
        'student_fees.amount as amount_due',
        'fees.due_date',
        'student_fees.receipt_path' ,
        'student_fees.status'
    )
    ->where('student_fees.status', 'pending')
    ->paginate(50);


    $isAccountant = session('role') == 'Accountant' ? true :false;

    return view('content.dashboard.accountant.accountant_fees', compact(
        'fees',
        'classes',
        'optionalServices',
        'totalFees',
        'totalCollected',
        'totalPending',
        'totalOverdue',
        'pendingCount',
        'pendingPayments',
        'isAccountant'
    ));
}

    public function Feesfilter(Request $request)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
        $tenantDb = session('school_db');
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        $feeId = $request->query('fee_id');
        $classId = $request->query('class_id');

        $query = DB::connection('tenant')
            ->table('student_fees')
            ->join('students', 'student_fees.student_id', '=', 'students.id')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->join('fees', 'student_fees.fee_id', '=', 'fees.id')
            ->select(
                'student_fees.id',
                DB::raw("CONCAT(students.first_name, ' ', students.last_name) AS full_name"),
                'classes.name as class_name',
                'fees.name as fee_name',
                'student_fees.amount as amount_due',
                'fees.due_date',
                'student_fees.receipt_path',
                'student_fees.status'
            )
            ->where('student_fees.status', 'pending');

        if ($feeId) {
            $query->where('student_fees.fee_id', $feeId);
        }

        if ($classId) {
            $query->where('students.class_id', $classId);
        }

        $pendingPayments = $query->paginate(20);


        // Return HTML partial for table body
        return view('content.dashboard.accountant.modals.invoices.verify-payments-body', compact('pendingPayments'));
    }

    // Mark as verified
    public function FeemarkVerified($id,Request $request)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
        $tenantDb = session('school_db');
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        DB::connection('tenant')
            ->table('student_fees')
            ->where('id', $id)
            ->update(['status' => 'paid']);

        return response()->json(['message' => 'Payment marked as verified']);
    }

public function accountantPaymentMethods(Request $request)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Fetch payment methods from DB
    $paymentMethods = DB::connection('tenant')->table('payment_methods')->orderBy('created_at', 'desc')->get();

    return view('content.dashboard.accountant.accountant_payment_methods', compact('paymentMethods'));
}

public function storePaymentMethod(Request $request)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $request->validate([
        'name' => 'required|string|max:255|unique:tenant.payment_methods,name',
    ]);

    DB::connection('tenant')->table('payment_methods')->insert([
        'name' => $request->name,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return back()->with('success', 'Payment method added successfully.');
}


public function updatePaymentMethod(Request $request, $id)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $request->validate([
        'name' => 'required|string|max:255|unique:tenant.payment_methods,name,' . $id,
    ]);

    DB::connection('tenant')->table('payment_methods')
        ->where('id', $id)
        ->update(['name' => $request->name, 'updated_at' => now()]);

    return back()->with('success', 'Payment method updated successfully.');
}

public function deletePaymentMethod($id)
{
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    DB::connection('tenant')->table('payment_methods')->where('id', $id)->delete();

    return back()->with('success', 'Payment method deleted successfully.');
}






    // Enhanced payment storage with receipt handling
public function storePayment(Request $request, $feeId, $studentId)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $request->validate([
        'amount' => 'required|numeric|min:1',
        'payment_method' => 'required|string',
        'payment_date' => 'required|date',
        'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048'
    ]);

    $receiptPath = null;
    if ($request->hasFile('receipt')) {
        // Store in public disk with tenant-specific folder
        $receiptPath = $request->file('receipt')->store('receipts/' . $tenantDb, 'public');
    }

    $paymentId = DB::connection('tenant')->table('student_fees')->insertGetId([
        'student_id' => $studentId,
        'fee_id' => $feeId,
        'amount' => $request->amount,
        'payment_method' => $request->payment_method,
        'transaction_id' => $request->transaction_id,
        'payment_date' => $request->payment_date,
        'receipt_path' => $receiptPath,
        'is_verified' => true,
        'status' => 'paid',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Update student's financial totals
    $this->updateStudentFinancials($studentId);

    return back()->with('success', 'Payment recorded successfully.');
}

private function updateStudentFinancials($studentId)
{
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Calculate total paid by student
    $totalPaid = DB::connection('tenant')
        ->table('student_fees')
        ->where('student_id', $studentId)
        ->sum('amount');

    // Calculate total fees assigned to student
    $totalFees = DB::connection('tenant')
        ->table('student_fees as sf')
        ->join('fees as f', 'sf.fee_id', '=', 'f.id')
        ->where('sf.student_id', $studentId)
        ->sum('f.amount');

    $balance = $totalFees - $totalPaid;

    // Determine fee status
    if ($totalPaid >= $totalFees) {
        $feeStatus = 'paid';
    } elseif ($totalPaid > 0) {
        $feeStatus = 'partial';
    } else {
        $feeStatus = 'pending';
    }

    // Update student record
    DB::connection('tenant')
        ->table('students')
        ->where('id', $studentId)
        ->update([
            'total_fees' => $totalFees,
            'total_paid' => $totalPaid,
            'total_balance' => $balance,
            'fee_status' => $feeStatus,
            'updated_at' => now()
        ]);
}


    // Send reminders
    public function sendReminders(Request $request)
    {
        $tenantDb = session('school_db');
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        $students = $this->getStudentsForReminders($request->reminder_type);
        
        foreach ($students as $student) {
            $this->sendReminderToStudent($student, $request->channels, $request->control_number, $request->custom_message);
        }

        return back()->with('success', 'Reminders sent successfully.');
    }

    // Generate invoices
    public function generateStudentInvoice(Request $request, $studentId)
    {
        if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
       
        $tenantDb = session('school_db');
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        try {
            $student = DB::connection('tenant')->table('students')
                ->where('id', $studentId)
                ->first();

                

            if (!$student) {
                return back()->with('error', 'Student not found.');
            }

            $feeId = $request->query('fee_id');
            $services = $request->query('services', []);
            $format = $request->query('format', 'pdf');
            $sendMethod = $request->query('send_method');

            $invoiceData = $this->prepareInvoiceData($studentId, $feeId, $services);

            if ($sendMethod) {
                $this->sendInvoice($invoiceData, $sendMethod, $student);
            }

            return $this->exportInvoice($invoiceData, $format);

        } catch (\Exception $e) {
            Log::error('Error generating student invoice: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate invoice.');
        }
    }

    // Bulk generate invoices for a class/fee
public function generateBulkInvoices(Request $request)
{
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    try {
        $feeId = $request->fee_id;
        $classId = $request->class_id;
        $services = $request->services ?? [];
        $format = $request->format;
        $sendMethod = $request->send_method;

        // Get students based on criteria
        $studentsQuery = DB::connection('tenant')->table('students');
        if ($classId) {
            $studentsQuery->where('class_id', $classId);
        }
        $students = $studentsQuery->get();

        $invoices = [];
        foreach ($students as $student) {
            $invoiceData = $this->prepareInvoiceData($student->id, $feeId, $services);
            
            // No database insertion - just collect data for PDF
            $invoices[] = $invoiceData;

            // Optional: send invoice if requested (if sendInvoice doesn't require DB record)
            if ($sendMethod) {
                $this->sendInvoice($invoiceData, $sendMethod, $student);
            }
        }

        return $this->exportBulkInvoices($invoices, $format);

    } catch (\Exception $e) {
        Log::error('Error generating bulk invoices: ' . $e->getMessage());
        return back()->with('error', 'Failed to generate invoices.');
    }
}


    // Prepare invoice data
    private function prepareInvoiceData($studentId, $feeId = null, $services = [])
{
    
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);

    // Fetch student and class info
    $student = DB::connection('tenant')->table('students')
        ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
        ->where('students.id', $studentId)
        ->select('students.*', 'classes.name as class_name')
        ->first();

    $fees = [];
    $totalAmount = 0;
    $totalPaid = 0;

    if ($feeId) {
        // Specific fee
        $fee = DB::connection('tenant')->table('fees')->where('id', $feeId)->first();
        if ($fee) {
            // Only count payments with status 'paid'
            $paidAmount = DB::connection('tenant')->table('student_fees')
                ->where('student_id', $studentId)
                ->where('fee_id', $feeId)
                ->where('status', 'paid')
                ->sum('amount');

            $fees[] = [
                'name' => $fee->name,
                'amount' => $fee->amount,
                'paid' => $paidAmount,
                'balance' => $fee->amount - $paidAmount
            ];

            $totalAmount += $fee->amount;
            $totalPaid += $paidAmount;
        }
    } else {
        // All fees for student
        $allFees = DB::connection('tenant')->table('fees')
            ->join('fee_class', 'fees.id', '=', 'fee_class.fee_id')
            ->where('fee_class.class_id', $student->class_id)
            ->select('fees.id', 'fees.name', 'fees.amount')
            ->get();

        foreach ($allFees as $fee) {
            // Payments for this fee (status = 'paid')
            $paidAmount = DB::connection('tenant')->table('student_fees')
                ->where('student_id', $studentId)
                ->where('fee_id', $fee->id)
                ->where('status', 'paid')
                ->sum('amount');

            $fees[] = [
                'name' => $fee->name,
                'amount' => $fee->amount,
                'paid' => $paidAmount,
                'balance' => $fee->amount - $paidAmount
            ];

            $totalAmount += $fee->amount;
            $totalPaid += $paidAmount;
        }
    }

    // Optional services
    $optionalServices = [];
    if (!empty($services)) {
        $servicesData = DB::connection('tenant')->table('optional_services')
            ->whereIn('id', $services)
            ->where('is_active', true)
            ->get();

        foreach ($servicesData as $service) {
            $optionalServices[] = [
                'name' => $service->name,
                'amount' => $service->amount
            ];
            $totalAmount += $service->amount;
        }
    }

    $invoiceNumber = 'INV-' . strtoupper($tenantDb) . '-' . $studentId . '-' . date('Ymd-His');

    return [
        'invoice_number' => $invoiceNumber,
        'student' => $student,
        'fees' => $fees,
        'optional_services' => $optionalServices,
        'total_amount' => $totalAmount,
        'total_paid' => $totalPaid,
        'balance_due' => $totalAmount - $totalPaid,
        'issue_date' => now()->format('Y-m-d'),
        'due_date' => now()->addDays(30)->format('Y-m-d'),
        'school_info' => $this->getSchoolInfo($student->id),
        'administrator'=> $this->getAdministratorDetails()
    ];
}





    // Export invoice in different formats
    private function exportInvoice($invoiceData, $format)
    {
        switch ($format) {
            case 'pdf':
                $pdf = PDF::loadView('content.dashboard.accountant.modals.invoices.template', $invoiceData);
                return $pdf->download('invoice-' . $invoiceData['invoice_number'] . '.pdf');

            case 'excel':
                return Excel::download(new InvoiceExport($invoiceData), 'invoice-' . $invoiceData['invoice_number'] . '.xlsx');

            default:
                return back()->with('error', 'Invalid format specified.');
        }
    }

    // Export bulk invoices
    private function exportBulkInvoices($invoices, $format)
{


    switch ($format) {
        case 'pdf':
            // foreach ($invoices as &$invoice) {
            //     $pdf = PDF::loadView('content.dashboard.accountant.modals.invoices.bulk-template', ['invoices' => [$invoice]]);

            //     // Save PDF to storage
            //     $fileName = 'invoices/' . $invoice['invoice_number'] . '.pdf';
            //     Storage::disk('public')->put($fileName, $pdf->output());

            //     // Update invoice record with pdf_path
            //     DB::connection('tenant')->table('invoices')
            //         ->where('id', $invoice['id'])
            //         ->update(['pdf_path' => $fileName]);

            //     // Attach path to invoice array for immediate download if needed
            //     $invoice['pdf_path'] = $fileName;
            // }

                   // Generate single PDF with all invoices - no database operations    
            $pdf = PDF::loadView('content.dashboard.accountant.modals.invoices.bulk-template', ['invoices' => $invoices]);
            return $pdf->download('bulk-invoices-' . date('Ymd-His') . '.pdf');
            

            // For individual PDFs, just return a ZIP download or show message
            return back()->with('success', 'Invoices generated and saved as PDF.');
            
        case 'excel':
            return Excel::download(new BulkInvoicesExport($invoices), 'bulk-invoices-' . date('Ymd-His') . '.xlsx');

        case 'zip':
            return 'ok';
            // return $this->generateZipFile($invoices);

        default:
            return back()->with('error', 'Invalid format specified.');
    }
}


    // Send invoice via different methods
    private function sendInvoice($invoiceData, $method, $student)
    {
        switch ($method) {
            case 'in_app':
                $this->sendInAppNotification($invoiceData, $student);
                break;

            case 'sms':
                $this->sendSMSNotification($invoiceData, $student);
                break;

            case 'both':
                $this->sendInAppNotification($invoiceData, $student);
                $this->sendSMSNotification($invoiceData, $student);
                break;
        }

        // Log the invoice sending
        DB::connection('tenant')->table('invoices')->insert([
            'invoice_number' => $invoiceData['invoice_number'],
            'student_id' => $student->id,
            'total_amount' => $invoiceData['total_amount'],
            'paid_amount' => $invoiceData['total_paid'],
            'due_amount' => $invoiceData['balance_due'],
            'issue_date' => $invoiceData['issue_date'],
            'due_date' => $invoiceData['due_date'],
            'status' => 'sent',
            'sent_via' => $method,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function sendInAppNotification($invoiceData, $student)
    {
        // Implement in-app notification logic
        // This could be storing a notification in the database
        DB::connection('tenant')->table('notifications')->insert([
            'user_id' => $student->id, // or parent user ID
            'type' => 'invoice_generated',
            'title' => 'New Fee Invoice Generated',
            'message' => "Invoice {$invoiceData['invoice_number']} has been generated for {$student->first_name} {$student->last_name}",
            'data' => json_encode($invoiceData),
            'read_at' => null,
            'created_at' => now(),
        ]);
    }

    private function sendSMSNotification($invoiceData, $student)
    {
        if (!$student->parent_phone) {
            return;
        }

        $message = "Dear Parent, Invoice {$invoiceData['invoice_number']} for {$student->first_name} has been generated. Total: Tsh {$invoiceData['total_amount']}. Due: {$invoiceData['due_date']}";

        // Integrate with your SMS gateway (e.g., Africa's Talking, Twilio)
        // $this->sendSMS($student->parent_phone, $message);
    }

    private function getSchoolInfo($studentId)
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

    private function getAdministratorDetails()
    {
         $tenantDb = session('school_db');
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        $admin = SchoolUser::where('role_id',2)->first();
        return $admin ? [
            'email' => $admin->email,
            'phone' => $admin->phone_number
        ] : null;

    }   



    private function getStudentsForReminders($type)
    {
        // Implement student filtering for reminders
    }

    private function sendReminderToStudent($student, $channels, $controlNumber, $customMessage)
    {
        // Implement reminder sending logic
    }

    /**
     * Export students data for finance system
     */
    public function exportStudentsForFinance(Request $request)
    {
        if (!(new HelperController)->isLoggedIn($request)) {
            return redirect('/login');
        }

        // Set up tenant database connection
        $tenantDb = session('school_db');
        if (!$tenantDb) {
            abort(500, 'Tenant database not set in session.');
        }

        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        $classId = $request->input('class_id');
        $filters = [
            'gender' => $request->input('gender'),
            'search' => $request->input('search'),
        ];

        return Excel::download(
            new \App\Exports\StudentsFinanceExport($classId, $filters),
            'students_finance_' . now()->format('Y-m-d_His') . '.xlsx'
        );
    }

}
