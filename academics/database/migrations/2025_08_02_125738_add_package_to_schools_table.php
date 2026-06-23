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
    Schema::table('schools', function (Blueprint $table) {
      $table->enum('package', ['core', 'standard', 'premium'])->default('premium')->after('database_url');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('schools', function (Blueprint $table) {
      //
    });
  }
};
