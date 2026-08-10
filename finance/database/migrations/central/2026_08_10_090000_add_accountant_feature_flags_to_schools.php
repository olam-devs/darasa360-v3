<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Super-admin-controlled per-school toggles for accountant features that
     * are not universal: headmaster account setup, parent portal password
     * setup, and reconciliation. Default false so existing schools don't
     * silently gain access to a feature nobody explicitly turned on.
     */
    public function up(): void
    {
        Schema::connection('central')->table('schools', function (Blueprint $table) {
            if (!Schema::connection('central')->hasColumn('schools', 'headmaster_management_enabled')) {
                $table->boolean('headmaster_management_enabled')->default(false)->after('parent_cross_access');
            }
            if (!Schema::connection('central')->hasColumn('schools', 'parent_portal_management_enabled')) {
                $table->boolean('parent_portal_management_enabled')->default(false)->after('headmaster_management_enabled');
            }
            if (!Schema::connection('central')->hasColumn('schools', 'reconciliation_enabled')) {
                $table->boolean('reconciliation_enabled')->default(false)->after('parent_portal_management_enabled');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('schools', function (Blueprint $table) {
            $table->dropColumn(['headmaster_management_enabled', 'parent_portal_management_enabled', 'reconciliation_enabled']);
        });
    }
};
