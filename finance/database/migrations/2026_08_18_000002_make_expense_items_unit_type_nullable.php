<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('expense_items')) {
            return;
        }
        Schema::table('expense_items', function (Blueprint $table) {
            $table->string('unit_type')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('expense_items')) {
            return;
        }
        Schema::table('expense_items', function (Blueprint $table) {
            $table->string('unit_type')->nullable(false)->change();
        });
    }
};
