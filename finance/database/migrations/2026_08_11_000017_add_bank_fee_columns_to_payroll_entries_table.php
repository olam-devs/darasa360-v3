<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mirrors expense_submissions' three bank-fee columns - optional, only
     * populated if the accountant explicitly picks a BookFeeCategory when
     * processing payroll. No FK (same convention as the rest of this app's
     * tenant tables) since book_fee_categories chains back to books, which
     * is MyISAM on at least one already-provisioned tenant.
     */
    public function up(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            if (!Schema::hasColumn('payroll_entries', 'bank_fee_voucher_id')) {
                $table->unsignedBigInteger('bank_fee_voucher_id')->nullable()->after('voucher_id');
                $table->index('bank_fee_voucher_id');
            }
            if (!Schema::hasColumn('payroll_entries', 'bank_fee_amount')) {
                $table->decimal('bank_fee_amount', 15, 2)->nullable()->after('net_salary');
            }
            if (!Schema::hasColumn('payroll_entries', 'bank_fee_category_id')) {
                $table->unsignedBigInteger('bank_fee_category_id')->nullable()->after('bank_fee_amount');
                $table->index('bank_fee_category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payroll_entries', function (Blueprint $table) {
            if (Schema::hasColumn('payroll_entries', 'bank_fee_voucher_id')) {
                $table->dropIndex(['bank_fee_voucher_id']);
                $table->dropColumn('bank_fee_voucher_id');
            }
            if (Schema::hasColumn('payroll_entries', 'bank_fee_category_id')) {
                $table->dropIndex(['bank_fee_category_id']);
                $table->dropColumn('bank_fee_category_id');
            }
            if (Schema::hasColumn('payroll_entries', 'bank_fee_amount')) {
                $table->dropColumn('bank_fee_amount');
            }
        });
    }
};
