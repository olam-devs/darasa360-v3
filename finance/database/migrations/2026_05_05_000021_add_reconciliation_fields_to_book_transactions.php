<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('book_transactions')) {
            return;
        }

        Schema::table('book_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('book_transactions', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('fee_voucher_id');
            }
            if (! Schema::hasColumn('book_transactions', 'cancel_reason')) {
                $table->string('cancel_reason')->nullable()->after('cancelled_at');
            }
            if (! Schema::hasColumn('book_transactions', 'replaced_by_transaction_id')) {
                $table->unsignedBigInteger('replaced_by_transaction_id')->nullable()->after('cancel_reason');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('book_transactions')) {
            return;
        }

        Schema::table('book_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('book_transactions', 'replaced_by_transaction_id')) {
                $table->dropColumn('replaced_by_transaction_id');
            }
            if (Schema::hasColumn('book_transactions', 'cancel_reason')) {
                $table->dropColumn('cancel_reason');
            }
            if (Schema::hasColumn('book_transactions', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
        });
    }
};
