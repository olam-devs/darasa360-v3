<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    /**
     * Per-user cross-system access grants for owners & headmasters
     * (parents are granted at school level via platform_schools.parent_cross_access).
     */
    public function up(): void
    {
        Schema::connection('platform')->create('platform_cross_access', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('user_ref');        // reg_no or platform_school_admins id reference
            $table->string('role');            // owner|headmaster
            $table->string('target_system');   // finance|academics — the system they may jump INTO
            $table->string('level')->default('readonly');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['school_id', 'user_ref']);
            $table->unique(['school_id', 'user_ref', 'target_system'], 'cross_access_unique');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('platform_cross_access');
    }
};
