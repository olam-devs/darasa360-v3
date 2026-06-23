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
        Schema::table('suggestions', function (Blueprint $table) {
            $table->text('reply')->nullable()->after('content');
            $table->unsignedBigInteger('replied_by')->nullable()->after('reply');
            $table->timestamp('replied_at')->nullable()->after('replied_by');
            $table->foreign('replied_by')->references('id')->on('schoolUsers')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('suggestions', function (Blueprint $table) {
            //
        });
    }
};
