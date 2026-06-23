<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Fix existing expense vouchers to have correct DR/CR values
     * Expenses should be: debit = amount, credit = 0 (DR in bank view, CR in cash view)
     */
    public function up(): void
    {
        // Find all expense vouchers (Payment type with no student_id and payment_by_receipt_to is not suspense-related)
        // These were incorrectly stored with credit = amount, debit = 0
        // We need to swap them to debit = amount, credit = 0
        DB::table('vouchers')
            ->where('voucher_type', 'Payment')
            ->whereNull('student_id')
            ->whereNull('particular_id')
            ->where(function($query) {
                $query->whereNull('payment_by_receipt_to')
                    ->orWhere(function($q) {
                        $q->where('payment_by_receipt_to', '!=', 'Suspense Account')
                          ->where('payment_by_receipt_to', '!=', 'Suspense Reversal')
                          ->where('payment_by_receipt_to', '!=', 'Suspense Resolution');
                    });
            })
            ->where('credit', '>', 0)
            ->where('debit', '=', 0)
            ->update([
                'debit' => DB::raw('credit'),
                'credit' => 0
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse: swap back debit to credit for expense vouchers
        DB::table('vouchers')
            ->where('voucher_type', 'Payment')
            ->whereNull('student_id')
            ->whereNull('particular_id')
            ->where(function($query) {
                $query->whereNull('payment_by_receipt_to')
                    ->orWhere(function($q) {
                        $q->where('payment_by_receipt_to', '!=', 'Suspense Account')
                          ->where('payment_by_receipt_to', '!=', 'Suspense Reversal')
                          ->where('payment_by_receipt_to', '!=', 'Suspense Resolution');
                    });
            })
            ->where('debit', '>', 0)
            ->where('credit', '=', 0)
            ->update([
                'credit' => DB::raw('debit'),
                'debit' => 0
            ]);
    }
};
