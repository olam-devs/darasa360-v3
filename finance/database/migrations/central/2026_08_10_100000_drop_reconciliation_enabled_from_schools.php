<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * reconciliation_enabled was a same-session mistake: reconciliation is
     * inherently per-accountant (who is trusted to edit historical records),
     * not a school-wide on/off switch like headmaster_management_enabled or
     * parent_portal_management_enabled genuinely are. Superseded by the
     * existing per-accountant SchoolAccountant::can_edit_history, now managed
     * from the super admin's Accountants list instead of the accountant's own
     * (previously disconnected/broken) self-service Settings panel.
     */
    public function up(): void
    {
        Schema::connection('central')->table('schools', function (Blueprint $table) {
            if (Schema::connection('central')->hasColumn('schools', 'reconciliation_enabled')) {
                $table->dropColumn('reconciliation_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('schools', function (Blueprint $table) {
            $table->boolean('reconciliation_enabled')->default(false)->after('parent_portal_management_enabled');
        });
    }
};
