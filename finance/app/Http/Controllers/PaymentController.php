<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Student;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // Payments are now managed through Vouchers
    // This controller provides a simplified interface

    public function index()
    {
        $payments = Voucher::where('voucher_type', 'Receipt')
            ->with(['student', 'particular', 'book'])
            ->orderBy('date', 'desc')
            ->get();

        return view('payments.index', compact('payments'));
    }

    public function create()
    {
        return view('payments.create');
    }

    public function store(Request $request)
    {
        return app(VoucherController::class)->store($request);
    }

    public function show($id)
    {
        $payment = Voucher::with(['student', 'particular', 'book'])->findOrFail($id);
        return view('payments.show', compact('payment'));
    }

    public function getInvoiceBalance(Request $request)
    {
        $studentId = $request->get('student_id');
        $particularId = $request->get('particular_id');

        $student = Student::findOrFail($studentId);
        $particular = $student->particulars()
            ->where('particular_id', $particularId)
            ->first();

        if (!$particular) {
            return response()->json(['error' => 'Particular not assigned to student'], 404);
        }

        $balance = $particular->pivot->sales - $particular->pivot->credit;

        return response()->json([
            'particular_name' => $particular->name,
            'sales' => $particular->pivot->sales,
            'paid' => $particular->pivot->credit,
            'balance' => $balance,
        ]);
    }
}
