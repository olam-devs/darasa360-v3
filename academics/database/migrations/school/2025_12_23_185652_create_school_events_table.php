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
        if (!Schema::connection('tenant')->hasTable('school_events')) {
            Schema::connection('tenant')->create('school_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('calendar_id')->nullable()->constrained('school_calendar')->onDelete('cascade');
                $table->string('title');
                $table->text('description')->nullable();
                $table->enum('event_type', ['exam', 'meeting', 'holiday', 'sport', 'cultural', 'academic', 'other'])->default('other');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->string('location')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->foreign('created_by')->references('id')->on('schoolUsers')->onDelete('cascade');
                $table->boolean('notify_staff')->default(false);
                $table->boolean('notify_students')->default(false);
                $table->boolean('notify_parents')->default(false);
                $table->integer('reminder_days_before')->nullable(); // Days before to send reminder
                $table->boolean('is_recurring')->default(false);
                $table->string('recurrence_pattern')->nullable(); // weekly, monthly, yearly
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('school_events');
    }
};
