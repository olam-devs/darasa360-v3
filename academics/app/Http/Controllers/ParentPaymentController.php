<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Container\Attributes\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ParentPaymentController extends Controller
{
 
public function index(Request $request)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Get student
    $student = DB::connection('tenant')->table('students')
        ->where('user_id', session('user_id'))
        ->first();

    if (!$student) {
        abort(404, 'Student record not found.');
    }

    // Get class
    $class = DB::connection('tenant')->table('classes')
        ->where('id', $student->class_id)
        ->first();

    // Get all fees assigned to this class via fee_class pivot
    $classFees = DB::connection('tenant')->table('fees')
        ->join('fee_class', 'fees.id', '=', 'fee_class.fee_id')
        ->where('fee_class.class_id', $student->class_id)
        ->select('fees.*')
        ->get();

    // Payments made by student
    $payments = DB::connection('tenant')->table('student_fees')
        ->where('student_id', $student->id)
        ->get();

    $paymentsByFee = $payments->groupBy('fee_id');

    $duePayments = collect();
    $paymentHistory = collect();
    $totalPaid = 0;
    $totalDues = 0;
    $overdueAmount = 0;

    foreach ($classFees as $fee) {
        $feePayments = $paymentsByFee->has($fee->id) ? $paymentsByFee[$fee->id] : collect();

        // Total paid for this fee (only 'paid' payments)
        $paidAmount = $feePayments->where('status', 'paid')->sum('amount');
        $balance = $fee->amount - $paidAmount;

        // Only include in duePayments if balance > 0 and no pending payments exist
        if ($balance > 0 && $feePayments->where('status', 'pending')->isEmpty()) {
            $duePayments->push((object)[
                'fee_id' => $fee->id,
                'fee_name' => $fee->name,
                'due_date' => $fee->due_date ? Carbon::parse($fee->due_date) : null,
                'amount' => $balance,
            ]);
        }

        // Payment history (all payments)
        foreach ($feePayments as $p) {
            $paymentHistory->push((object)[
                'id' => $p->id,
                'fee_name' => $fee->name,
                'amount' => $p->amount,
                'paid_at' => $p->created_at ? Carbon::parse($p->created_at) : null,
                'receipt_path' => $p->receipt_path ?? null,
                'status' => $p->status ?? 'pending',
            ]);
        }

        $totalPaid += $paidAmount;
        $totalDues += $balance;

        // Overdue calculation
        if ($fee->due_date && $balance > 0 && Carbon::parse($fee->due_date)->isPast()) {
            $overdueAmount += $balance;
        }
    }

    // Last payment date (only 'paid' payments)
    $lastPaymentDate = $payments->where('status', 'paid')->isNotEmpty()
        ? Carbon::parse($payments->where('status', 'paid')->max('created_at'))
        : null;

    // Next due date (next fee with remaining balance)
    $nextDueDate = $duePayments
        ->where('due_date', '>=', now())
        ->sortBy('due_date')
        ->first()
        ->due_date ?? null;


    $paymentMethods = DB::connection('tenant')->table('payment_methods')->orderBy('created_at', 'desc')->get();


    return view('content.dashboard.student.payments.paymentHome', compact(
        'student',
        'class',
        'totalDues',
        'totalPaid',
        'overdueAmount',
        'lastPaymentDate',
        'nextDueDate',
        'duePayments',
        'paymentHistory',
        'paymentMethods'
    ));
}





public function uploadPayment(Request $request)
{

    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    try {
        $request->validate([
            'student_id' => 'required|integer|exists:tenant.students,id',
            'fee_id' => 'required|integer|exists:tenant.fees,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date|before_or_equal:today',
            'transaction_id' => 'nullable|string|max:100',
            'receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        // Check if student exists and belongs to current user (if needed)
        $student = DB::connection('tenant')->table('students')
            ->where('id', $request->student_id)
            ->first();

        if (!$student) {
            return back()->with('error', 'Student not found.');
        }

        $receiptPath = null;
        if ($request->hasFile('receipt')) {
            $receiptPath = $request->file('receipt')->store('receipts/' . $tenantDb, 'public');
        }

        DB::connection('tenant')->table('student_fees')->insert([
            'student_id' => $request->student_id,
            'fee_id' => $request->fee_id,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id,
            'payment_date' => $request->payment_date,
            'receipt_path' => $receiptPath,
            'status' => 'pending',
            'is_verified' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Log the payment
        Log::info('Parent uploaded payment', [
            'student_id' => $request->student_id,
            'fee_id' => $request->fee_id,
            'amount' => $request->amount,
            'has_receipt' => !is_null($receiptPath)
        ]);

        return back()->with('success', 'Payment recorded successfully. Awaiting verification.');

    } catch (\Exception $e) {
        Log::error('Payment upload failed: ' . $e->getMessage());
        return back()->with('error', 'Failed to record payment: ' . $e->getMessage());
    }
}



}
