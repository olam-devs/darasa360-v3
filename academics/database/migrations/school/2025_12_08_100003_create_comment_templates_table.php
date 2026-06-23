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
        Schema::create('comment_templates', function (Blueprint $table) {
            $table->id();
            $table->enum('stakeholder_type', [
                'accountant',
                'discipline',
                'class_teacher',
                'subject_teacher',
                'academic'
            ]);
            $table->string('name'); // Template name
            $table->text('content'); // Default comment template
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comment_templates');
    }
};
