<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-school dedicated MySQL credentials. This hosting environment's
     * shared DB user (env DB_USERNAME) does not automatically have access
     * to every tenant database — each school's database needs its own
     * dedicated user, created via the DirectAdmin API at provisioning time
     * (mirroring the Finance app's already-working DirectAdminService).
     * Nullable so existing rows keep working via the env() fallback until
     * backfilled.
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (!Schema::hasColumn('schools', 'db_username')) {
                $table->string('db_username')->nullable()->after('database_url');
            }
            if (!Schema::hasColumn('schools', 'db_password')) {
                $table->string('db_password')->nullable()->after('db_username');
            }
        });
    }

    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            if (Schema::hasColumn('schools', 'db_password')) {
                $table->dropColumn('db_password');
            }
            if (Schema::hasColumn('schools', 'db_username')) {
                $table->dropColumn('db_username');
            }
        });
    }
};
