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
    Schema::connection('tenant')->create('support_ticket_comments', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('ticket_id');
      $table->unsignedBigInteger('user_id');
      $table->text('comment');
      $table->boolean('is_internal')->default(false); // Internal notes only visible to staff
      $table->timestamps();

      $table->foreign('ticket_id')->references('id')->on('support_tickets')->onDelete('cascade');
      $table->foreign('user_id')->references('id')->on('schoolUsers')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::connection('tenant')->dropIfExists('support_ticket_comments');
  }
};
