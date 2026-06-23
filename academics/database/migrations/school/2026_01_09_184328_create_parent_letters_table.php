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
        if (!Schema::connection('tenant')->hasTable('parent_letters')) {
            Schema::connection('tenant')->create('parent_letters', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('report_card_config_id');
                $table->string('letter_title')->default('Dear Parents/Guardians');
                $table->text('closing_semester_message')->nullable();
                $table->text('upcoming_semester_message')->nullable();
                $table->text('fee_payment_notice')->nullable();
                $table->text('special_announcements')->nullable();
                $table->text('safari_trips')->nullable();
                $table->text('other_information')->nullable();
                $table->string('signature_name')->nullable();
                $table->string('signature_title')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                // Foreign keys
                $table->foreign('report_card_config_id')
                    ->references('id')->on('report_card_configurations')
                    ->onDelete('cascade');
                $table->foreign('created_by')
                    ->references('id')->on('schoolUsers')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('parent_letters');
    }
};
