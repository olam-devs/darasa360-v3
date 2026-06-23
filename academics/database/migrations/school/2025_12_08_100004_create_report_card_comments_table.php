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
        Schema::create('report_card_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_config_id')->constrained('report_card_configurations')->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->enum('stakeholder_type', [
                'accountant',
                'discipline',
                'class_teacher',
                'subject_teacher',
                'academic'
            ]);
            $table->foreignId('subject_id')->nullable()->constrained()->onDelete('cascade'); // For subject teachers
            $table->text('comment');
            $table->foreignId('commented_by')->nullable()->constrained('schoolUsers')->onDelete('set null'); // User who wrote comment
            $table->timestamps();

            // Ensure unique comment per student per stakeholder type (except subject teachers can have multiple)
            $table->index(['report_card_config_id', 'student_id', 'stakeholder_type'], 'rc_comments_config_student_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_card_comments');
    }
};
