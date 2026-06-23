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
        Schema::table('support_tickets', function (Blueprint $table) {
            if (!Schema::hasColumn('support_tickets', 'escalation_level')) {
                $table->enum('escalation_level', ['system_admin', 'super_admin'])->default('system_admin')->after('priority');
            }
            if (!Schema::hasColumn('support_tickets', 'is_escalated')) {
                $table->boolean('is_escalated')->default(false)->after('escalation_level');
            }
            if (!Schema::hasColumn('support_tickets', 'escalated_at')) {
                $table->dateTime('escalated_at')->nullable()->after('is_escalated');
            }
            if (!Schema::hasColumn('support_tickets', 'escalated_to')) {
                $table->foreignId('escalated_to')->nullable()->after('escalated_at');
            }
            if (!Schema::hasColumn('support_tickets', 'escalated_by')) {
                $table->foreignId('escalated_by')->nullable()->after('escalated_to');
            }
            if (!Schema::hasColumn('support_tickets', 'escalation_reason')) {
                $table->text('escalation_reason')->nullable()->after('escalated_by');
            }
            if (!Schema::hasColumn('support_tickets', 'notify_sms')) {
                $table->boolean('notify_sms')->default(true)->after('escalation_reason');
            }
            if (!Schema::hasColumn('support_tickets', 'notify_email')) {
                $table->boolean('notify_email')->default(true)->after('notify_sms');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn([
                'escalation_level',
                'is_escalated',
                'escalated_at',
                'escalated_to',
                'escalated_by',
                'escalation_reason',
                'notify_sms',
                'notify_email'
            ]);
        });
    }
};
