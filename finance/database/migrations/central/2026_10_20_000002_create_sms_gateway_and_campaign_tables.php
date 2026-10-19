<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Own SMS gateway per school (e.g. Onfon: client ID, encrypted API key, sender ID) and the management
 * portal's SMS campaigns: contact lists (e.g. potential customers), campaigns and their recipients.
 */
return new class extends Migration {
    protected $connection = 'central';

    public function up(): void
    {
        $schema = Schema::connection('central');
        if (!$schema->hasTable('school_sms_gateways')) {
            $schema->create('school_sms_gateways', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->unique();
                $table->string('provider', 30)->default('onfon');
                $table->string('client_id', 120);
                $table->text('api_key'); // encrypted with the app key
                $table->string('sender_id', 30);
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }
        if (!$schema->hasTable('sms_contact_lists')) {
            $schema->create('sms_contact_lists', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('manager_accountant_id')->index();
                $table->string('name', 120);
                $table->string('notes', 500)->nullable();
                $table->timestamps();
            });
        }
        if (!$schema->hasTable('sms_contacts')) {
            $schema->create('sms_contacts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sms_contact_list_id')->index();
                $table->string('name', 150)->nullable();
                $table->string('phone', 20);
                $table->timestamp('opted_out_at')->nullable();
                $table->timestamps();
                $table->unique(['sms_contact_list_id', 'phone']);
            });
        }
        if (!$schema->hasTable('sms_campaigns')) {
            $schema->create('sms_campaigns', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('manager_accountant_id')->index();
                $table->unsignedBigInteger('school_id'); // whose SMS account and sender ID were used
                $table->string('title', 150);
                $table->text('message');
                $table->string('audience', 500); // human-readable description of who it went to
                $table->unsignedInteger('recipients')->default(0);
                $table->unsignedInteger('parts')->default(1);
                $table->unsignedInteger('sent')->default(0);
                $table->unsignedInteger('failed')->default(0);
                $table->string('status', 20)->default('sending'); // sending, done
                $table->timestamps();
            });
        }
        if (!$schema->hasTable('sms_campaign_recipients')) {
            $schema->create('sms_campaign_recipients', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sms_campaign_id')->index();
                $table->string('name', 150)->nullable();
                $table->string('phone', 20);
                $table->string('message_id', 100)->nullable()->index();
                $table->string('status', 20)->default('pending'); // pending, sent, delivered, failed
                $table->string('description', 255)->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        foreach (['sms_campaign_recipients', 'sms_campaigns', 'sms_contacts', 'sms_contact_lists', 'school_sms_gateways'] as $t) {
            Schema::connection('central')->dropIfExists($t);
        }
    }
};
