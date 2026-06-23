<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('assistant_reports', function (Blueprint $table) {
            $table->id();
            $table->string('audience')->default('parent'); // parent|headmaster|accountant
            $table->text('message');
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('status')->default('new'); // new|read|resolved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('assistant_reports');
    }
};
