<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vouchers')) {
            return;
        }
        Schema::table('vouchers', function (Blueprint $table) {
            if (! Schema::hasColumn('vouchers', 'voided_at')) {
                $table->timestamp('voided_at')->nullable()->after('notes');
            }
            if (! Schema::hasColumn('vouchers', 'voided_by')) {
                $table->unsignedBigInteger('voided_by')->nullable()->after('voided_at');
            }
            if (! Schema::hasColumn('vouchers', 'void_reason')) {
                $table->string('void_reason', 500)->nullable()->after('voided_by');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vouchers')) {
            return;
        }
        Schema::table('vouchers', function (Blueprint $table) {
            foreach (['void_reason', 'voided_by', 'voided_at'] as $col) {
                if (Schema::hasColumn('vouchers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
