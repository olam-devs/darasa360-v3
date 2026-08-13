<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('tenant')->hasTable('expense_categories')) {
            return;
        }

        if (! Schema::connection('tenant')->hasColumn('expense_categories', 'is_system')) {
            Schema::connection('tenant')->table('expense_categories', function (Blueprint $table) {
                $table->boolean('is_system')->default(false)->after('decision_note');
            });
        }

        // Ensure the protected "Payroll" system category exists.
        // Uses firstOrCreate semantics: safe to re-run.
        $exists = DB::connection('tenant')
            ->table('expense_categories')
            ->where('is_system', true)
            ->exists();

        if (! $exists) {
            DB::connection('tenant')->table('expense_categories')->insert([
                'name'       => 'Payroll',
                'status'     => 'approved',
                'is_system'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::connection('tenant')->hasTable('expense_categories')) {
            return;
        }

        // Remove the seeded system category before dropping the column
        DB::connection('tenant')
            ->table('expense_categories')
            ->where('is_system', true)
            ->delete();

        if (Schema::connection('tenant')->hasColumn('expense_categories', 'is_system')) {
            Schema::connection('tenant')->table('expense_categories', function (Blueprint $table) {
                $table->dropColumn('is_system');
            });
        }
    }
};
