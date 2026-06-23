<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('tenant')->table('exams_marks', function (Blueprint $table) {
            if (!Schema::connection('tenant')->hasColumn('exams_marks', 'teacher_comment')) {
                $table->text('teacher_comment')->nullable()->after('marks');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('exams_marks', function (Blueprint $table) {
            $table->dropColumn('teacher_comment');
        });
    }
};
