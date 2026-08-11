<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * created_by/approved_by/processed_by/resolved_by/sent_by across these
     * tables were wrongly given real FK constraints to the tenant `users`
     * table by the original base migration - but every real controller
     * populates them from auth()->id(), which for an accountant resolves
     * to the CENTRAL SchoolAccountant id, not a tenant users.id. On any
     * already-provisioned school where this FK is actually enforced
     * (confirmed on LITTLE DOVES, live), the first real write to any of
     * these columns 500s with "Cannot add or update a child row" - this
     * silently broke SMS logging (a real message got sent to the real
     * gateway with no record kept and no credit deducted) and would have
     * hit vouchers/expenses/payroll/etc the first time any of those were
     * used for real. The base migration itself is fixed for future
     * schools in the same commit; this migration cleans up schools that
     * already ran the old version. Some already-provisioned schools (e.g.
     * OLAM SECONDARY on sandbox) never got this FK for unrelated
     * schema-history reasons, so each drop is checked via
     * information_schema rather than assumed present.
     */
    public function up(): void
    {
        $targets = [
            'vouchers' => ['created_by'],
            'expenses' => ['created_by', 'approved_by', 'processed_by'],
            'suspense_accounts' => ['resolved_by', 'created_by'],
            'payroll_entries' => ['created_by', 'approved_by'],
            'sms_logs' => ['sent_by'],
            'sms_templates' => ['created_by'],
            'bank_transactions' => ['processed_by'],
            'book_transactions' => ['created_by'],
        ];

        foreach ($targets as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            foreach ($columns as $column) {
                $constraintName = "{$table}_{$column}_foreign";

                $exists = DB::select(
                    "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY'",
                    [$table, $constraintName]
                );

                if (! empty($exists)) {
                    Schema::table($table, function ($t) use ($constraintName) {
                        $t->dropForeign($constraintName);
                    });
                }
            }
        }
    }

    /**
     * Not reversible - re-adding these FKs would just reintroduce the bug
     * this migration fixes.
     */
    public function down(): void
    {
    }
};
