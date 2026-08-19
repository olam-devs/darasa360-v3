<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('tenant')->hasColumn('expense_items', 'item_type')) {
            Schema::connection('tenant')->table('expense_items', function (Blueprint $table) {
                $table->enum('item_type', ['goods', 'service'])->default('goods')->after('unit_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::connection('tenant')->hasColumn('expense_items', 'item_type')) {
            Schema::connection('tenant')->table('expense_items', function (Blueprint $table) {
                $table->dropColumn('item_type');
            });
        }
    }
};
