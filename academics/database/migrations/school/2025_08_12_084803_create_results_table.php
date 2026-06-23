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
    Schema::create('results', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('class_id');
      $table->unsignedBigInteger('exam_id');
      $table->string('grading_type'); // individual, standard, division
      $table->json('data'); // serialized aggregated results per student (optional)
      $table->timestamp('generated_at');
      $table->unsignedBigInteger('generated_by'); // user_id of academic
      $table->boolean('sent_to_headteacher')->default(false);
      $table->timestamps();

      $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
      $table->foreign('exam_id')->references('id')->on('exams')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('results');
  }
};
