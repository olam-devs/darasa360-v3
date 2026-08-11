<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replaces `expenses` (renamed to legacy_expenses, see the last migration
     * in this batch) as the parent record for a non-payroll expense. Now a
     * "submission" with multiple line items instead of one amount.
     */
    public function up(): void
    {
        Schema::create('expense_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submission_number')->unique();
            $table->foreignId('expense_category_id')->constrained();
            $table->foreignId('book_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('academic_year_id')->constrained();
            $table->date('transaction_date');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            // Denormalized cache only - always recomputed from line items on
            // every write, never trusted as the source of truth.
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status')->default('pending'); // pending | approved | partially_approved | denied | cancelled
            $table->unsignedBigInteger('submitted_by');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable(); // visible to ALL accountants at the school
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->foreignId('bank_fee_voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
            $table->decimal('bank_fee_amount', 15, 2)->nullable();
            $table->timestamps();

            $table->index('submitted_by');
            $table->index('decided_by');
            $table->index(['status', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_submissions');
    }
};
