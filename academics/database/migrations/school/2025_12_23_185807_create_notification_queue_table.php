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
        if (!Schema::connection('tenant')->hasTable('notification_queue')) {
            Schema::connection('tenant')->create('notification_queue', function (Blueprint $table) {
                $table->id();
                $table->enum('type', ['sms', 'email', 'both'])->default('sms');
                $table->string('recipient_phone')->nullable();
                $table->string('recipient_email')->nullable();
                $table->string('recipient_name')->nullable();
                $table->string('subject')->nullable(); // For emails
                $table->text('message');
                $table->enum('status', ['pending', 'sent', 'failed', 'cancelled'])->default('pending');
                $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
                $table->string('notification_category')->nullable(); // ticket, report_card, event, etc
                $table->foreignId('related_id')->nullable(); // ID of related record (ticket, event, etc)
                $table->string('related_type')->nullable(); // ticket, event, report_card, etc
                $table->dateTime('scheduled_at')->nullable(); // For scheduled notifications
                $table->dateTime('sent_at')->nullable();
                $table->text('error_message')->nullable();
                $table->integer('retry_count')->default(0);
                $table->timestamps();

                // Indexes for performance
                $table->index(['status', 'scheduled_at']);
                $table->index(['type', 'status']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('notification_queue');
    }
};
