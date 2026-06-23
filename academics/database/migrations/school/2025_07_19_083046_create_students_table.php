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
      Schema::create('students', function (Blueprint $table) {
        $table->id();

        // If students are also users
        $table->foreignId('user_id')->nullable()->constrained('schoolUsers')->onDelete('set null');

        $table->string('registration_no')->unique(); // e.g., 30XX001
        $table->string('first_name');
        $table->string('middle_name')->nullable();
        $table->string('last_name');
        $table->enum('gender', ['Male', 'Female']);
        $table->date('date_of_birth')->nullable();

        $table->string('phone_number')->nullable();
        $table->string('email')->nullable();

        $table->foreignId('stream_id')->constrained('streams')->onDelete('cascade'); // connects to stream → class

        $table->text('address')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
