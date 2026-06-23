<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('school_settings')) {
            return;
        }

        Schema::table('school_settings', function (Blueprint $table) {
            if (! Schema::hasColumn('school_settings', 'office_whatsapp_number')) {
                $table->string('office_whatsapp_number', 32)->nullable()->after('phone');
            }
            if (! Schema::hasColumn('school_settings', 'parent_messenger_pin')) {
                $table->string('parent_messenger_pin', 64)->nullable()->after('office_whatsapp_number');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('school_settings')) {
            return;
        }

        Schema::table('school_settings', function (Blueprint $table) {
            if (Schema::hasColumn('school_settings', 'parent_messenger_pin')) {
                $table->dropColumn('parent_messenger_pin');
            }
            if (Schema::hasColumn('school_settings', 'office_whatsapp_number')) {
                $table->dropColumn('office_whatsapp_number');
            }
        });
    }
};
