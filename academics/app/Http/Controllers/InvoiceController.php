<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    // Show bulk invoice generation page
public function showBulkGeneration(Request $request, $feeId = null)
{
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $fees = DB::connection('tenant')->table('fees')
        ->where('is_active', true)
        ->orderBy('name')
        ->get();

    // All classes in school
    $allClasses = DB::connection('tenant')->table('classes')->get();
    $totalClasses = $allClasses->count();

    $classes = $allClasses;
    $assignedClassCount = 0;

    if ($feeId) {
        $assignedClasses = DB::connection('tenant')->table('classes')
            ->join('fee_class', 'classes.id', '=', 'fee_class.class_id')
            ->where('fee_class.fee_id', $feeId)
            ->select('classes.*')
            ->get();

        $assignedClassCount = $assignedClasses->count();

        // If not all classes are assigned, only show assigned
        if ($assignedClassCount < $totalClasses) {
            $classes = $assignedClasses;
        }
    }

    $optionalServices = DB::connection('tenant')->table('optional_services')
        ->where('is_active', true)
        ->get();

    return view('content.dashboard.accountant.modals.invoices.bulk', compact(
        'fees',
        'classes',
        'optionalServices',
        'feeId',
        'totalClasses',
        'assignedClassCount'
    ));
}



// Get student count for fee and class
public function getStudentCount(Request $request, $feeId)
{
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $classId = $request->query('class_id');

    // Get fee and check if it exists
    $fee = DB::connection('tenant')->table('fees')->where('id', $feeId)->first();
    
    if (!$fee) {
        return response()->json([
            'error' => 'Fee not found',
            'count' => 0,
            'fee' => null,
            'students' => []
        ], 404);
    }

    // Build student query with class join
    $studentsQuery = DB::connection('tenant')->table('students')
        ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
        ->select(
            'students.id',
            'students.first_name',
            'students.last_name', 
            'students.registration_no',
            'students.class_id',
            'classes.name as class_name'
        );

    if ($classId) {
        // If specific class is selected, only get students from that class
        $studentsQuery->where('students.class_id', $classId);
    } elseif ($fee->class_id) {
        // If fee is class-specific, only get students from that class
        $studentsQuery->where('students.class_id', $fee->class_id);
    }
    // If no class filter and fee is for all classes, get all students

    $count = $studentsQuery->count();
    $students = $studentsQuery->get();

    return response()->json([
        'count' => $count,
        'fee' => $fee,
        'students' => $students
    ]);
}

// Get invoice generation history
public function getInvoiceHistory()
{
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Get recent invoice history
    $history = DB::connection('tenant')->table('invoices')
        ->join('fees', 'invoices.fee_id', '=', 'fees.id')
        ->join('students', 'invoices.student_id', '=', 'students.id')
        ->join('classes', 'students.class_id', '=', 'classes.id')
        ->select(
            'invoices.id',
            'invoices.invoice_number',
            'invoices.issue_date',
            'invoices.due_date',
            'invoices.status',
            'fees.name as fee_name',
            'classes.name as class_name',
            DB::raw('(SELECT COUNT(*) FROM invoices i2 WHERE i2.fee_id = invoices.fee_id AND i2.issue_date = invoices.issue_date) as student_count')
        )
        ->orderBy('invoices.created_at', 'desc')
        ->limit(10)
        ->get();

    return response()->json($history);
}

public function downloadInvoice($invoiceId)
{
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $invoice = DB::connection('tenant')->table('invoices')->where('id', $invoiceId)->first();

    if (!$invoice || !$invoice->pdf_path) {
        return back()->with('error', 'Invoice PDF not found.');
    }

    return response()->download(storage_path('app/public/' . $invoice->pdf_path));
}


}
