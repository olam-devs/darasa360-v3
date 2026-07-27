<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * school_classes.level was created as unsignedInteger, but the class
     * create/edit form (Level: Pre/Primary/Secondary/Advanced) has always sent
     * a string - every class creation/update failed with "Incorrect integer
     * value" until this was caught. The column exists purely for display
     * (see superadmin/platform/classes.blade.php), nothing sorts/filters on it
     * as a number, so widening it to a string is safe.
     */
    public function up(): void
    {
        if (Schema::hasColumn('school_classes', 'level')) {
            DB::statement('ALTER TABLE school_classes MODIFY level VARCHAR(50) NULL');
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('school_classes', 'level')) {
            DB::statement('ALTER TABLE school_classes MODIFY level INT UNSIGNED NULL');
        }
    }
};
