<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('status')->default('approved'); // approved | pending | denied
            $table->boolean('show_budget_to_others')->default(false);
            // These store a central SchoolAccountant id, not a tenant users.id -
            // see app/Models/ExpenseSubmission.php for the full explanation.
            // Plain indexed columns, no FK constraint, matching Voucher.created_by.
            $table->unsignedBigInteger('proposed_by')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_note')->nullable();
            $table->timestamps();

            $table->index('proposed_by');
            $table->index('decided_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
