<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('control_numbers', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['student', 'school']);
            $table->unsignedBigInteger('student_id')->nullable();
            $table->string('control_number')->unique();
            $table->string('payment_channel')->default('mpesa');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('student_id')->references('id')->on('students')->onDelete('cascade');
            $table->index(['type', 'student_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('control_numbers');
    }
};