<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The item catalog - freely addable by any accountant, no approval needed
     * (unlike categories). Deliberately no category FK and no stock/quantity
     * columns: items are category-agnostic (the same item can appear under
     * different categories across submissions), and this leaves room for an
     * independent later stock-tracking phase to attach to expense_item_id
     * without reshaping this table.
     */
    public function up(): void
    {
        Schema::create('expense_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('unit_type'); // free text: kg, pieces, litres, ...
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_items');
    }
};
