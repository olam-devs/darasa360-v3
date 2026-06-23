<?php

namespace App\Http\Controllers;

use App\Models\Particular;
use App\Models\Student;
use App\Models\Voucher;
use App\Support\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $query = Voucher::with(['student', 'particular', 'book']);

        if ($request->has('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->has('particular_id')) {
            $query->where('particular_id', $request->particular_id);
        }

        if ($request->has('book_id')) {
            $query->where('book_id', $request->book_id);
        }

        if ($request->has('date_from')) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('date', '<=', $request->date_to);
        }

        // Paginate with 15 items per page
        $vouchers = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Transform vouchers to include display names for expense/suspense entries
        $vouchers->getCollection()->transform(function ($voucher) {
            // Add display_student_name - use payment_by_receipt_to as fallback
            $voucher->display_student_name = $voucher->student->name ?? $voucher->payment_by_receipt_to ?? 'N/A';

            // Add display_particular_name - determine based on voucher type and context
            if ($voucher->payment_by_receipt_to === 'Advance Used') {
                $voucher->display_particular_name = ($voucher->particular?->name ?? 'Fee').' — paid from advance';
            } elseif ($voucher->particular) {
                $voucher->display_particular_name = $voucher->particular->name;
            } elseif ($voucher->payment_by_receipt_to === 'Suspense Account') {
                $voucher->display_particular_name = 'Suspense Account';
            } elseif ($voucher->payment_by_receipt_to === 'Suspense Reversal') {
                $voucher->display_particular_name = 'Suspense Reversal';
            } elseif ($voucher->payment_by_receipt_to === 'Suspense Resolution') {
                $voucher->display_particular_name = 'Suspense Resolution';
            } elseif ($voucher->voucher_type === 'Payment' && ! $voucher->student_id) {
                $voucher->display_particular_name = 'Expense';
            } else {
                $voucher->display_particular_name = 'N/A';
            }

            return $voucher;
        });

        return response()->json($vouchers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'student_id' => 'required|exists:students,id',
            'particular_id' => 'required|exists:particulars,id',
            'book_id' => 'nullable|exists:books,id',
            'voucher_type' => 'required|in:Sales,Receipt,Payment',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'payment_by_receipt_to' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $validated['created_by'] = auth()->id();

        DB::beginTransaction();
        try {
            $student = Student::findOrFail($validated['student_id']);
            $particular = Particular::findOrFail($validated['particular_id']);

            if (trim((string) ($validated['notes'] ?? '')) === '') {
                $validated['notes'] = match ($validated['voucher_type']) {
                    'Sales' => "Fee charged: {$particular->name} ({$student->name})",
                    'Receipt' => "Cash receipt for {$particular->name} ({$student->name})",
                    'Payment' => "Payment for {$particular->name} ({$student->name})",
                    default => "Fee entry: {$particular->name} ({$student->name})",
                };
            }

            // Update particular_student pivot table

            $pivot = $student->particulars()
                ->where('particular_id', $particular->id)
                ->first();

            // If this is a Receipt and exceeds outstanding balance, split into:
            // - Receipt to the particular (up to outstanding)
            // - Receipt to "Advance Payment" (remaining), tracked on student.advance_balance
            $advanceVoucher = null;
            if (($validated['voucher_type'] ?? null) === 'Receipt' && $pivot) {
                $incoming = (float) ($validated['debit'] ?? 0);
                $sales = (float) ($pivot->pivot->sales ?? 0);
                $debit = (float) ($pivot->pivot->debit ?? 0);
                $credit = (float) ($pivot->pivot->credit ?? 0);
                $outstanding = max(0.0, ($sales + $debit) - $credit);

                $applyToParticular = min($outstanding, $incoming);
                $advanceAmount = max(0.0, $incoming - $applyToParticular);

                // Adjust the main voucher amount to match what is applied to the particular.
                $validated['debit'] = $applyToParticular;

                if ($advanceAmount > 0.0) {
                    $student->advance_balance = (float) ($student->advance_balance ?? 0) + $advanceAmount;
                    $student->save();

                    $advanceVoucher = Voucher::create([
                        'date' => $validated['date'],
                        'student_id' => $validated['student_id'],
                        'particular_id' => null,
                        'book_id' => $validated['book_id'],
                        'voucher_type' => 'Receipt',
                        'debit' => $advanceAmount,
                        'credit' => 0,
                        'payment_by_receipt_to' => 'Advance Payment',
                        'notes' => trim(($validated['notes'] ?? '').' [Advance payment]'),
                        'created_by' => $validated['created_by'],
                    ]);
                }
            }

            $voucher = Voucher::create($validated);

            if ($pivot) {
                $salesDelta = 0;
                $creditDelta = 0;

                // Pivot schema tracks expected fees (sales) and payments received (credit).
                if ($voucher->voucher_type === 'Sales') {
                    $salesDelta = (float) ($validated['debit'] ?? 0);
                } elseif ($voucher->voucher_type === 'Receipt') {
                    // Canonical storage: Receipt is DR (debit), but pivot stores "credit" as amount paid.
                    $creditDelta = (float) ($validated['debit'] ?? 0);
                }

                if ($salesDelta !== 0.0 || $creditDelta !== 0.0) {
                    $student->particulars()->updateExistingPivot($particular->id, [
                        'sales' => (float) $pivot->pivot->sales + $salesDelta,
                        'credit' => (float) $pivot->pivot->credit + $creditDelta,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'voucher' => $voucher,
                'advance_voucher' => $advanceVoucher,
                'student_advance_balance' => (float) ($student->advance_balance ?? 0),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        $voucher = Voucher::with(['student', 'particular', 'book'])->findOrFail($id);

        return response()->json($voucher);
    }

    public function update(Request $request, $id)
    {
        $voucher = Voucher::findOrFail($id);

        $validated = $request->validate([
            'date' => 'required|date',
            'debit' => 'nullable|numeric|min:0',
            'credit' => 'nullable|numeric|min:0',
            'payment_by_receipt_to' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $oldDebit = (float) $voucher->debit;
            $oldCredit = (float) $voucher->credit;
            $oldType = $voucher->voucher_type;

            $voucher->update($validated);

            // Keep pivot in sync for student fee accounting.
            // Only apply to vouchers that actually affect student fee balances.
            if ($voucher->student_id && $voucher->particular_id) {
                $student = Student::find($voucher->student_id);
                $particular = Particular::find($voucher->particular_id);
                if ($student && $particular) {
                    $pivot = $student->particulars()
                        ->where('particular_id', $particular->id)
                        ->first();

                    if ($pivot) {
                        $oldSalesDelta = $oldType === 'Sales' ? $oldDebit : 0.0;
                        $oldCreditDelta = $oldType === 'Receipt' ? $oldDebit : 0.0;

                        $newSalesDelta = $oldType === 'Sales' ? (float) ($voucher->debit) : 0.0;
                        $newCreditDelta = $oldType === 'Receipt' ? (float) ($voucher->debit) : 0.0;

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

            return response()->json($voucher);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Vouchers (fee receipts / sales) are no longer deletable so the ledger stays auditable.
     * Use the void endpoint to reverse a mistaken entry while preserving the record.
     */
    public function destroy($id)
    {
        return response()->json([
            'error' => 'Vouchers cannot be deleted. Use Void to reverse a mistaken entry; the record will be kept for audit.',
        ], 403);
    }

    /**
     * Void a voucher: zero its amounts, reverse pivot impact, mark as voided with a reason.
     * The row stays in the ledger so reports and audits remain consistent.
     */
    public function void(Request $request, $id)
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $voucher = Voucher::findOrFail($id);

        if (! is_null($voucher->voided_at)) {
            return response()->json(['error' => 'Voucher already voided.'], 400);
        }

        DB::beginTransaction();
        try {
            $student = $voucher->student;
            $particular = $voucher->particular;

            if ($student && $particular) {
                $pivot = $student->particulars()
                    ->where('particular_id', $particular->id)
                    ->first();

                if ($pivot) {
                    $salesDelta = $voucher->voucher_type === 'Sales' ? (float) $voucher->debit : 0.0;
                    $creditDelta = $voucher->voucher_type === 'Receipt' ? (float) $voucher->debit : 0.0;

                    if ($salesDelta !== 0.0 || $creditDelta !== 0.0) {
                        $student->particulars()->updateExistingPivot($particular->id, [
                            'sales' => max(0, (float) $pivot->pivot->sales - $salesDelta),
                            'credit' => max(0, (float) $pivot->pivot->credit - $creditDelta),
                        ]);
                    }
                }
            }

            $voucher->forceFill([
                'debit' => 0,
                'credit' => 0,
                'voided_at' => now(),
                'voided_by' => auth()->id(),
                'void_reason' => $validated['reason'],
                'notes' => trim((string) $voucher->notes.' [VOIDED: '.$validated['reason'].']'),
            ])->save();

            DB::commit();

            ActivityLogger::log('voucher_voided', "Voided voucher #{$voucher->id}: {$validated['reason']}", $voucher);

            return response()->json(['message' => 'Voucher voided', 'voucher' => $voucher->fresh()]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function searchStudent(Request $request)
    {
        $search = $request->get('q', '');

        $students = Student::where('name', 'LIKE', "%{$search}%")
            ->orWhere('student_reg_no', 'LIKE', "%{$search}%")
            ->with('schoolClass')
            ->limit(10)
            ->get();

        return response()->json($students);
    }

    /**
     * Apply some of a student's existing advance balance against a particular.
     * Creates a Receipt voucher (no book/cash movement since the cash is already in advance),
     * decrements student.advance_balance, and credits the particular pivot.
     */
    public function applyAdvance(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'particular_id' => 'required|exists:particulars,id',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'notes' => 'nullable|string|max:500',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $particular = Particular::findOrFail($validated['particular_id']);

        $available = (float) ($student->advance_balance ?? 0);
        $amount = (float) $validated['amount'];

        if ($amount > $available + 0.0001) {
            return response()->json([
                'error' => 'Amount exceeds available advance balance (TSh '.number_format($available, 2).').',
            ], 400);
        }

        $pivot = $student->particulars()->where('particular_id', $particular->id)->first();
        if (! $pivot) {
            return response()->json([
                'error' => 'This student is not assigned to that particular.',
            ], 400);
        }

        $outstanding = max(0.0, (float) $pivot->pivot->sales + (float) $pivot->pivot->debit - (float) $pivot->pivot->credit);
        if ($amount > $outstanding + 0.0001) {
            return response()->json([
                'error' => 'Amount exceeds outstanding balance for this particular (TSh '.number_format($outstanding, 2).').',
            ], 400);
        }

        $noteText = trim((string) ($validated['notes'] ?? ''));
        if ($noteText === '') {
            $noteText = "Fee payment for {$particular->name} ({$student->name}) — paid from advance balance";
        } elseif (! str_contains(strtolower($noteText), 'advance')) {
            $noteText .= ' — paid from advance balance';
        }

        DB::beginTransaction();
        try {
            // No book_id: cash already in advance; this only allocates advance to a particular (student ledger).
            $voucher = Voucher::create([
                'date' => $validated['date'],
                'student_id' => $student->id,
                'particular_id' => $particular->id,
                'book_id' => null,
                'voucher_type' => 'Receipt',
                'debit' => $amount,
                'credit' => 0,
                'payment_by_receipt_to' => 'Advance Used',
                'notes' => $noteText,
                'created_by' => auth()->id(),
            ]);

            $student->advance_balance = max(0.0, $available - $amount);
            $student->save();

            $student->particulars()->updateExistingPivot($particular->id, [
                'credit' => (float) $pivot->pivot->credit + $amount,
            ]);

            DB::commit();

            ActivityLogger::log(
                'advance_applied',
                "Applied TSh ".number_format($amount, 2)." advance for {$student->name} → {$particular->name}",
                $voucher,
                ['student_id' => $student->id, 'particular_id' => $particular->id]
            );

            return response()->json([
                'voucher' => $voucher,
                'student_advance_balance' => (float) $student->advance_balance,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
