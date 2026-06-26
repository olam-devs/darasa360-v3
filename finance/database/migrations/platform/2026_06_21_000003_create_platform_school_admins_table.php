<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('platform_school_admins', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('username')->nullable();
            $table->string('password');
            $table->string('role'); // accountant|headmaster|owner|teacher|school_admin
            $table->string('systems')->default('finance'); // finance|academics|both
            $table->string('reg_no')->nullable(); // S{code}{role}{seq} for staff
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('school_id');
            $table->index(['email', 'school_id']);
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('platform_school_admins');
    }
};
