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
    Schema::connection('tenant')->create('exam_results', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('class_id');
      $table->unsignedBigInteger('exam_id');
      $table->unsignedBigInteger('student_id');
      $table->string('student_name');
      $table->decimal('total_marks', 8, 2)->nullable();
      $table->decimal('average_marks', 8, 2)->nullable();
      $table->string('final_grade')->nullable();
      $table->enum('grading_type', ['individual', 'standard', 'division']);
      $table->json('subjects');
      $table->boolean('sent_to_headteacher')->default(false);
      $table->boolean('sent_to_class_teacher')->default(false);
      $table->boolean('sent_to_student')->default(false);
      $table->timestamps();

      $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
      $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
      $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');

      $table->index(['class_id', 'exam_id']);
      $table->index(['student_id']);
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('exam_results');
  }
};
