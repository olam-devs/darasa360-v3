<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Particular;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Voucher;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReconciliationController extends Controller
{
    public function page()
    {
        $settings = SchoolSetting::getSettings();
        $user = auth()->user();
        $canEditHistory = (bool) ($user->can_edit_history ?? false);

        return view('admin.accountant.modules.reconciliation', compact('settings', 'canEditHistory'));
    }

    /**
     * Post a pure ledger adjustment (cash / accountant view).
     * increase = more money in the book (Receipt DR), decrease = money out (Payment CR).
     */
    public function storeAdjustment(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'date' => 'required|date',
            'direction' => 'required|in:increase,decrease',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:2000',
        ]);

        $book = Book::findOrFail($validated['book_id']);
        $amount = (float) $validated['amount'];

        if ($validated['direction'] === 'decrease') {
            $cash = $book->getCashViewBalance();
            if ($cash < $amount) {
                return response()->json([
                    'error' => 'Insufficient book balance for this adjustment. Available (cash view): TSh '.number_format($cash, 2),
                ], 400);
            }
        }

        DB::beginTransaction();
        try {
            if ($validated['direction'] === 'increase') {
                Voucher::create([
                    'date' => $validated['date'],
                    'student_id' => null,
                    'particular_id' => null,
                    'book_id' => $book->id,
                    'voucher_type' => 'Receipt',
                    'debit' => $amount,
                    'credit' => 0,
                    'payment_by_receipt_to' => 'Reconciliation Adjustment',
                    'notes' => $validated['reason'],
                    'created_by' => auth()->id(),
                ]);
            } else {
                Voucher::create([
                    'date' => $validated['date'],
                    'student_id' => null,
                    'particular_id' => null,
                    'book_id' => $book->id,
                    'voucher_type' => 'Payment',
                    'debit' => 0,
                    'credit' => $amount,
                    'payment_by_receipt_to' => 'Reconciliation Adjustment',
                    'notes' => $validated['reason'],
                    'created_by' => auth()->id(),
                ]);
            }

            DB::commit();

            ActivityLogger::log('reconciliation_adjustment', "Posted {$validated['direction']} adjustment TSh ".number_format($amount, 2)." on book #{$book->id}", $book, $validated);

            return response()->json(['message' => 'Reconciliation adjustment posted successfully'], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Record a bank transaction fee on this book (reconciliation / historical correction).
     */
    public function storeBankFee(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:2000',
        ]);

        $book = Book::findOrFail($validated['book_id']);
        $amount = (float) $validated['amount'];
        $cash = $book->getCashViewBalance();
        if ($cash < $amount) {
            return response()->json([
                'error' => 'Insufficient book balance. Available (cash view): TSh '.number_format($cash, 2),
            ], 400);
        }

        $voucher = Voucher::create([
            'date' => $validated['date'],
            'student_id' => null,
            'particular_id' => null,
            'book_id' => $book->id,
            'voucher_type' => 'Payment',
            'debit' => 0,
            'credit' => $amount,
            'payment_by_receipt_to' => 'Bank Transaction Fee',
            'notes' => $validated['reason'],
            'created_by' => auth()->id(),
        ]);

        ActivityLogger::log('reconciliation_bank_fee', "Bank fee TSh ".number_format($amount, 2)." on book #{$book->id}", $voucher, $validated);

        return response()->json(['message' => 'Bank fee recorded', 'voucher' => $voucher], 201);
    }

    /**
     * Record a monthly bank cut on this book (one-off amount for reconciliation).
     */
    public function storeMonthlyCut(Request $request)
    {
        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:2000',
        ]);

        $book = Book::findOrFail($validated['book_id']);
        $amount = (float) $validated['amount'];
        $cash = $book->getCashViewBalance();
        if ($cash < $amount) {
            return response()->json([
                'error' => 'Insufficient book balance. Available (cash view): TSh '.number_format($cash, 2),
            ], 400);
        }

        $voucher = Voucher::create([
            'date' => $validated['date'],
            'student_id' => null,
            'particular_id' => null,
            'book_id' => $book->id,
            'voucher_type' => 'Payment',
            'debit' => 0,
            'credit' => $amount,
            'payment_by_receipt_to' => 'Monthly Bank Cut',
            'notes' => $validated['reason'],
            'created_by' => auth()->id(),
        ]);

        ActivityLogger::log('reconciliation_monthly_cut', "Monthly cut TSh ".number_format($amount, 2)." on book #{$book->id}", $voucher, $validated);

        return response()->json(['message' => 'Monthly bank cut recorded', 'voucher' => $voucher], 201);
    }

    /**
     * Correct amounts on book ledger vouchers when the accountant has edit-history permission.
     */
    public function updateVoucher(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        if ($voucher->isVoided()) {
            return response()->json(['error' => 'Voided vouchers cannot be edited.'], 422);
        }

        $validated = $request->validate([
            'book_id' => 'required|exists:books,id',
            'debit' => 'required|numeric|min:0',
            'credit' => 'required|numeric|min:0',
            'notes_append' => 'nullable|string|max:1000',
        ]);

        if ((int) $voucher->book_id !== (int) $validated['book_id']) {
            return response()->json(['error' => 'Voucher does not belong to this book.'], 422);
        }

        $newD = round((float) $validated['debit'], 2);
        $newC = round((float) $validated['credit'], 2);
        $oldD = (float) $voucher->debit;
        $oldC = (float) $voucher->credit;
        $pbt = $voucher->payment_by_receipt_to;
        $type = $voucher->voucher_type;

        $restrictedTypes = ['Bank Transaction Fee', 'Monthly Bank Cut', 'Reconciliation Adjustment'];
        $hasFullEdit = ! in_array($pbt, $restrictedTypes, true);

        if ($hasFullEdit) {
            if ($type === 'Sales' || $type === 'Receipt') {
                if ($newC != 0.0) {
                    return response()->json(['error' => 'Receipts and sales are stored as debit-only vouchers.'], 422);
                }
                if ($newD <= 0) {
                    return response()->json(['error' => 'Debit amount must be greater than zero.'], 422);
                }
            } elseif ($type === 'Payment') {
                if ($newD != 0.0) {
                    return response()->json(['error' => 'Payments are stored as credit-only vouchers.'], 422);
                }
                if ($newC <= 0) {
                    return response()->json(['error' => 'Credit amount must be greater than zero.'], 422);
                }
            } else {
                if (($newD > 0 && $newC > 0) || ($newD <= 0 && $newC <= 0)) {
                    return response()->json(['error' => 'Set either debit or credit (not both).'], 422);
                }
            }
        } elseif ($pbt === 'Reconciliation Adjustment') {
            if (($newD > 0 && $newC > 0) || ($newD <= 0 && $newC <= 0)) {
                return response()->json(['error' => 'Reconciliation adjustment must have either debit or credit (not both).'], 422);
            }
        } else {
            if ($newD != 0.0) {
                return response()->json(['error' => 'Bank fees and monthly cuts are stored as credit-only vouchers.'], 422);
            }
            if ($newC <= 0) {
                return response()->json(['error' => 'Credit amount must be greater than zero.'], 422);
            }
        }

        $book = Book::findOrFail((int) $validated['book_id']);
        $delta = ($newD - $newC) - ($oldD - $oldC);
        if ($delta < 0 && $book->getCashViewBalance() + $delta < -0.0001) {
            return response()->json([
                'error' => 'This change would overdraw the book (cash view). Reduce the adjustment or post an offsetting entry.',
            ], 400);
        }

        DB::beginTransaction();
        try {
            $oldType = $voucher->voucher_type;

            $voucher->debit = $newD;
            $voucher->credit = $newC;
            if (! empty($validated['notes_append'])) {
                $voucher->notes = trim((string) $voucher->notes).' | '.$validated['notes_append'];
            }
            $voucher->save();

            if ($voucher->student_id && $voucher->particular_id) {
                $student = Student::find($voucher->student_id);
                $particular = Particular::find($voucher->particular_id);
                if ($student && $particular) {
                    $pivot = $student->particulars()
                        ->where('particular_id', $particular->id)
                        ->first();

                    if ($pivot) {
                        $oldSalesDelta = $oldType === 'Sales' ? $oldD : 0.0;
                        $oldCreditDelta = $oldType === 'Receipt' ? $oldD : 0.0;

                        $newSalesDelta = $oldType === 'Sales' ? $newD : 0.0;
                        $newCreditDelta = $oldType === 'Receipt' ? $newD : 0.0;

                        $sales = (float) $pivot->pivot->sales - $oldSalesDelta + $newSalesDelta;
                        $credit = (float) $pivot->pivot->credit - $oldCreditDelta + $newCreditDelta;

                        $student->particulars()->updateExistingPivot($particular->id, [
                            'sales' => max(0, $sales),
                            'credit' => max(0, $credit),
                        ]);
                    }
                }
            }

            DB::commit();

            ActivityLogger::log('reconciliation_voucher_edit', "Updated voucher #{$voucher->id} ({$pbt})", $voucher, [
                'debit' => $newD,
                'credit' => $newC,
            ]);

            return response()->json(['message' => 'Voucher updated', 'voucher' => $voucher->fresh()]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
