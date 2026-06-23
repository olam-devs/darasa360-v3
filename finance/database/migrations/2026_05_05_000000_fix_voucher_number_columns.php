<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vouchers')) {
            return;
        }

        $hasVoucherNo = Schema::hasColumn('vouchers', 'voucher_no');
        $hasVoucherNumber = Schema::hasColumn('vouchers', 'voucher_number');

        // Ensure both columns exist (legacy + current codepaths).
        if ($hasVoucherNo && ! $hasVoucherNumber) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->string('voucher_number', 191)->nullable()->after('voucher_no');
            });

            DB::table('vouchers')->whereNull('voucher_number')->update([
                'voucher_number' => DB::raw('voucher_no'),
            ]);

            Schema::table('vouchers', function (Blueprint $table) {
                $table->unique('voucher_number', 'vouchers_voucher_number_unique');
            });
        } elseif (! $hasVoucherNo && $hasVoucherNumber) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->string('voucher_no', 191)->nullable()->after('voucher_number');
            });

            DB::table('vouchers')->whereNull('voucher_no')->update([
                'voucher_no' => DB::raw('voucher_number'),
            ]);

            Schema::table('vouchers', function (Blueprint $table) {
                $table->unique('voucher_no', 'vouchers_voucher_no_unique');
            });
        } elseif (! $hasVoucherNo && ! $hasVoucherNumber) {
            Schema::table('vouchers', function (Blueprint $table) {
                $table->string('voucher_number', 191)->unique();
                $table->string('voucher_no', 191)->unique();
            });
        }
    }

    public function down(): void
    {
        // Intentionally no-op: columns may be used by either codepath.
    }
};
