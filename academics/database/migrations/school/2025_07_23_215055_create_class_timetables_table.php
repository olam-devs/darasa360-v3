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
    Schema::create('class_timetables', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('class_id');
      $table->string('day_of_week'); // e.g. Monday, Tuesday
      $table->time('start_time');
      $table->time('end_time');
      $table->unsignedBigInteger('subject_id');
      $table->unsignedBigInteger('teacher_id')->nullable();
      $table->timestamps();

      $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
      $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
      $table->foreign('teacher_id')->references('id')->on('teachers')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('class_timetables');
  }
};
