<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Super-admin-designated "main accountant" per school - can grant/revoke
     * can_edit_history/can_view_logs for other accountants at their own
     * school without going through the super admin each time. Independent of
     * can_edit_history/can_view_logs and of is_primary (an unrelated,
     * auto-set "first accountant registered" display flag) - being main does
     * not itself grant any permission, only the power to manage others'.
     */
    public function up(): void
    {
        Schema::connection('central')->table('school_accountants', function (Blueprint $table) {
            if (!Schema::connection('central')->hasColumn('school_accountants', 'is_main_accountant')) {
                $table->boolean('is_main_accountant')->default(false)->after('is_primary');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('school_accountants', function (Blueprint $table) {
            $table->dropColumn('is_main_accountant');
        });
    }
};
