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
    Schema::table('classes', function (Blueprint $table) {
      $table->unsignedBigInteger('class_teacher_id')->nullable()->after('id');
      $table->foreign('class_teacher_id')->references('id')->on('schoolUsers')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('classes', function (Blueprint $table) {
      //
    });
  }
};
