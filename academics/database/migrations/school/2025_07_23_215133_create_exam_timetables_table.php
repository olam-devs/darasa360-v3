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
    Schema::create('exam_timetables', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('class_id');
      $table->string('exam_name');
      $table->date('exam_date');
      $table->time('start_time');
      $table->time('end_time');
      $table->unsignedBigInteger('subject_id');
      $table->timestamps();

      $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
      $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('exam_timetables');
  }
};
