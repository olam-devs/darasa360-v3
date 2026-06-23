<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'advance_balance')) {
            Schema::table('students', function (Blueprint $table) {
                $table->decimal('advance_balance', 15, 2)->default(0)->after('status');
            });
        }

        if (Schema::hasTable('particular_student') && ! Schema::hasColumn('particular_student', 'overpayment')) {
            Schema::table('particular_student', function (Blueprint $table) {
                $table->decimal('overpayment', 15, 2)->default(0)->after('credit');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('particular_student') && Schema::hasColumn('particular_student', 'overpayment')) {
            Schema::table('particular_student', function (Blueprint $table) {
                $table->dropColumn('overpayment');
            });
        }

        if (Schema::hasTable('students') && Schema::hasColumn('students', 'advance_balance')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('advance_balance');
            });
        }
    }
};
