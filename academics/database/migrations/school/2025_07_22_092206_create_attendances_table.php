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
    Schema::create('attendances', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('class_id'); // Class
      $table->unsignedBigInteger('stream_id')->nullable(); // Optional stream
      $table->date('from_date'); // Start of attendance period
      $table->date('to_date');   // End of attendance period
      $table->string('title');   // E.g. "Term 1 Week 1"
      $table->unsignedBigInteger('created_by'); // Staff creating it
      $table->timestamps();

      $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
      $table->foreign('stream_id')->references('id')->on('streams')->onDelete('set null');
      $table->foreign('created_by')->references('id')->on('schoolUsers')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('attendances');
  }
};
