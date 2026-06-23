<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('suspense_accounts')) {
            return;
        }

        Schema::table('suspense_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('suspense_accounts', 'resolved_amount')) {
                $table->decimal('resolved_amount', 15, 2)->default(0)->after('amount');
            }
            if (! Schema::hasColumn('suspense_accounts', 'resolved')) {
                $table->boolean('resolved')->default(false)->after('resolved_amount');
            }
        });

        // Legacy rows: resolution was tracked via resolved_at / status only.
        if (Schema::hasColumn('suspense_accounts', 'resolved_at')) {
            DB::table('suspense_accounts')
                ->whereNotNull('resolved_at')
                ->update([
                    'resolved' => true,
                    'resolved_amount' => DB::raw('amount'),
                ]);
        }

        if (Schema::hasColumn('suspense_accounts', 'status')) {
            DB::table('suspense_accounts')
                ->whereIn('status', ['resolved', 'completed'])
                ->where('resolved', false)
                ->update([
                    'resolved' => true,
                    'resolved_amount' => DB::raw('amount'),
                ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('suspense_accounts')) {
            return;
        }

        Schema::table('suspense_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('suspense_accounts', 'resolved')) {
                $table->dropColumn('resolved');
            }
            if (Schema::hasColumn('suspense_accounts', 'resolved_amount')) {
                $table->dropColumn('resolved_amount');
            }
        });
    }
};
