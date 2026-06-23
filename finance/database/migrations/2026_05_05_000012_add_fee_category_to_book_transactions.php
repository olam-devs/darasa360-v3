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
            // Older schema (transfers) didn't include these columns. Add them so we can
            // support deposits/withdrawals + reconciliation-friendly metadata.
            if (! Schema::hasColumn('book_transactions', 'book_id')) {
                $table->foreignId('book_id')
                    ->nullable()
                    ->constrained('books')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('book_transactions', 'transaction_type')) {
                $table->enum('transaction_type', ['deposit', 'withdrawal'])->nullable();
            }
            if (! Schema::hasColumn('book_transactions', 'short_notes')) {
                $table->string('short_notes')->nullable();
            }
            if (! Schema::hasColumn('book_transactions', 'full_details')) {
                $table->text('full_details')->nullable();
            }
            if (! Schema::hasColumn('book_transactions', 'voucher_id')) {
                $table->foreignId('voucher_id')
                    ->nullable()
                    ->constrained('vouchers')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('book_transactions', 'fee_category_id')) {
                $table->foreignId('fee_category_id')
                    ->nullable()
                    ->constrained('book_fee_categories')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('book_transactions', 'fee_voucher_id')) {
                $table->foreignId('fee_voucher_id')
                    ->nullable()
                    ->constrained('vouchers')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('book_transactions')) {
            return;
        }

        Schema::table('book_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('book_transactions', 'fee_voucher_id')) {
                $table->dropForeign(['fee_voucher_id']);
                $table->dropColumn('fee_voucher_id');
            }
            if (Schema::hasColumn('book_transactions', 'fee_category_id')) {
                $table->dropForeign(['fee_category_id']);
                $table->dropColumn('fee_category_id');
            }
            if (Schema::hasColumn('book_transactions', 'voucher_id')) {
                $table->dropForeign(['voucher_id']);
                $table->dropColumn('voucher_id');
            }
            if (Schema::hasColumn('book_transactions', 'full_details')) {
                $table->dropColumn('full_details');
            }
            if (Schema::hasColumn('book_transactions', 'short_notes')) {
                $table->dropColumn('short_notes');
            }
            if (Schema::hasColumn('book_transactions', 'transaction_type')) {
                $table->dropColumn('transaction_type');
            }
            if (Schema::hasColumn('book_transactions', 'book_id')) {
                $table->dropForeign(['book_id']);
                $table->dropColumn('book_id');
            }
        });
    }
};
