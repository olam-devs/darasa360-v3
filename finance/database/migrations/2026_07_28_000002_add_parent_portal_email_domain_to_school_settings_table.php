<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Admin-set short name used to build each student's portal_email
     * (e.g. "olam" -> "jackson@olam.com").
     */
    public function up(): void
    {
        if (Schema::hasTable('school_settings') && ! Schema::hasColumn('school_settings', 'parent_portal_email_domain')) {
            Schema::table('school_settings', function (Blueprint $table) {
                $table->string('parent_portal_email_domain')->nullable()->after('parent_messenger_pin');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('school_settings') && Schema::hasColumn('school_settings', 'parent_portal_email_domain')) {
            Schema::table('school_settings', function (Blueprint $table) {
                $table->dropColumn('parent_portal_email_domain');
            });
        }
    }
};
