<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The old single-amount Expenses module is replaced by the new
     * category/item/approval-based expense_submissions system. Renamed
     * (not dropped) so any pre-existing rows (confirmed: zero on live,
     * a handful of test rows on sandbox) stay readable for historical
     * reference via App\Models\Expense, now pointed at legacy_expenses.
     * No automated data migration into the new schema - the two are
     * structurally incompatible (single amount vs. line items, no
     * category on the old rows) and there's no real data to migrate.
     */
    public function up(): void
    {
        if (Schema::hasTable('expenses') && !Schema::hasTable('legacy_expenses')) {
            Schema::rename('expenses', 'legacy_expenses');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('legacy_expenses') && !Schema::hasTable('expenses')) {
            Schema::rename('legacy_expenses', 'expenses');
        }
    }
};
