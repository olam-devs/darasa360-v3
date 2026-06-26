<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    /**
     * Single-use, short-lived tokens for seamless cross-system jumps.
     * The opaque token is the only thing passed in the URL.
     */
    public function up(): void
    {
        Schema::connection('platform')->create('platform_handoff_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('token', 128)->unique(); // sha256 hash of the random secret
            $table->unsignedBigInteger('school_id');
            $table->string('user_ref');          // identifies who is jumping
            $table->string('role');              // headmaster|owner|parent|accountant
            $table->string('source_system');     // finance|academics
            $table->string('target_system');     // finance|academics
            $table->json('payload')->nullable(); // e.g. student reg_no(s) for parents
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('platform_handoff_tokens');
    }
};
