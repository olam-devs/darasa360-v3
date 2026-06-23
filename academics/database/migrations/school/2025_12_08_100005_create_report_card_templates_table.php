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
        Schema::create('report_card_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "Standard Report Card", "Detailed Report Card"
            $table->text('header_content')->nullable(); // HTML/Text for header with placeholders
            $table->text('footer_content')->nullable(); // HTML/Text for footer
            $table->json('layout_settings')->nullable(); // JSON for layout customization
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_card_templates');
    }
};
