<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ExpenseController::process() sets `processed_at` (also in
     * Expense::$fillable) but the column was never added to the
     * `expenses` table - every attempt to process a pending expense
     * 500s with "Unknown column 'processed_at'".
     */
    public function up(): void
    {
        if (Schema::hasTable('expenses') && ! Schema::hasColumn('expenses', 'processed_at')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->timestamp('processed_at')->nullable()->after('processed_by');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'processed_at')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->dropColumn('processed_at');
            });
        }
    }
};
