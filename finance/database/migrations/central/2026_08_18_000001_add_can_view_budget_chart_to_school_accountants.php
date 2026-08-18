<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('school_accountants', function (Blueprint $table) {
            if (!Schema::connection('central')->hasColumn('school_accountants', 'can_view_budget_chart')) {
                $table->boolean('can_view_budget_chart')->default(false)->after('is_main_accountant');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('school_accountants', function (Blueprint $table) {
            if (Schema::connection('central')->hasColumn('school_accountants', 'can_view_budget_chart')) {
                $table->dropColumn('can_view_budget_chart');
            }
        });
    }
};
