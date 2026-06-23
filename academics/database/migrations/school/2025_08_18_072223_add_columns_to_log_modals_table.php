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
    Schema::table('logs', function (Blueprint $table) {
      $table->string('user_name')->nullable()->after('user_id');
      $table->string('registration_no')->nullable()->after('user_name');
      $table->string('role')->nullable()->after('registration_no');
      $table->string('school_code')->nullable()->after('role');
      $table->string('school_name')->nullable()->after('school_code');

      // If you want to modify the context column to be JSON (if it isn't already)
      $table->json('context')->change()->nullable();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::table('logs', function (Blueprint $table) {
      //
    });
  }
};
