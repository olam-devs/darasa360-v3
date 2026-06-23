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
        if (!Schema::connection('tenant')->hasTable('school_calendar')) {
            Schema::connection('tenant')->create('school_calendar', function (Blueprint $table) {
                $table->id();
                $table->string('academic_year'); // e.g., 2024/2025
                $table->date('start_date');
                $table->date('end_date');
                $table->integer('term_count')->default(3); // Number of terms/semesters
                $table->boolean('is_active')->default(false);
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('school_calendar');
    }
};
