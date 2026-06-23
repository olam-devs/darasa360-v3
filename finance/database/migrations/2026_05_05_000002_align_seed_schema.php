<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // academic_years: add is_active if missing (base migration omits it)
        if (Schema::hasTable('academic_years') && ! Schema::hasColumn('academic_years', 'is_active')) {
            Schema::table('academic_years', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('is_current');
            });
        }

        // school_classes: add code/level/capacity to support setup scripts + UI
        if (Schema::hasTable('school_classes')) {
            Schema::table('school_classes', function (Blueprint $table) {
                if (! Schema::hasColumn('school_classes', 'code')) {
                    $table->string('code')->nullable()->after('name');
                }
                if (! Schema::hasColumn('school_classes', 'level')) {
                    $table->unsignedInteger('level')->nullable()->after('code');
                }
                if (! Schema::hasColumn('school_classes', 'capacity')) {
                    $table->unsignedInteger('capacity')->nullable()->after('level');
                }
            });
        }

        // particulars: add book_ids/class_names JSON columns used by app
        if (Schema::hasTable('particulars')) {
            Schema::table('particulars', function (Blueprint $table) {
                if (! Schema::hasColumn('particulars', 'book_ids')) {
                    $table->json('book_ids')->nullable()->after('name');
                }
                if (! Schema::hasColumn('particulars', 'class_names')) {
                    $table->json('class_names')->nullable()->after('book_ids');
                }
            });
        }
    }

    public function down(): void
    {
        // No-op: these columns are now relied upon by the application.
    }
};
