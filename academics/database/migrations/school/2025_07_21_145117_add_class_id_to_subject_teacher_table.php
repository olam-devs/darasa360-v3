<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('subject_teacher', function (Blueprint $table) {
            // Add class_id column (ensure it matches the type of class_models.id)
            $table->unsignedBigInteger('class_id')->after('subject_id')->nullable();

            // Add the foreign key constraint
            $table->foreign('class_id')
                ->references('id')
                ->on('classes')
                ->onDelete('cascade');
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subject_teacher', function (Blueprint $table) {
            //
        });
    }
};
