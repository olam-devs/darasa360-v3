<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('expense_categories', function (Blueprint $table) {
            $table->boolean('main_accountant_only')->default(false)->after('is_system');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('expense_categories', function (Blueprint $table) {
            $table->dropColumn('main_accountant_only');
        });
    }
};
