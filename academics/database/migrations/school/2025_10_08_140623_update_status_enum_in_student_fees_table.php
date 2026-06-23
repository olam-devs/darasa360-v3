<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
          Schema::table('student_fees', function (Blueprint $table) {
            // Modify ENUM column to include 'rejected'
            DB::statement("ALTER TABLE student_fees MODIFY status ENUM('paid', 'pending', 'partial', 'rejected') NOT NULL");
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_fees', function (Blueprint $table) {
            //
        });
    }
};
