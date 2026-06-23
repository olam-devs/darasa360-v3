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
            $table->string('student_reg_no', 20)->unique(); // S{code3}{role1}{seq4}
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->string('gender', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('parent_name', 150)->nullable();
            $table->string('parent_phone', 30)->nullable();
            $table->string('parent_email', 191)->nullable();
            $table->unsignedBigInteger('platform_class_id')->nullable();
            $table->string('status', 50)->default('active');

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
