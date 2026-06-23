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
        Schema::connection('tenant')->table('report_card_configurations', function (Blueprint $table) {
            if (!Schema::connection('tenant')->hasColumn('report_card_configurations', 'has_parent_letter')) {
                $table->boolean('has_parent_letter')->default(false)->after('description');
            }
            if (!Schema::connection('tenant')->hasColumn('report_card_configurations', 'auto_fetch_comments')) {
                $table->boolean('auto_fetch_comments')->default(true)->after('has_parent_letter');
            }
            if (!Schema::connection('tenant')->hasColumn('report_card_configurations', 'distribution_method')) {
                $table->enum('distribution_method', ['none', 'email', 'sms', 'both'])->default('both')->after('auto_fetch_comments');
            }
            if (!Schema::connection('tenant')->hasColumn('report_card_configurations', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('distribution_method');
            }
            if (!Schema::connection('tenant')->hasColumn('report_card_configurations', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('report_card_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'has_parent_letter',
                'auto_fetch_comments',
                'distribution_method',
                'approved_by',
                'approved_at'
            ]);
        });
    }
};
