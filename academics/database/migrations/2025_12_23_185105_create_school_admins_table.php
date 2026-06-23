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
        Schema::create('school_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('schools')->onDelete('cascade');
            $table->foreignId('system_admin_id')->constrained('users')->onDelete('cascade');
            $table->enum('admin_type', ['system_admin'])->default('system_admin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure unique assignment
            $table->unique(['school_id', 'system_admin_id', 'admin_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('school_admins');
    }
};
