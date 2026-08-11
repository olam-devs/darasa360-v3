<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Which BookFeeCategory (if any) the main accountant explicitly picked
     * when approving, so the fee that got cut is traceable later - same
     * no-FK convention as the rest of this table (book_id, voucher_id, etc.)
     * since book_fee_categories ultimately chains back to books, which is
     * MyISAM on some already-provisioned tenant schools.
     */
    public function up(): void
    {
        Schema::table('expense_submissions', function (Blueprint $table) {
            if (!Schema::hasColumn('expense_submissions', 'bank_fee_category_id')) {
                $table->unsignedBigInteger('bank_fee_category_id')->nullable()->after('bank_fee_amount');
                $table->index('bank_fee_category_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expense_submissions', function (Blueprint $table) {
            if (Schema::hasColumn('expense_submissions', 'bank_fee_category_id')) {
                $table->dropIndex(['bank_fee_category_id']);
                $table->dropColumn('bank_fee_category_id');
            }
        });
    }
};
