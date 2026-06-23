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
    Schema::connection('tenant')->create('support_tickets', function (Blueprint $table) {
      $table->id();

      // Ticket details
      $table->string('ticket_number')->unique(); // e.g., TKT-2025-0001
      $table->string('subject');
      $table->unsignedBigInteger('module_id'); // Links to module categories
      $table->string('sub_module')->nullable(); // Specific area within module
      $table->text('description'); // Rich text description

      // User information
      $table->unsignedBigInteger('user_id'); // Person who created the ticket
      $table->unsignedBigInteger('assigned_to')->nullable(); // Support staff assigned

      // Status and Priority
      $table->enum('status', ['open', 'in_progress', 'pending', 'resolved', 'closed'])->default('open');
      $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');

      // Tracking
      $table->timestamp('resolved_at')->nullable();
      $table->unsignedBigInteger('resolved_by')->nullable();
      $table->text('resolution_notes')->nullable();

      $table->timestamps();

      // Foreign keys
      $table->foreign('user_id')->references('id')->on('schoolUsers')->onDelete('cascade');
      $table->foreign('assigned_to')->references('id')->on('schoolUsers')->onDelete('set null');
      $table->foreign('resolved_by')->references('id')->on('schoolUsers')->onDelete('set null');
      $table->foreign('module_id')->references('id')->on('support_modules')->onDelete('cascade');
    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::connection('tenant')->dropIfExists('support_tickets');
  }
};
