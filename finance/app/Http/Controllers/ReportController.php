<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Student;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function incomeStatement(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfYear());
        $dateTo = $request->get('date_to', now()->endOfYear());

        $income = Voucher::whereBetween('date', [$dateFrom, $dateTo])
            ->where('voucher_type', 'Receipt')
            ->sum('debit');

        $expenses = Voucher::whereBetween('date', [$dateFrom, $dateTo])
            ->where('voucher_type', 'Payment')
            ->sum('credit');

        $netIncome = $income - $expenses;

        return response()->json([
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'income' => $income,
            'expenses' => $expenses,
            'net_income' => $netIncome,
        ]);
    }

    public function balanceSheet(Request $request)
    {
        // Simplified balance sheet
        $totalAssets = Book::sum('opening_balance');
        $totalLiabilities = Student::join('particular_student', 'students.id', '=', 'particular_student.student_id')
            ->sum(DB::raw('particular_student.sales - particular_student.credit'));

        $equity = $totalAssets - $totalLiabilities;

        return response()->json([
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
        ]);
    }

    public function trialBalance(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfYear());
        $dateTo = $request->get('date_to', now()->endOfYear());

        $books = Book::all()->map(function ($book) use ($dateFrom, $dateTo) {
            $debit = Voucher::where('book_id', $book->id)
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->sum('debit');

            $credit = Voucher::where('book_id', $book->id)
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->sum('credit');

            return [
                'account' => $book->name,
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $book->opening_balance + $debit - $credit,
            ];
        });

        $totalDebit = $books->sum('debit');
        $totalCredit = $books->sum('credit');

        return response()->json([
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'books' => $books,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ]);
    }

    public function feeCollection(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->startOfMonth());
        $dateTo = $request->get('date_to', now()->endOfMonth());

        $collections = Voucher::whereBetween('date', [$dateFrom, $dateTo])
            ->where('voucher_type', 'Receipt')
            ->with(['student', 'particular'])
            ->get()
            ->groupBy('particular_id')
            ->map(function ($group) {
                return [
                    'particular' => $group->first()->particular->name ?? 'N/A',
                    'total_collected' => $group->sum('debit'),
                    'transaction_count' => $group->count(),
                ];
            })
            ->values();

        $totalCollected = $collections->sum('total_collected');

        return response()->json([
            'period' => [
                'from' => $dateFrom,
                'to' => $dateTo,
            ],
            'collections' => $collections,
            'total_collected' => $totalCollected,
        ]);
    }

    public function outstandingBalances()
    {
        $students = Student::with(['schoolClass', 'particulars'])
            ->where('status', 'active')
            ->get()
            ->map(function ($student) {
                $totalSales = $student->particulars->sum('pivot.sales');
                $totalCredit = $student->particulars->sum('pivot.credit');
                $balance = $totalSales - $totalCredit;

                if ($balance > 0) {
                    return [
                        'student_id' => $student->id,
                        'student_name' => $student->name,
                        'class' => $student->schoolClass->name ?? $student->class,
                        'total_sales' => $totalSales,
                        'total_paid' => $totalCredit,
                        'balance' => $balance,
                    ];
                }

                return null;
            })
            ->filter()
            ->values();

        $totalOutstanding = $students->sum('balance');

        return response()->json([
            'students' => $students,
            'total_outstanding' => $totalOutstanding,
            'student_count' => $students->count(),
        ]);
    }

    public function studentStatement($studentId = null)
    {
        if (! $studentId) {
            return response()->json(['error' => 'Student ID required'], 400);
        }

        $student = Student::with(['schoolClass', 'particulars'])->findOrFail($studentId);

        $vouchers = Voucher::where('student_id', $studentId)
            ->with('particular')
            ->orderBy('date')
            ->get();

        $summary = [
            'student' => $student,
            'total_sales' => $student->particulars->sum('pivot.sales'),
            'total_paid' => $student->particulars->sum('pivot.credit'),
            'balance' => $student->particulars->sum(function ($p) {
                return $p->pivot->sales - $p->pivot->credit;
            }),
            'vouchers' => $vouchers,
        ];

        return response()->json($summary);
    }
}
