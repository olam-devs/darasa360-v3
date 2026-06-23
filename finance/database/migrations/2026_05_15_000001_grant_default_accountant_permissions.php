<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (Schema::hasColumn('users', 'can_edit_history')) {
            DB::table('users')->update(['can_edit_history' => true]);
        }
        if (Schema::hasColumn('users', 'can_view_logs')) {
            DB::table('users')->update(['can_view_logs' => true]);
        }
    }

    public function down(): void
    {
        // Permissions remain; admins can revoke manually in settings.
    }
};
