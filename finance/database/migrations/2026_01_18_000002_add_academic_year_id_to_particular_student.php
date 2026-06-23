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
        if (!Schema::hasColumn('particular_student', 'academic_year_id')) {
            Schema::table('particular_student', function (Blueprint $table) {
                $table->unsignedBigInteger('academic_year_id')->nullable()->after('particular_id');
                $table->foreign('academic_year_id')->references('id')->on('academic_years')->onDelete('set null');
            });
        }

        // Update existing records to use the current academic year
        $currentYear = \DB::table('academic_years')
            ->where('is_current', true)
            ->first();

        if ($currentYear) {
            \DB::table('particular_student')
                ->whereNull('academic_year_id')
                ->update(['academic_year_id' => $currentYear->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('particular_student', function (Blueprint $table) {
            $table->dropForeign(['academic_year_id']);
            $table->dropColumn('academic_year_id');
        });
    }
};
