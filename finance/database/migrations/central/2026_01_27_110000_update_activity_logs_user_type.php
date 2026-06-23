<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // MySQL doesn't allow easy modification of ENUM, so we'll use a workaround
        // Change the column to VARCHAR to support more user types
        Schema::connection('central')->table('activity_logs', function (Blueprint $table) {
            $table->string('user_type', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Leave as VARCHAR since it's more flexible
    }
};
