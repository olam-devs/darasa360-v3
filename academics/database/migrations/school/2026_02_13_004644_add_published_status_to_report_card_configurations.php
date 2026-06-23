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
        // Modify the status ENUM to include 'published'
        DB::connection('tenant')->statement("
            ALTER TABLE `report_card_configurations`
            MODIFY COLUMN `status` ENUM('draft', 'active', 'published', 'archived')
            NOT NULL DEFAULT 'draft'
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to original ENUM values
        DB::connection('tenant')->statement("
            ALTER TABLE `report_card_configurations`
            MODIFY COLUMN `status` ENUM('draft', 'active', 'archived')
            NOT NULL DEFAULT 'draft'
        ");
    }
};
