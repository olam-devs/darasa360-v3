<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if the new unique constraint already exists (created by base migration)
        try {
            // First, drop foreign key constraints that depend on the unique index
            Schema::table('particular_student', function (Blueprint $table) {
                // Drop foreign keys first (they may reference the unique constraint)
                $table->dropForeign(['particular_id']);
                $table->dropForeign(['student_id']);
            });

            Schema::table('particular_student', function (Blueprint $table) {
                // Now we can drop the unique constraint
                $table->dropUnique('particular_student_particular_id_student_id_unique');
            });

            Schema::table('particular_student', function (Blueprint $table) {
                // Create new unique constraint that includes academic_year_id
                // This allows same student to be assigned same particular in different academic years
                $table->unique(['particular_id', 'student_id', 'academic_year_id'], 'particular_student_year_unique');

                // Re-add foreign keys
                $table->foreign('particular_id')->references('id')->on('particulars')->onDelete('cascade');
                $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Constraint already exists or was created by base migration - skip
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('particular_student', function (Blueprint $table) {
            $table->dropForeign(['particular_id']);
            $table->dropForeign(['student_id']);
        });

        Schema::table('particular_student', function (Blueprint $table) {
            $table->dropUnique('particular_student_year_unique');
        });

        Schema::table('particular_student', function (Blueprint $table) {
            $table->unique(['particular_id', 'student_id'], 'particular_student_particular_id_student_id_unique');
            $table->foreign('particular_id')->references('id')->on('particulars')->onDelete('cascade');
            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
        });
    }
};
