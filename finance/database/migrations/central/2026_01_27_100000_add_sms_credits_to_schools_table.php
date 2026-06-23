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
        Schema::connection('central')->table('schools', function (Blueprint $table) {
            // SMS Credit System
            $table->integer('sms_credits_assigned')->default(0)->after('max_students');
            $table->integer('sms_credits_used')->default(0)->after('sms_credits_assigned');
        });

        // Add user_name to activity_logs for easier tracking of which accountant did what
        Schema::connection('central')->table('activity_logs', function (Blueprint $table) {
            $table->string('user_name')->nullable()->after('user_id');
        });

        // Add is_primary to school_accountants to track primary accountant
        Schema::connection('central')->table('school_accountants', function (Blueprint $table) {
            $table->boolean('is_primary')->default(false)->after('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('central')->table('schools', function (Blueprint $table) {
            $table->dropColumn(['sms_credits_assigned', 'sms_credits_used']);
        });

        Schema::connection('central')->table('activity_logs', function (Blueprint $table) {
            $table->dropColumn('user_name');
        });

        Schema::connection('central')->table('school_accountants', function (Blueprint $table) {
            $table->dropColumn('is_primary');
        });
    }
};
