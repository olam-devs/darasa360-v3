<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BankApiSetting::$fillable includes use_simulation/webhook_secret,
     * but the original 0001_01_01 schema never had them (it has an
     * unused `account_id`/`settings` JSON column from an earlier design
     * instead) - every settings save 500'd with "Unknown column
     * 'use_simulation'".
     */
    public function up(): void
    {
        if (! Schema::hasTable('bank_api_settings')) {
            return;
        }

        Schema::table('bank_api_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_api_settings', 'use_simulation')) {
                $table->boolean('use_simulation')->default(true)->after('is_active');
            }
            if (! Schema::hasColumn('bank_api_settings', 'webhook_secret')) {
                $table->string('webhook_secret')->nullable()->after('use_simulation');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bank_api_settings')) {
            return;
        }

        Schema::table('bank_api_settings', function (Blueprint $table) {
            foreach (['webhook_secret', 'use_simulation'] as $col) {
                if (Schema::hasColumn('bank_api_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
