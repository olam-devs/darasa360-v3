<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('tenant')->table('sms_logs', function (Blueprint $table) {
            if (! Schema::connection('tenant')->hasColumn('sms_logs', 'reference')) {
                $table->string('reference')->nullable()->after('message_id');
            }
            if (! Schema::connection('tenant')->hasColumn('sms_logs', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('sent_at');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('sms_logs', function (Blueprint $table) {
            $table->dropColumn(['reference', 'delivered_at']);
        });
    }
};
