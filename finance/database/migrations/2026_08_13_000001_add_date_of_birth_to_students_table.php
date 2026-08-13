<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('students', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('admission_date');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('students', function (Blueprint $table) {
            $table->dropColumn('date_of_birth');
        });
    }
};
