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
    Schema::table('exams_marks', function (Blueprint $table) {
      $table->boolean('submitted_by_teacher')->default(false)->after('marks');
      $table->boolean('verified_by_academic')->default(false)->after('submitted_by_teacher');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('exam_marks', function (Blueprint $table) {
      //
    });
  }
};
