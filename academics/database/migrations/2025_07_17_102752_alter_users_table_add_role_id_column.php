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
        Schema::table('users', function (Blueprint $table) {
            // Optional: drop old role column if it exists and is not needed
            if (Schema::hasColumn('users', 'role_id')) {
                $table->dropColumn('role_id');
            }

            // Add new role_id column and foreign key
            $table->unsignedBigInteger('role_id')->after('phone_number');
            $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
