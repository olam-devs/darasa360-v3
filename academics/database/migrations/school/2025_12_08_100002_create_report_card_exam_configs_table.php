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
        Schema::create('report_card_exam_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_card_config_id')->constrained('report_card_configurations')->onDelete('cascade');
            $table->foreignId('exam_id')->constrained()->onDelete('cascade');
            $table->decimal('percentage_weight', 5, 2); // e.g. 20.00, 30.50
            $table->integer('order')->default(0); // Display order
            $table->timestamps();

            // Ensure unique exam per configuration
            $table->unique(['report_card_config_id', 'exam_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_card_exam_configs');
    }
};
