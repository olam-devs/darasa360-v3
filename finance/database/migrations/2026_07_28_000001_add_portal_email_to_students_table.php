<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parents log into the Finance parent portal with a generated,
     * per-school-unique "portal email" (firstname[N]@{school-domain}.com)
     * instead of the student_reg_no, which an accountant can set to
     * anything and which a parent could otherwise use to probe for other
     * students' reg numbers.
     */
    public function up(): void
    {
        if (Schema::hasTable('students') && ! Schema::hasColumn('students', 'portal_email')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('portal_email')->nullable()->unique()->after('student_reg_no');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('students') && Schema::hasColumn('students', 'portal_email')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('portal_email');
            });
        }
    }
};
