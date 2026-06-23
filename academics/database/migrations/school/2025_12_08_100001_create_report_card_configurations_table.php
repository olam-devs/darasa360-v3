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
        Schema::create('report_card_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Term 1 Report Card 2024"
            $table->foreignId('class_id')->constrained()->onDelete('cascade');
            $table->string('term'); // e.g. "Term 1", "Term 2", "Annual"
            $table->string('academic_year'); // e.g. "2024/2025"
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_card_configurations');
    }
};
