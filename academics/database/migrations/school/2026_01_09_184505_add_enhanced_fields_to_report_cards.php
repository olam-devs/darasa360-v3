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
        Schema::connection('tenant')->table('report_cards', function (Blueprint $table) {
            if (!Schema::connection('tenant')->hasColumn('report_cards', 'pdf_generated')) {
                $table->boolean('pdf_generated')->default(false)->after('status');
            }
            if (!Schema::connection('tenant')->hasColumn('report_cards', 'pdf_path')) {
                $table->string('pdf_path', 500)->nullable()->after('pdf_generated');
            }
            if (!Schema::connection('tenant')->hasColumn('report_cards', 'email_sent_at')) {
                $table->timestamp('email_sent_at')->nullable()->after('pdf_path');
            }
            if (!Schema::connection('tenant')->hasColumn('report_cards', 'sms_sent_at')) {
                $table->timestamp('sms_sent_at')->nullable()->after('email_sent_at');
            }
            if (!Schema::connection('tenant')->hasColumn('report_cards', 'parent_viewed_at')) {
                $table->timestamp('parent_viewed_at')->nullable()->after('sms_sent_at');
            }
            if (!Schema::connection('tenant')->hasColumn('report_cards', 'download_count')) {
                $table->integer('download_count')->default(0)->after('parent_viewed_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->table('report_cards', function (Blueprint $table) {
            $table->dropColumn([
                'pdf_generated',
                'pdf_path',
                'email_sent_at',
                'sms_sent_at',
                'parent_viewed_at',
                'download_count'
            ]);
        });
    }
};
