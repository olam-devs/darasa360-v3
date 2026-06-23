<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('scholarships')) {
            return;
        }

        Schema::table('scholarships', function (Blueprint $table) {
            // Newer scholarship schema expects these columns to exist.
            if (! Schema::hasColumn('scholarships', 'student_id')) {
                $table->foreignId('student_id')->nullable()->after('id')->constrained('students')->nullOnDelete();
            }
            if (! Schema::hasColumn('scholarships', 'particular_id')) {
                $table->foreignId('particular_id')->nullable()->after('student_id')->constrained('particulars')->nullOnDelete();
            }
            if (! Schema::hasColumn('scholarships', 'academic_year_id')) {
                $table->foreignId('academic_year_id')->nullable()->after('particular_id')->constrained('academic_years')->nullOnDelete();
            }

            if (! Schema::hasColumn('scholarships', 'original_amount')) {
                $table->decimal('original_amount', 15, 2)->default(0)->after('academic_year_id');
            }
            if (! Schema::hasColumn('scholarships', 'forgiven_amount')) {
                $table->decimal('forgiven_amount', 15, 2)->default(0)->after('original_amount');
            }
            if (! Schema::hasColumn('scholarships', 'remaining_amount')) {
                $table->decimal('remaining_amount', 15, 2)->default(0)->after('forgiven_amount');
            }

            if (! Schema::hasColumn('scholarships', 'scholarship_type')) {
                $table->enum('scholarship_type', ['full', 'partial'])->nullable()->after('remaining_amount');
            }
            if (! Schema::hasColumn('scholarships', 'scholarship_name')) {
                $table->string('scholarship_name')->nullable()->after('scholarship_type');
            }
            if (! Schema::hasColumn('scholarships', 'notes')) {
                $table->text('notes')->nullable()->after('scholarship_name');
            }
            if (! Schema::hasColumn('scholarships', 'applied_date')) {
                $table->date('applied_date')->nullable()->after('notes');
            }

            if (! Schema::hasColumn('scholarships', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('applied_date');
            }
        });

        // Add the uniqueness constraint expected by newer logic, if missing.
        // (Avoid Doctrine/DBAL dependency; use SHOW INDEX instead.)
        $hasIndex = false;
        try {
            $rows = DB::select("SHOW INDEX FROM `scholarships` WHERE Key_name = 'sch_unique'");
            $hasIndex = ! empty($rows);
        } catch (Throwable $e) {
            $hasIndex = false;
        }

        if (! $hasIndex) {
            Schema::table('scholarships', function (Blueprint $table) {
                // One scholarship record per student+particular+academic year
                $table->unique(['student_id', 'particular_id', 'academic_year_id'], 'sch_unique');
            });
        }
    }

    public function down(): void
    {
        // Non-destructive: do not drop columns (this migration is for alignment).
    }
};
