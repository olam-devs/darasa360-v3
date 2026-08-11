<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A stock-in ('in') or stock-out ('out') movement. Current quantity is
     * never cached anywhere - always live-summed from these rows (same
     * philosophy as ExpenseCategoryPlan::actualSpent()), so there's nothing
     * to keep in sync when a movement is edited or deleted.
     */
    public function up(): void
    {
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            $table->enum('type', ['in', 'out']);
            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->string('issued_to')->nullable();
            $table->text('note')->nullable();
            $table->date('movement_date');
            // No FK to `users` - see 2026_08_11_000018.
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['inventory_item_id', 'movement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};
