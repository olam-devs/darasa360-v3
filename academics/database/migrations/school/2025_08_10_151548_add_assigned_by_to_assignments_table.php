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
    Schema::table('assignments', function (Blueprint $table) {
      $table->unsignedBigInteger('assigned_by')->nullable()->after('id');

      // Assuming 'schoolUsers' table exists in tenant DB
      $table->foreign('assigned_by')
        ->references('id')
        ->on('schoolUsers')
        ->onDelete('set null');
    });
  }


  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('assignments', function (Blueprint $table) {
      //
    });
  }
};
