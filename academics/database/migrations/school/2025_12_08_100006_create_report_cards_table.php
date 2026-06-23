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
        Schema::create('report_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_config_id')->constrained('report_card_configurations')->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('template_id')->nullable()->constrained('report_card_templates')->onDelete('set null');
            $table->decimal('total_marks', 8, 2)->nullable();
            $table->decimal('average_marks', 8, 2)->nullable();
            $table->string('overall_grade')->nullable();
            $table->integer('class_position')->nullable();
            $table->integer('total_students_in_class')->nullable();
            $table->json('exam_breakdown')->nullable(); // Store individual exam scores
            $table->json('subject_performance')->nullable(); // Store subject-wise performance
            $table->enum('status', ['draft', 'finalized', 'published', 'sent'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Ensure unique report card per student per configuration
            $table->unique(['report_card_config_id', 'student_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_cards');
    }
};
