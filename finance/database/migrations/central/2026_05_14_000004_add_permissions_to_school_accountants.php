<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function getConnection()
    {
        return 'central';
    }

    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('school_accountants')) {
            return;
        }
        Schema::connection('central')->table('school_accountants', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('school_accountants', 'can_edit_history')) {
                $table->boolean('can_edit_history')->default(false);
            }
            if (! Schema::connection('central')->hasColumn('school_accountants', 'can_view_logs')) {
                $table->boolean('can_view_logs')->default(false);
            }
        });
    }

    public function down(): void
    {
        if (! Schema::connection('central')->hasTable('school_accountants')) {
            return;
        }
        Schema::connection('central')->table('school_accountants', function (Blueprint $table) {
            foreach (['can_edit_history', 'can_view_logs'] as $col) {
                if (Schema::connection('central')->hasColumn('school_accountants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
