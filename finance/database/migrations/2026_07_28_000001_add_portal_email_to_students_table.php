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
                // 191, not 255: this host's default_storage_engine is MyISAM
                // (max key length 1000 bytes) and utf8mb4 uses up to 4 bytes/char,
                // so a unique index on varchar(255) always exceeds it (255*4=1020).
                // 191 is the same length Laravel itself historically defaulted
                // string columns to for exactly this reason (191*4=764).
                $table->string('portal_email', 191)->nullable()->unique()->after('student_reg_no');
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
