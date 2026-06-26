<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    /**
     * Master student registry. student_reg_no is the universal ID shared
     * across both Finance and Academics tenant databases.
     */
    public function up(): void
    {
        Schema::connection('platform')->create('platform_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('student_reg_no')->unique(); // S{code3}{role1}{seq4}
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('parent_name')->nullable();
            $table->string('parent_phone')->nullable();
            $table->string('parent_email')->nullable();
            $table->unsignedBigInteger('platform_class_id')->nullable();
            $table->string('status')->default('active');

            $table->boolean('synced_finance')->default(false);
            $table->boolean('synced_academics')->default(false);
            $table->timestamps();

            $table->index('school_id');
            $table->index('platform_class_id');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('platform_students');
    }
};
