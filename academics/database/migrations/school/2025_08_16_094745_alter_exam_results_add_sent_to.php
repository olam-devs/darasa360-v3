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
    Schema::connection('tenant')->table('exam_results', function (Blueprint $table) {
      $table->boolean('is_verified')->default(false)->after('grading_type');
      $table->json('sent_to')->nullable()->after('is_verified');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    //
  }
};
