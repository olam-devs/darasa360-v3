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
    Schema::create('grades', function (Blueprint $table) {
      $table->id();
      $table->string('name'); // e.g., A, B, 1, 2
      $table->enum('type', ['division', 'standard']); // grading type
      $table->integer('min_mark'); // inclusive lower bound
      $table->integer('max_mark'); // inclusive upper bound
      $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
