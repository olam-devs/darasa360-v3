<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `payroll_entries.deductions` is a dead legacy scalar column (always
     * 0.00, never written to by any code path since the 2026-06-18
     * schema fix introduced the real `payroll_entry_deductions` table +
     * `total_deductions`) - but its name collides with the model's
     * `deductions()` HasMany relationship. Eloquent always resolves a
     * real column over a same-named relation, so `$payrollEntry->deductions`
     * returned this raw "0.00" string everywhere instead of the actual
     * deduction line items, breaking every deductions breakdown in the
     * UI/API and crashing anything that called ->sum()/->each() on it.
     */
    public function up(): void
    {
        if (Schema::hasTable('payroll_entries') && Schema::hasColumn('payroll_entries', 'deductions')) {
            Schema::table('payroll_entries', function (Blueprint $table) {
                $table->dropColumn('deductions');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payroll_entries') && ! Schema::hasColumn('payroll_entries', 'deductions')) {
            Schema::table('payroll_entries', function (Blueprint $table) {
                $table->decimal('deductions', 15, 2)->default(0)->after('allowances');
            });
        }
    }
};
