<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (! Schema::hasColumn('books', 'bank_fees_enabled')) {
                $table->boolean('bank_fees_enabled')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('books', 'bank_fee_particular_id')) {
                $table->foreignId('bank_fee_particular_id')
                    ->nullable()
                    ->after('bank_fees_enabled')
                    ->constrained('particulars')
                    ->nullOnDelete();
            }
        });

        if (! Schema::hasTable('book_bank_fee_tiers')) {
            Schema::create('book_bank_fee_tiers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
                $table->decimal('amount_from', 15, 2);
                $table->decimal('amount_to', 15, 2)->nullable();
                $table->decimal('fee_amount', 15, 2);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }

        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'bank_fee_amount')) {
                $table->decimal('bank_fee_amount', 15, 2)->nullable()->after('amount');
            }
            if (! Schema::hasColumn('expenses', 'bank_fee_voucher_id')) {
                $table->foreignId('bank_fee_voucher_id')
                    ->nullable()
                    ->after('voucher_id')
                    ->constrained('vouchers')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'bank_fee_voucher_id')) {
                $table->dropForeign(['bank_fee_voucher_id']);
                $table->dropColumn('bank_fee_voucher_id');
            }
            if (Schema::hasColumn('expenses', 'bank_fee_amount')) {
                $table->dropColumn('bank_fee_amount');
            }
        });

        Schema::dropIfExists('book_bank_fee_tiers');

        Schema::table('books', function (Blueprint $table) {
            if (Schema::hasColumn('books', 'bank_fee_particular_id')) {
                $table->dropForeign(['bank_fee_particular_id']);
                $table->dropColumn('bank_fee_particular_id');
            }
            if (Schema::hasColumn('books', 'bank_fees_enabled')) {
                $table->dropColumn('bank_fees_enabled');
            }
        });
    }
};
