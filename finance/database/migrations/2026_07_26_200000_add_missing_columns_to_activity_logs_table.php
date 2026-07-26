<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Live's .env consolidates the 'central' connection into the same
     * database as the default connection (CENTRAL_DB_DATABASE=DB_DATABASE),
     * unlike sandbox which has a genuinely separate central database. That
     * meant this table - already created by an earlier migration for a
     * different purpose - never got the school_id/user_type columns that
     * database/migrations/central/2026_01_13_095352_create_central_database_tables.php
     * assumes exist (that whole central/ folder was never run against live -
     * see CLAUDE.md - and shouldn't be, since it also tries to create tables
     * like 'schools' that already exist with a different, actually-used schema).
     * This migration just adds the two columns the super-admin login activity
     * logger actually needs, on whichever connection 'central' resolves to.
     */
    public function up(): void
    {
        Schema::connection('central')->table('activity_logs', function (Blueprint $table) {
            if (!Schema::connection('central')->hasColumn('activity_logs', 'school_id')) {
                $table->unsignedBigInteger('school_id')->nullable()->after('id');
            }
            if (!Schema::connection('central')->hasColumn('activity_logs', 'user_type')) {
                $table->string('user_type', 50)->nullable()->after('school_id');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('activity_logs', function (Blueprint $table) {
            if (Schema::connection('central')->hasColumn('activity_logs', 'user_type')) {
                $table->dropColumn('user_type');
            }
            if (Schema::connection('central')->hasColumn('activity_logs', 'school_id')) {
                $table->dropColumn('school_id');
            }
        });
    }
};
