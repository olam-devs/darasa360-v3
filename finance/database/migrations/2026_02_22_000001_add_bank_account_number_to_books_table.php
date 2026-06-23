<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds missing columns to the books table that exist in the model but not in the original migration.
     */
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (!Schema::hasColumn('books', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('account_number');
            }

            if (!Schema::hasColumn('books', 'is_cash_book')) {
                $table->boolean('is_cash_book')->default(false)->after('is_active');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('books', function (Blueprint $table) {
            if (Schema::hasColumn('books', 'bank_account_number')) {
                $table->dropColumn('bank_account_number');
            }
            if (Schema::hasColumn('books', 'is_cash_book')) {
                $table->dropColumn('is_cash_book');
            }
        });
    }
};
