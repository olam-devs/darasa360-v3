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
        if (!Schema::hasTable('book_transactions')) {
            Schema::create('book_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('book_id')->constrained('books')->onDelete('cascade');
                $table->enum('transaction_type', ['deposit', 'withdrawal']);
                $table->decimal('amount', 15, 2);
                $table->date('transaction_date');
                $table->string('reference_number')->nullable();
                $table->string('short_notes')->nullable(); // Short notes shown in ledger
                $table->text('full_details')->nullable(); // Full details shown in transaction history
                $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->onDelete('set null');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('book_transactions');
    }
};
