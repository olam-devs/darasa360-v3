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
        Schema::create('classroom_subject', function (Blueprint $table) {
          $table->id();
          $table->unsignedBigInteger('classroom_id');
          $table->unsignedBigInteger('subject_id');
          $table->timestamps();

          $table->unique(['classroom_id', 'subject_id']);

          $table->foreign('classroom_id')->references('id')->on('classes')->onDelete('cascade');
          $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classroom_subject');
    }
};
