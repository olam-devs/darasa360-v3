<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * item_name_snapshot/unit_type_snapshot are captured at creation time and
     * never updated afterward - this is what keeps price-history and old
     * report display correct even if an ExpenseItem is later renamed. The
     * expense_item_id FK is what the live autocomplete/price-history lookup
     * groups by.
     */
    public function up(): void
    {
        Schema::create('expense_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_submission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('expense_item_id')->constrained();
            $table->string('item_name_snapshot');
            $table->string('unit_type_snapshot');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_total', 15, 2);
            $table->string('status')->default('pending'); // pending | approved | denied
            $table->text('denial_reason')->nullable();
            $table->timestamps();

            $table->index(['expense_item_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_line_items');
    }
};
