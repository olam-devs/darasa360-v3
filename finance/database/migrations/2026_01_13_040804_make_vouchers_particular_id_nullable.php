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
        Schema::table('vouchers', function (Blueprint $table) {
            // Make particular_id nullable to allow expense vouchers without particular association
            $table->unsignedBigInteger('particular_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vouchers', function (Blueprint $table) {
            // Revert particular_id back to non-nullable
            $table->unsignedBigInteger('particular_id')->nullable(false)->change();
        });
    }
};
