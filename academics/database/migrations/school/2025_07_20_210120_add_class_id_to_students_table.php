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
    Schema::table('students', function (Blueprint $table) {
      $table->unsignedBigInteger('class_id')->nullable()->after('id');

      // Add foreign key constraint to classes table
      $table->foreign('class_id')->references('id')->on('classes')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('students', function (Blueprint $table) {
      //
    });
  }
};
