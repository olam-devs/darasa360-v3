<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Maps a NextSMS messageId back to the school that sent it, so the shared
// /api/sms/delivery-callback webhook (one URL, all schools, since SMS
// sending uses one shared gateway account) knows which tenant database to
// update when a delivery report comes in. Lives centrally, not per-tenant,
// because the callback arrives with no session/school context at all.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('sms_delivery_index')) {
            Schema::connection('central')->create('sms_delivery_index', function (Blueprint $table) {
                $table->id();
                $table->string('message_id')->unique();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('sms_delivery_index');
    }
};
