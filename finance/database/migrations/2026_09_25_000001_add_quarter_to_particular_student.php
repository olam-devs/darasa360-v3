<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('particular_student', function (Blueprint $table) {
            $table->unsignedTinyInteger('quarter')->nullable()->after('deadline')->comment('1=Q1, 2=Q2, 3=Q3, 4=Q4');
        });

        // Drop old unique constraint and add new one that includes quarter
        // Constraint name may vary; try the known name first then fall back
        try {
            Schema::connection('tenant')->table('particular_student', function (Blueprint $table) {
                $table->dropUnique('particular_student_particular_id_student_id_academic_year_id_unique');
            });
        } catch (\Exception $e) {
            // Constraint may have a different name or already dropped
        }

        Schema::connection('tenant')->table('particular_student', function (Blueprint $table) {
            $table->unique(
                ['particular_id', 'student_id', 'academic_year_id', 'quarter'],
                'ps_unique_part_student_year_quarter'
            );
        });

        // Data migration: assign quarters based on deadline month
        DB::connection('tenant')->table('particular_student')
            ->whereNull('quarter')
            ->whereRaw('MONTH(deadline) = 1')
            ->update(['quarter' => 1]);

        DB::connection('tenant')->table('particular_student')
            ->whereNull('quarter')
            ->whereRaw('MONTH(deadline) = 2')
            ->update(['quarter' => 1]);

        DB::connection('tenant')->table('particular_student')
            ->whereNull('quarter')
            ->whereRaw('MONTH(deadline) = 3')
            ->update(['quarter' => 1]);

        DB::connection('tenant')->table('particular_student')
            ->whereNull('quarter')
            ->whereRaw('MONTH(deadline) IN (4, 5, 6)')
            ->update(['quarter' => 2]);

        DB::connection('tenant')->table('particular_student')
            ->whereNull('quarter')
            ->whereRaw('MONTH(deadline) IN (7, 8, 9)')
            ->update(['quarter' => 3]);

        DB::connection('tenant')->table('particular_student')
            ->whereNull('quarter')
            ->whereRaw('MONTH(deadline) IN (10, 11, 12)')
            ->update(['quarter' => 4]);
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('particular_student', function (Blueprint $table) {
            try {
                $table->dropUnique('ps_unique_part_student_year_quarter');
            } catch (\Exception $e) {
                //
            }
            $table->unique(['particular_id', 'student_id', 'academic_year_id']);
            $table->dropColumn('quarter');
        });
    }
};
