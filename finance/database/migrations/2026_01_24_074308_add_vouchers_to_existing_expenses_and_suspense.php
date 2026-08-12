<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\SuspenseAccount;
use App\Models\Voucher;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // This migration adds voucher entries for existing expenses and suspense accounts
        // that were created before the ledger entry feature was implemented.
        //
        // Uses DB::table('expenses') rather than App\Models\Expense because the
        // Expense model's $table was later changed to 'legacy_expenses' (when the
        // old Expenses module was replaced by the approval-based ExpenseSubmission
        // system). When this migration runs for a brand-new school, the table is
        // still called 'expenses' at this point in the migration sequence; the
        // rename to legacy_expenses happens in a later migration (000015).

        DB::transaction(function () {
            // 1. Add vouchers for existing processed expenses that don't have voucher_id
            $expenses = DB::table('expenses')
                ->where('status', 'processed')
                ->whereNull('voucher_id')
                ->get();

            foreach ($expenses as $expense) {
                // Create a Payment voucher for this expense
                $voucher = Voucher::create([
                    'date' => $expense->transaction_date,
                    'student_id' => null,
                    'particular_id' => null,
                    'book_id' => $expense->book_id,
                    'voucher_type' => 'Payment',
                    'debit' => 0,
                    'credit' => $expense->amount,
                    'payment_by_receipt_to' => $expense->expense_name,
                    'notes' => $expense->description . ' (Retroactively added)',
                    'created_by' => $expense->processed_by ?? $expense->created_by,
                ]);

                // Update expense with voucher_id
                DB::table('expenses')->where('id', $expense->id)->update(['voucher_id' => $voucher->id]);
            }

            // 2. Add vouchers for existing suspense accounts that don't have voucher_id
            $suspenseAccounts = SuspenseAccount::whereNull('voucher_id')
                ->get();

            foreach ($suspenseAccounts as $suspense) {
                // Create a Receipt voucher for the suspense account creation
                $voucher = Voucher::create([
                    'date' => $suspense->date,
                    'student_id' => null,
                    'particular_id' => null,
                    'book_id' => $suspense->book_id,
                    'voucher_type' => 'Receipt',
                    'debit' => 0,
                    'credit' => $suspense->amount,
                    'payment_by_receipt_to' => 'Suspense Account',
                    'notes' => $suspense->description . (isset($suspense->reference_number) ? ' (Ref: ' . $suspense->reference_number . ')' : '') . ' (Retroactively added)',
                    'created_by' => $suspense->created_by,
                ]);

                // Update suspense account with voucher_id
                $suspense->update(['voucher_id' => $voucher->id]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the retroactively added vouchers
        DB::transaction(function () {
            // Find and delete vouchers that were retroactively added
            Voucher::where('notes', 'LIKE', '%(Retroactively added)%')->delete();

            // Clear voucher_id from expenses and suspense accounts
            DB::table('expenses')->whereNotNull('voucher_id')->update(['voucher_id' => null]);
            SuspenseAccount::whereNotNull('voucher_id')->update(['voucher_id' => null]);
        });
    }
};
