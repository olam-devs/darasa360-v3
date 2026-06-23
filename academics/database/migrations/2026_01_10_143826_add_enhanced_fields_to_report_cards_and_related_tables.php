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
        // Add enhanced fields to report_cards table
        if (Schema::connection('tenant')->hasTable('report_cards')) {
            Schema::connection('tenant')->table('report_cards', function (Blueprint $table) {
                if (!Schema::connection('tenant')->hasColumn('report_cards', 'pdf_generated')) {
                    $table->boolean('pdf_generated')->default(false)->after('status');
                }
                if (!Schema::connection('tenant')->hasColumn('report_cards', 'pdf_path')) {
                    $table->string('pdf_path', 500)->nullable()->after('pdf_generated');
                }
            });
        }

        // Add logo field to schools table (main database)
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (!Schema::hasColumn('schools', 'logo')) {
                    $table->string('logo')->nullable();
                }
            });
        }

        // Add photo field to students table (tenant database)
        if (Schema::connection('tenant')->hasTable('students')) {
            Schema::connection('tenant')->table('students', function (Blueprint $table) {
                if (!Schema::connection('tenant')->hasColumn('students', 'photo')) {
                    $table->string('photo')->nullable()->after('registration_no');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove enhanced fields from report_cards table
        if (Schema::connection('tenant')->hasTable('report_cards')) {
            Schema::connection('tenant')->table('report_cards', function (Blueprint $table) {
                if (Schema::connection('tenant')->hasColumn('report_cards', 'pdf_generated')) {
                    $table->dropColumn('pdf_generated');
                }
                if (Schema::connection('tenant')->hasColumn('report_cards', 'pdf_path')) {
                    $table->dropColumn('pdf_path');
                }
            });
        }

        // Remove logo field from schools table
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (Schema::hasColumn('schools', 'logo')) {
                    $table->dropColumn('logo');
                }
            });
        }

        // Remove photo field from students table
        if (Schema::connection('tenant')->hasTable('students')) {
            Schema::connection('tenant')->table('students', function (Blueprint $table) {
                if (Schema::connection('tenant')->hasColumn('students', 'photo')) {
                    $table->dropColumn('photo');
                }
            });
        }
    }
};
