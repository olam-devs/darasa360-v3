<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_reports', function (Blueprint $table) {
            $table->id();
            $table->string('report_number')->unique();
            $table->enum('report_type', ['class', 'stream', 'payment_method', 'term', 'year', 'overdue']);
            $table->string('title');
            $table->json('filters')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->unsignedBigInteger('class_id')->nullable();
            $table->json('data');
            $table->string('pdf_path')->nullable();
            $table->string('excel_path')->nullable();
            $table->boolean('sent_to_headmaster')->default(false);
            $table->boolean('sent_to_owner')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('class_id')->references('id')->on('classes')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_reports');
    }
};