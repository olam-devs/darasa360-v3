<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Confirmed bank receipts: several fee-entry receipt vouchers (one per particular) that were one payment
 * into the bank. Stored centrally so tenant vouchers are never changed; the bank ledger shows a group as
 * one line on its bank date.
 */
return new class extends Migration {
    protected $connection = 'central';

    public function up(): void
    {
        $schema = Schema::connection('central');
        if (!$schema->hasTable('receipt_groups')) {
            $schema->create('receipt_groups', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('book_id');
                $table->unsignedBigInteger('student_id');
                $table->date('receipt_date');
                $table->string('reference', 120)->nullable();
                $table->decimal('total', 16, 2);
                $table->text('note')->nullable();
                $table->unsignedBigInteger('confirmed_by');
                $table->timestamps();
                $table->index(['school_id', 'book_id', 'receipt_date']);
            });
        }
        if (!$schema->hasTable('receipt_group_vouchers')) {
            $schema->create('receipt_group_vouchers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('receipt_group_id')->index();
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('voucher_id');
                // A voucher can belong to one confirmed receipt only.
                $table->unique(['school_id', 'voucher_id'], 'receipt_group_voucher_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('receipt_group_vouchers');
        Schema::connection('central')->dropIfExists('receipt_groups');
    }
};
