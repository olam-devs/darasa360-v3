<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    /**
     * Atomic per-school, per-role counters for reg-number generation.
     * Guarantees no collision across Finance + Academics.
     */
    public function up(): void
    {
        Schema::connection('platform')->create('platform_reg_sequences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->unsignedTinyInteger('role_digit'); // 1=student,2=teacher/hm,3=accountant,4=owner/admin
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(['school_id', 'role_digit']);
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('platform_reg_sequences');
    }
};
