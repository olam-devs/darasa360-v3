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
        if (!Schema::hasTable('scholarships')) {
            Schema::create('scholarships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
                $table->foreignId('particular_id')->constrained('particulars')->onDelete('cascade');
                $table->foreignId('academic_year_id')->nullable()->constrained('academic_years')->onDelete('set null');
                $table->decimal('original_amount', 15, 2); // Original fee amount
                $table->decimal('forgiven_amount', 15, 2); // Amount forgiven/scholarship
                $table->decimal('remaining_amount', 15, 2); // Amount student still needs to pay
                $table->enum('scholarship_type', ['full', 'partial']); // Full or partial scholarship
                $table->string('scholarship_name')->nullable(); // e.g., "Government Scholarship", "School Bursary"
                $table->text('notes')->nullable(); // Additional notes about the scholarship
                $table->date('applied_date'); // Date scholarship was applied
                $table->foreignId('applied_by')->nullable()->constrained('users')->onDelete('set null');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                // Unique constraint: one scholarship per student per particular per academic year
                $table->unique(['student_id', 'particular_id', 'academic_year_id'], 'student_particular_year_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarships');
    }
};
