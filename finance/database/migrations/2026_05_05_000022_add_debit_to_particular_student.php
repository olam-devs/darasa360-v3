<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('particular_student')) {
            return;
        }

        Schema::table('particular_student', function (Blueprint $table) {
            // Some older schemas track only sales + credit. The app expects both debit and credit.
            if (! Schema::hasColumn('particular_student', 'debit')) {
                $table->decimal('debit', 15, 2)->default(0)->after('sales');
            }

            // Ensure pivot remains compatible with advance payment flows.
            if (! Schema::hasColumn('particular_student', 'overpayment')) {
                $table->decimal('overpayment', 15, 2)->default(0)->after('credit');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('particular_student')) {
            return;
        }

        Schema::table('particular_student', function (Blueprint $table) {
            if (Schema::hasColumn('particular_student', 'overpayment')) {
                $table->dropColumn('overpayment');
            }
            if (Schema::hasColumn('particular_student', 'debit')) {
                $table->dropColumn('debit');
            }
        });
    }
};
