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
        Schema::connection('tenant')->table('schoolUsers', function (Blueprint $table) {
            if (!Schema::connection('tenant')->hasColumn('schoolUsers', 'profile_picture')) {
                $table->string('profile_picture')->nullable();
            }
            if (!Schema::connection('tenant')->hasColumn('schoolUsers', 'address')) {
                $table->text('address')->nullable();
            }
        });

        Schema::connection('tenant')->table('students', function (Blueprint $table) {
            if (!Schema::connection('tenant')->hasColumn('students', 'profile_picture')) {
                $table->string('profile_picture')->nullable();
            }
            if (!Schema::connection('tenant')->hasColumn('students', 'address')) {
                $table->text('address')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('schoolUsers', function (Blueprint $table) {
            if (Schema::connection('tenant')->hasColumn('schoolUsers', 'profile_picture')) {
                $table->dropColumn('profile_picture');
            }
            if (Schema::connection('tenant')->hasColumn('schoolUsers', 'address')) {
                $table->dropColumn('address');
            }
        });

        Schema::connection('tenant')->table('students', function (Blueprint $table) {
            if (Schema::connection('tenant')->hasColumn('students', 'profile_picture')) {
                $table->dropColumn('profile_picture');
            }
            if (Schema::connection('tenant')->hasColumn('students', 'address')) {
                $table->dropColumn('address');
            }
        });
    }
};
