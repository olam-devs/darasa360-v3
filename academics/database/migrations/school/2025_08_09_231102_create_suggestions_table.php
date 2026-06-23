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
    Schema::create('suggestions', function (Blueprint $table) {
      $table->id();

      // Sender of the suggestion
      $table->unsignedBigInteger('sender_id');

      // Destination role - foreign key to roles table
      $table->unsignedBigInteger('destination_role_id');

      // Class ID - nullable, used only if destination role is class teacher
      $table->unsignedBigInteger('class_id')->nullable();

      // Suggestion content
      $table->text('content');

      $table->timestamps();

      // Foreign key constraints
      $table->foreign('sender_id')->references('id')->on('schoolUsers')->onDelete('cascade');
      $table->foreign('destination_role_id')->references('id')->on('school_roles')->onDelete('cascade');
      $table->foreign('class_id')->references('id')->on('classes')->onDelete('set null');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('suggestions');
  }
};
