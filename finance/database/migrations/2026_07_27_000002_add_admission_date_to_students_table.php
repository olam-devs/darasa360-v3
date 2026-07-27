<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * StudentController::store()/update(), the Student model's $fillable
     * (with a date cast) and three blade views (create/edit/show) have always
     * read and written 'admission_date', but the base tenant migration never
     * created that column (only 'date_of_birth', a different field) - so
     * every student creation/update through the accountant UI 500'd with
     * "Unknown column 'admission_date'".
     */
    public function up(): void
    {
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'admission_date')) {
            Schema::table('students', function (Blueprint $table) {
                $table->date('admission_date')->nullable()->after('date_of_birth');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'admission_date')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('admission_date');
            });
        }
    }
};
