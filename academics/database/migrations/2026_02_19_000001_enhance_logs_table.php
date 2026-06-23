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
        // Check if logs table exists, if not create it first
        if (!Schema::hasTable('logs')) {
            Schema::create('logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('user_name')->nullable();
                $table->string('registration_no')->nullable();
                $table->string('role')->nullable();
                $table->string('school_code')->nullable();
                $table->string('school_name')->nullable();
                $table->string('level')->nullable(); // info, warning, error
                $table->string('method')->nullable(); // GET, POST, etc.
                $table->text('url')->nullable();
                $table->string('ip')->nullable();
                $table->text('message')->nullable();
                $table->text('context')->nullable(); // JSON data
                $table->timestamps();
            });
        }

        // Add new fields to logs table
        Schema::table('logs', function (Blueprint $table) {
            if (!Schema::hasColumn('logs', 'browser')) {
                $table->string('browser')->nullable()->after('ip');
            }
            if (!Schema::hasColumn('logs', 'browser_version')) {
                $table->string('browser_version')->nullable()->after('browser');
            }
            if (!Schema::hasColumn('logs', 'platform')) {
                $table->string('platform')->nullable()->after('browser_version'); // OS
            }
            if (!Schema::hasColumn('logs', 'device_type')) {
                $table->string('device_type')->nullable()->after('platform'); // desktop/mobile/tablet
            }
            if (!Schema::hasColumn('logs', 'country')) {
                $table->string('country')->nullable()->after('device_type');
            }
            if (!Schema::hasColumn('logs', 'city')) {
                $table->string('city')->nullable()->after('country');
            }
            if (!Schema::hasColumn('logs', 'user_agent')) {
                $table->string('user_agent', 500)->nullable()->after('city');
            }
            if (!Schema::hasColumn('logs', 'logged_at')) {
                $table->timestamp('logged_at')->nullable()->after('user_agent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('logs', function (Blueprint $table) {
            if (Schema::hasColumn('logs', 'browser')) {
                $table->dropColumn('browser');
            }
            if (Schema::hasColumn('logs', 'browser_version')) {
                $table->dropColumn('browser_version');
            }
            if (Schema::hasColumn('logs', 'platform')) {
                $table->dropColumn('platform');
            }
            if (Schema::hasColumn('logs', 'device_type')) {
                $table->dropColumn('device_type');
            }
            if (Schema::hasColumn('logs', 'country')) {
                $table->dropColumn('country');
            }
            if (Schema::hasColumn('logs', 'city')) {
                $table->dropColumn('city');
            }
            if (Schema::hasColumn('logs', 'user_agent')) {
                $table->dropColumn('user_agent');
            }
            if (Schema::hasColumn('logs', 'logged_at')) {
                $table->dropColumn('logged_at');
            }
        });
    }
};
