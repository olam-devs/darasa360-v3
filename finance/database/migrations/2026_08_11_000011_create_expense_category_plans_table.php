<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A category's budget/plan: an expected amount over a date range. Editing
     * this in place (amount and/or dates) is what makes the expected-vs-actual
     * chart "adjust simultaneously" - nothing else caches a snapshot of this,
     * every chart/report query reads this row + sums expense_line_items live.
     */
    public function up(): void
    {
        Schema::create('expense_category_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->decimal('expected_amount', 15, 2);
            $table->date('from_date');
            $table->date('to_date');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->index(['expense_category_id', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_category_plans');
    }
};
