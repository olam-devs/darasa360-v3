<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lightweight, admin-visible record of app-level errors that happen inside
 * an otherwise-successful action (e.g. a real SMS that sent but whose log
 * row failed to write) - the accountant sees a friendly message and the
 * action's real outcome is unaffected, but the raw diagnostic detail
 * (exception message, school, context) is captured here instead of only
 * living in the server's laravel.log, which nobody at Olam can see without
 * SSH access. Central, not per-tenant, so one super-admin view covers
 * every school. Not a general-purpose exception handler hook - deliberately
 * opt-in per call site, starting with SMS sending.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('app_error_logs')) {
            Schema::connection('central')->create('app_error_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->nullable()->index();
                $table->string('context'); // e.g. "sms.bulk_send.log_write_failed"
                $table->text('message');
                $table->json('meta')->nullable();
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('app_error_logs');
    }
};
