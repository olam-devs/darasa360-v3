<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The default tenant connection's database may not exist yet (e.g. a fresh
     * deploy with no schools onboarded), in which case even hasTable() throws
     * when it tries to reach the database. Treat that as "nothing to do" here;
     * real per-school tenant DBs get these columns via MigrateAllSchools /
     * database/migrations/school instead.
     */
    private function tenantReachable(): bool
    {
        try {
            Schema::connection('tenant')->hasTable('report_cards');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if ($this->tenantReachable()) {
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

            // Add photo field to students table (tenant database)
            if (Schema::connection('tenant')->hasTable('students')) {
                Schema::connection('tenant')->table('students', function (Blueprint $table) {
                    if (!Schema::connection('tenant')->hasColumn('students', 'photo')) {
                        $table->string('photo')->nullable()->after('registration_no');
                    }
                });
            }
        }

        // Add logo field to schools table (main database)
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (!Schema::hasColumn('schools', 'logo')) {
                    $table->string('logo')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->tenantReachable()) {
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

            // Remove photo field from students table
            if (Schema::connection('tenant')->hasTable('students')) {
                Schema::connection('tenant')->table('students', function (Blueprint $table) {
                    if (Schema::connection('tenant')->hasColumn('students', 'photo')) {
                        $table->dropColumn('photo');
                    }
                });
            }
        }

        // Remove logo field from schools table
        if (Schema::hasTable('schools')) {
            Schema::table('schools', function (Blueprint $table) {
                if (Schema::hasColumn('schools', 'logo')) {
                    $table->dropColumn('logo');
                }
            });
        }
    }
};
