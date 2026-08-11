<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Independent of Expense Planning/approval by explicit design - any
     * accountant can add an item, no approval workflow (a headmaster- or
     * inventory-admin-approval step may be added later, deliberately not
     * built now). category is free text (like BookFeeCategory), not a
     * separate approved/pending table - inventory categories don't carry
     * the budget/approval semantics Expense categories do.
     */
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('unit_type');
            $table->string('category')->nullable();
            $table->decimal('reorder_level', 15, 3)->nullable();
            // No FK to `users` - see 2026_08_11_000018 for why every
            // created_by-style column in this app avoids that FK.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
