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
    if (!Schema::hasTable('notes')) {
      Schema::create('notes', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('class_id');
        $table->unsignedBigInteger('subject_id');
        $table->string('title');
        $table->text('description')->nullable();
        $table->string('file_path');
        $table->timestamp('uploaded_at');
        $table->timestamps();

        $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
        $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
      });
    }
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    if (Schema::hasTable('notes')) {
      Schema::dropIfExists('notes');
    }
  }
};
