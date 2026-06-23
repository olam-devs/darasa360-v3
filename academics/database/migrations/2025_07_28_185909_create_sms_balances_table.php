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
    Schema::create('sms_balances', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('school_id');
      $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
      $table->integer('sms_allocated')->default(0);
      $table->integer('sms_used')->default(0);
      $table->timestamps();
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('sms_balances');
  }
};
