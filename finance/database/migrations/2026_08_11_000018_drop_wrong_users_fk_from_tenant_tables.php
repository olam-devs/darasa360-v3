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
            // 'expenses' was renamed to 'legacy_expenses' by an earlier
            // migration (2026_08_11_000015) - by the time this migration
            // runs, the table is always called legacy_expenses, never the
            // original name, on both already-provisioned and future schools.
            'legacy_expenses' => ['created_by', 'approved_by', 'processed_by'],
            'suspense_accounts' => ['resolved_by', 'created_by'],
            'payroll_entries' => ['created_by', 'approved_by'],
            'payroll_deduction_types' => ['created_by'],
            'staff' => ['created_by'],
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
                // Looked up by column, not by assuming Laravel's
                // {table}_{column}_foreign naming convention - a renamed
                // table (legacy_expenses, formerly expenses) keeps its
                // original constraint names, so guessing the name from the
                // table's current name would silently miss it.
                $constraintName = DB::selectOne(
                    "SELECT kcu.CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE kcu
                     JOIN information_schema.TABLE_CONSTRAINTS tc
                       ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME AND tc.TABLE_SCHEMA = kcu.TABLE_SCHEMA
                     WHERE kcu.TABLE_SCHEMA = DATABASE()
                       AND kcu.TABLE_NAME = ?
                       AND kcu.COLUMN_NAME = ?
                       AND kcu.REFERENCED_TABLE_NAME = 'users'
                       AND tc.CONSTRAINT_TYPE = 'FOREIGN KEY'
                     LIMIT 1",
                    [$table, $column]
                )?->CONSTRAINT_NAME;

                if ($constraintName) {
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
