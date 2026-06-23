<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Student;
use App\Models\SuspenseAccount;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuspenseAccountController extends Controller
{
    public function index()
    {
        $suspenseAccounts = SuspenseAccount::with(['book', 'student'])
            ->orderBy('resolved')
            ->orderBy('date', 'desc')
            ->get();

        // Calculate summary totals
        $totalUnresolved = $suspenseAccounts->sum(function ($suspense) {
            return $suspense->getUnresolvedAmount();
        });

        $totalResolved = $suspenseAccounts->sum('resolved_amount');

        return response()->json([
            'suspense_accounts' => $suspenseAccounts,
            'summary' => [
                'total_unresolved' => $totalUnresolved,
                'total_resolved' => $totalResolved,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'book_id' => 'required|exists:books,id',
            'description' => 'required|string',
            'reference_number' => 'nullable|string',
            'date' => 'required|date',
        ]);

        $validated['created_by'] = auth()->id();
        $validated['resolved'] = false;
        $validated['resolved_amount'] = 0; // Initialize resolved_amount to 0

        DB::beginTransaction();
        try {
            // Create voucher entry for suspense account
            // Canonical storage (accountant view): money received -> DR (debit)
            $voucher = Voucher::create([
                'date' => $validated['date'],
                'student_id' => null,
                'particular_id' => null,
                'book_id' => $validated['book_id'],
                'voucher_type' => 'Receipt',
                'debit' => $validated['amount'],
                'credit' => 0,
                'payment_by_receipt_to' => 'Suspense Account',
                'notes' => $validated['description'].(isset($validated['reference_number']) ? ' (Ref: '.$validated['reference_number'].')' : ''),
                'created_by' => auth()->id(),
            ]);

            // Create suspense account with voucher reference
            $validated['voucher_id'] = $voucher->id;
            $suspense = SuspenseAccount::create($validated);

            DB::commit();

            return response()->json($suspense->load('voucher'), 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $suspense = SuspenseAccount::with(['book', 'student', 'voucher'])->findOrFail($id);

        return response()->json($suspense);
    }

    public function update(Request $request, $id)
    {
        $suspense = SuspenseAccount::findOrFail($id);

        if ($suspense->resolved) {
            return response()->json([
                'error' => 'Cannot update resolved suspense account',
            ], 400);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
            'reference_number' => 'nullable|string',
            'date' => 'required|date',
        ]);

        $suspense->update($validated);

        return response()->json($suspense);
    }

    public function destroy($id)
    {
        $suspense = SuspenseAccount::findOrFail($id);

        if ($suspense->resolved) {
            return response()->json([
                'error' => 'Cannot delete resolved suspense account',
            ], 400);
        }

        $suspense->delete();

        return response()->json([
            'message' => 'Suspense account deleted successfully',
        ]);
    }

    public function resolve(Request $request, $id)
    {
        $suspense = SuspenseAccount::findOrFail($id);

        if ($suspense->resolved) {
            return response()->json([
                'error' => 'Suspense account already resolved',
            ], 400);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'particular_id' => 'required|exists:particulars,id',
            'amount_to_resolve' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $amountToResolve = min($validated['amount_to_resolve'], $suspense->getUnresolvedAmount());

        DB::beginTransaction();
        try {
            // Step 1: Create balancing/reversal entry to reverse the original suspense entry
            // We want a net-zero change to the book for the resolution operation:
            // create a CR (Payment) for the amount being resolved.
            $balancingVoucher = Voucher::create([
                'date' => now(),
                'student_id' => null,
                'particular_id' => null,
                'book_id' => $suspense->book_id,
                'voucher_type' => 'Payment',
                'debit' => 0,
                'credit' => $amountToResolve,
                'payment_by_receipt_to' => 'Suspense Reversal',
                'notes' => 'Balancing entry for suspense resolution',
                'created_by' => auth()->id(),
            ]);

            // Step 2: Create normal student receipt entry
            // Canonical storage: Receipt is DR (debit)
            $receiptVoucher = Voucher::create([
                'date' => now(),
                'student_id' => $validated['student_id'],
                'particular_id' => $validated['particular_id'],
                'book_id' => $suspense->book_id,
                'voucher_type' => 'Receipt',
                'debit' => $amountToResolve,
                'credit' => 0,
                'payment_by_receipt_to' => 'Suspense Resolution',
                'notes' => $validated['notes'] ?? 'Resolved from suspense account',
                'created_by' => auth()->id(),
            ]);

            // Update suspense account
            $newResolvedAmount = $suspense->resolved_amount + $amountToResolve;
            $suspense->update([
                'resolved_amount' => $newResolvedAmount,
                'resolved' => $newResolvedAmount >= $suspense->amount,
                'resolved_student_id' => $validated['student_id'],
                'voucher_id' => $receiptVoucher->id, // Store the receipt voucher ID
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);

            // Update student's particular balance
            $student = Student::find($validated['student_id']);
            $pivot = $student->particulars()
                ->where('particular_id', $validated['particular_id'])
                ->first();

            if ($pivot) {
                $student->particulars()->updateExistingPivot($validated['particular_id'], [
                    'credit' => $pivot->pivot->credit + $amountToResolve,
                ]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Suspense account resolved successfully',
                'suspense' => $suspense->fresh(),
                'balancing_voucher' => $balancingVoucher,
                'receipt_voucher' => $receiptVoucher,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function unresolvedSummary()
    {
        $unresolved = SuspenseAccount::where('resolved', false)
            ->with('book')
            ->get();

        $totalUnresolved = $unresolved->sum(function ($suspense) {
            return $suspense->getUnresolvedAmount();
        });

        $byBook = $unresolved->groupBy('book_id')->map(function ($group) {
            return [
                'book' => $group->first()->book,
                'count' => $group->count(),
                'total' => $group->sum(function ($s) {
                    return $s->getUnresolvedAmount();
                }),
            ];
        })->values();

        return response()->json([
            'total_unresolved' => $totalUnresolved,
            'count' => $unresolved->count(),
            'by_book' => $byBook,
        ]);
    }
}
