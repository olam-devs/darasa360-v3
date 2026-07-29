<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * ExpenseController::store()/update() never populate `category`, and
     * validate `description` as nullable - but both columns were created
     * NOT NULL with no default, so creating an expense without a
     * description 500s (SQLSTATE 23000), and `category` silently gets an
     * empty string on hosts where MySQL isn't in strict mode.
     */
    public function up(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->string('category')->nullable()->change();
                $table->string('description')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('expenses')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->string('category')->nullable(false)->change();
                $table->string('description')->nullable(false)->change();
            });
        }
    }
};
