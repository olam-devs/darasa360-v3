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
            // No FK constraint on book_id/academic_year_id/voucher_id/
            // bank_fee_voucher_id: books, academic_years, and vouchers are
            // all pre-existing base tenant tables that remain MyISAM on
            // schools provisioned before this app's InnoDB-engine-forcing
            // fix (confirmed on this sandbox school) - MySQL cannot create a
            // foreign key referencing a MyISAM table (error 1824). Plain
            // indexed columns instead, enforced at the app layer only, same
            // as every other cross-connection-style reference in this batch.
            $table->unsignedBigInteger('book_id')->nullable();
            $table->unsignedBigInteger('academic_year_id');
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
            $table->unsignedBigInteger('voucher_id')->nullable();
            $table->unsignedBigInteger('bank_fee_voucher_id')->nullable();
            $table->decimal('bank_fee_amount', 15, 2)->nullable();
            $table->timestamps();

            $table->index('book_id');
            $table->index('academic_year_id');
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
