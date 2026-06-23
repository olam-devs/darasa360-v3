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
        if (!Schema::hasTable('subject_performance')) {
            Schema::create('subject_performance', function (Blueprint $table) {
                $table->id();
                $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
                $table->foreignId('class_id')->constrained('classes')->onDelete('cascade');
                $table->foreignId('exam_id')->nullable()->constrained('exams')->onDelete('cascade');
                $table->decimal('average_score', 5, 2)->nullable();
                $table->integer('total_students')->default(0);
                $table->integer('students_passed')->default(0);
                $table->integer('students_failed')->default(0);
                $table->decimal('pass_rate', 5, 2)->nullable();
                $table->integer('class_rank')->nullable(); // Position within the class
                $table->integer('school_rank')->nullable(); // Position within the school
                $table->decimal('highest_score', 5, 2)->nullable();
                $table->decimal('lowest_score', 5, 2)->nullable();
                $table->string('grade_distribution')->nullable(); // JSON: {A: 5, B: 10, C: 15, etc}
                $table->timestamps();

                // Index for faster queries
                $table->index(['subject_id', 'class_id']);
                $table->index(['exam_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subject_performance');
    }
};
