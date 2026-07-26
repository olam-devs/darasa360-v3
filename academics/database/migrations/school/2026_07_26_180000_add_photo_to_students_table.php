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
        if (Schema::connection('tenant')->hasTable('students') && !Schema::connection('tenant')->hasColumn('students', 'photo')) {
            Schema::connection('tenant')->table('students', function (Blueprint $table) {
                $table->string('photo')->nullable()->after('registration_no');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('tenant')->hasTable('students') && Schema::connection('tenant')->hasColumn('students', 'photo')) {
            Schema::connection('tenant')->table('students', function (Blueprint $table) {
                $table->dropColumn('photo');
            });
        }
    }
};
