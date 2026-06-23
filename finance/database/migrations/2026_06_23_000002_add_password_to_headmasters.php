<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Runs on tenant DB — no $connection set; caller sets DB dynamically.

    public function up(): void
    {
        if (Schema::hasTable('headmasters') && !Schema::hasColumn('headmasters', 'password')) {
            Schema::table('headmasters', function (Blueprint $table) {
                $table->string('password')->nullable()->after('phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('headmasters', 'password')) {
            Schema::table('headmasters', function (Blueprint $table) {
                $table->dropColumn('password');
            });
        }
    }
};
