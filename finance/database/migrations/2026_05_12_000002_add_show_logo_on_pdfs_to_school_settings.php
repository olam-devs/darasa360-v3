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
        if (! Schema::hasColumn('school_settings', 'show_logo_on_pdfs')) {
            Schema::table('school_settings', function (Blueprint $table) {
                $table->boolean('show_logo_on_pdfs')->default(true)->after('logo_path');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('school_settings') && Schema::hasColumn('school_settings', 'show_logo_on_pdfs')) {
            Schema::table('school_settings', function (Blueprint $table) {
                $table->dropColumn('show_logo_on_pdfs');
            });
        }
    }
};
