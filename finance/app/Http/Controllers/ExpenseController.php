<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Expense;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with(['book', 'processor', 'bankFeeVoucher'])
            ->orderBy('transaction_date', 'desc');

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('book_id') && $request->book_id != '') {
            $query->where('book_id', $request->book_id);
        }

        if ($request->has('from_date') && $request->from_date != '') {
            $query->where('transaction_date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date != '') {
            $query->where('transaction_date', '<=', $request->to_date);
        }

        $perPage = $request->get('per_page', 15);
        $expenses = $query->paginate($perPage);

        $allExpenses = Expense::all();
        $totalPending = $allExpenses->where('status', 'pending')->sum('amount');
        $totalProcessed = $allExpenses->where('status', 'processed')->sum('amount');
        $totalCount = $allExpenses->count();
        $pendingCount = $allExpenses->where('status', 'pending')->count();
        $processedCount = $allExpenses->where('status', 'processed')->count();

        return response()->json([
            'expenses' => $expenses,
            'summary' => [
                'total_pending' => $totalPending,
                'total_processed' => $totalProcessed,
                'total_count' => $totalCount,
                'pending_count' => $pendingCount,
                'processed_count' => $processedCount,
                'total_amount' => $totalPending + $totalProcessed,
            ],
        ]);
    }

    public function summary(Request $request)
    {
        $query = Expense::query();

        if ($request->has('from_date')) {
            $query->where('transaction_date', '>=', $request->from_date);
        }
        if ($request->has('to_date')) {
            $query->where('transaction_date', '<=', $request->to_date);
        }

        $pendingExpenses = (clone $query)->where('status', 'pending')->get();
        $processedExpenses = (clone $query)->where('status', 'processed')->get();
        $allExpenses = $query->get();

        return response()->json([
            'pending_count' => $pendingExpenses->count(),
            'pending_amount' => $pendingExpenses->sum('amount'),
            'processed_count' => $processedExpenses->count(),
            'processed_amount' => $processedExpenses->sum('amount'),
            'total_count' => $allExpenses->count(),
            'total_amount' => $allExpenses->sum('amount'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_name' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'book_id' => 'nullable|exists:books,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        $validated['status'] = 'pending';

        $expense = Expense::create($validated);

        return response()->json($expense, 201);
    }

    public function show($id)
    {
        $expense = Expense::with(['book.bankFeeTiers', 'book.bankFeeParticular', 'voucher', 'bankFeeVoucher', 'processor'])->findOrFail($id);

        return response()->json($expense);
    }

    public function update(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        $validated = $request->validate([
            'expense_name' => 'required|string|max:255',
            'transaction_date' => 'required|date',
            'book_id' => 'required|exists:books,id',
            'amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
        ]);

        if ($expense->status === 'pending') {
            $expense->update($validated);

            return response()->json($expense);
        }

        if ($expense->status === 'processed') {
            if ((int) $validated['book_id'] !== (int) $expense->book_id) {
                return response()->json([
                    'error' => 'Cannot change the book on a processed expense. Cancel this expense first, then create a new one for the other book.',
                ], 400);
            }

            $expense->load(['voucher', 'bankFeeVoucher']);
            $book = Book::with(['bankFeeTiers', 'bankFeeParticular'])->findOrFail($expense->book_id);
            $newFee = $book->resolveBankFeeForWithdrawalAmount((float) $validated['amount']);
            $released = (float) ($expense->voucher?->debit ?? 0) + (float) ($expense->bankFeeVoucher?->debit ?? 0);
            $projectedBalance = $this->cashViewBalance($book) + $released;
            $required = (float) $validated['amount'] + $newFee;

            if ($projectedBalance < $required) {
                return response()->json([
                    'error' => 'Insufficient cash balance after adjusting this expense. Projected balance: TSh '.number_format($projectedBalance, 2).', required: TSh '.number_format($required, 2).'.',
                ], 400);
            }

            DB::beginTransaction();
            try {
                $this->deleteExpenseVouchers($expense);
                $expense->update($validated);
                $this->createExpenseVouchers($expense);
                $expense->refresh();

                DB::commit();

                return response()->json($expense->load(['book', 'voucher', 'bankFeeVoucher']));
            } catch (\Exception $e) {
                DB::rollBack();

                return response()->json([
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'error' => 'Cannot update cancelled expense',
        ], 400);
    }

    /**
     * Expenses are no longer deletable.
     * - A pending expense can be edited (no impact yet).
     * - A processed expense must be cancelled (vouchers reversed) or updated.
     * - A cancelled expense is the audit trail of a reversed entry.
     */
    public function destroy($id)
    {
        return response()->json([
            'error' => 'Expenses cannot be deleted. Cancel a processed expense or edit a pending one; the record will be kept for audit.',
        ], 403);
    }

    public function process(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        if ($expense->status !== 'pending') {
            return response()->json([
                'error' => 'Expense already processed',
            ], 400);
        }

        $book = Book::with(['bankFeeTiers', 'bankFeeParticular'])->findOrFail($expense->book_id);

        $fee = $book->resolveBankFeeForWithdrawalAmount((float) $expense->amount);
        $totalOut = (float) $expense->amount + $fee;

        $cashViewBalance = $this->cashViewBalance($book);

        if ($cashViewBalance < $totalOut) {
            return response()->json([
                'error' => 'Insufficient cash balance to process this expense and bank fees. Current cash balance: TSh '.number_format($cashViewBalance, 2).', Required: TSh '.number_format($totalOut, 2).' (expense '.number_format((float) $expense->amount, 2).($fee > 0 ? ' + bank fee '.number_format($fee, 2) : '').')',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $this->createExpenseVouchers($expense);

            $expense->update([
                'status' => 'processed',
                'processed_by' => auth()->id(),
                'processed_at' => now(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Expense processed successfully',
                'expense' => $expense->fresh(['voucher', 'bankFeeVoucher', 'book']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request, $id)
    {
        $expense = Expense::findOrFail($id);

        if ($expense->status === 'pending') {
            return response()->json([
                'error' => 'Expense is not processed yet',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $this->deleteExpenseVouchers($expense);

            $expense->update([
                'status' => 'cancelled',
                'voucher_id' => null,
                'bank_fee_voucher_id' => null,
                'bank_fee_amount' => null,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Expense cancelled successfully',
                'expense' => $expense->fresh(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function analytics(Request $request)
    {
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        if (! $fromDate || ! $toDate) {
            $fromDate = now()->startOfMonth()->toDateString();
            $toDate = now()->endOfMonth()->toDateString();
        }

        $expenses = Expense::where('status', 'processed')
            ->whereBetween('transaction_date', [$fromDate, $toDate])
            ->with('book')
            ->orderBy('transaction_date', 'asc')
            ->get();

        $daysDiff = (strtotime($toDate) - strtotime($fromDate)) / (60 * 60 * 24);
        $isMonthly = $daysDiff <= 31;

        $expenseTrend = [];
        if ($isMonthly) {
            $currentDate = new \DateTime($fromDate);
            $endDate = new \DateTime($toDate);

            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->format('Y-m-d');
                $dailyTotal = $expenses->filter(function ($expense) use ($dateStr) {
                    return $expense->transaction_date === $dateStr;
                })->sum('amount');

                $expenseTrend[] = [
                    'label' => $currentDate->format('M d'),
                    'amount' => (float) $dailyTotal,
                ];

                $currentDate->modify('+1 day');
            }
        } else {
            $months = [];
            $currentDate = new \DateTime($fromDate);
            $endDate = new \DateTime($toDate);

            while ($currentDate <= $endDate) {
                $monthKey = $currentDate->format('Y-m');
                if (! isset($months[$monthKey])) {
                    $months[$monthKey] = [
                        'label' => $currentDate->format('M Y'),
                        'amount' => 0,
                    ];
                }
                $currentDate->modify('+1 month');
            }

            foreach ($expenses as $expense) {
                $monthKey = date('Y-m', strtotime((string) $expense->transaction_date));
                if (isset($months[$monthKey])) {
                    $months[$monthKey]['amount'] += (float) $expense->amount;
                }
            }

            $expenseTrend = array_values($months);
        }

        $booksDistribution = [];
        $expensesByBook = $expenses->groupBy('book_id');

        foreach ($expensesByBook as $bookId => $bookExpenses) {
            $book = $bookExpenses->first()->book;
            if ($book) {
                $booksDistribution[] = [
                    'name' => $book->name,
                    'amount' => (float) $bookExpenses->sum('amount'),
                ];
            }
        }

        return response()->json([
            'expense_trend' => $expenseTrend,
            'books_distribution' => $booksDistribution,
        ]);
    }

    protected function cashViewBalance(Book $book): float
    {
        // Accountant view: DR=debit (money in), CR=credit (money out)
        $dr = (float) $book->vouchers()->sum('debit');
        $cr = (float) $book->vouchers()->sum('credit');

        return (float) $book->opening_balance + $dr - $cr;
    }

    protected function deleteExpenseVouchers(Expense $expense): void
    {
        if ($expense->bank_fee_voucher_id) {
            Voucher::where('id', $expense->bank_fee_voucher_id)->delete();
        }
        if ($expense->voucher_id) {
            Voucher::where('id', $expense->voucher_id)->delete();
        }

        $expense->forceFill([
            'voucher_id' => null,
            'bank_fee_voucher_id' => null,
            'bank_fee_amount' => null,
        ])->saveQuietly();
    }

    protected function createExpenseVouchers(Expense $expense): void
    {
        $book = Book::with(['bankFeeTiers', 'bankFeeParticular'])->findOrFail($expense->book_id);

        $mainVoucher = Voucher::create([
            'date' => $expense->transaction_date,
            'student_id' => null,
            'particular_id' => null,
            'book_id' => $expense->book_id,
            'voucher_type' => 'Payment',
            'debit' => 0,
            'credit' => $expense->amount,
            'payment_by_receipt_to' => $expense->expense_name,
            'notes' => $expense->description,
            'created_by' => auth()->id(),
        ]);

        $fee = $book->resolveBankFeeForWithdrawalAmount((float) $expense->amount);
        $feeVoucherId = null;
        $bankFeeAmount = null;

        if ($fee > 0 && $book->bank_fees_enabled && $book->bank_fee_particular_id && ! $book->is_cash_book) {
            $feeNotes = sprintf(
                'Bank transaction fee for expense #%d "%s" (withdrawal TSh %s). Linked voucher #%s.',
                $expense->id,
                $expense->expense_name,
                number_format((float) $expense->amount, 2),
                $mainVoucher->voucher_number
            );

            $feeVoucher = Voucher::create([
                'date' => $expense->transaction_date,
                'student_id' => null,
                'particular_id' => $book->bank_fee_particular_id,
                'book_id' => $expense->book_id,
                'voucher_type' => 'Payment',
                'debit' => 0,
                'credit' => $fee,
                'payment_by_receipt_to' => 'Bank transaction fee',
                'notes' => $feeNotes,
                'created_by' => auth()->id(),
            ]);
            $feeVoucherId = $feeVoucher->id;
            $bankFeeAmount = $fee;
        }

        $expense->forceFill([
            'voucher_id' => $mainVoucher->id,
            'bank_fee_voucher_id' => $feeVoucherId,
            'bank_fee_amount' => $bankFeeAmount,
        ])->saveQuietly();
    }
}
