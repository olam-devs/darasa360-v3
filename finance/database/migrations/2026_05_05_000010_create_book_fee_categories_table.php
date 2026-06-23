<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('book_fee_categories')) {
            Schema::create('book_fee_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
                $table->string('name');
                $table->string('code')->nullable();
                $table->boolean('is_active')->default(true);
                // Where to post the fee for ledger traceability
                $table->foreignId('particular_id')->nullable()->constrained('particulars')->nullOnDelete();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('book_fee_category_tiers')) {
            Schema::create('book_fee_category_tiers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('book_fee_category_id')->constrained('book_fee_categories')->cascadeOnDelete();
                $table->decimal('amount_from', 15, 2);
                $table->decimal('amount_to', 15, 2)->nullable();
                $table->decimal('fee_amount', 15, 2);
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('book_fee_category_tiers');
        Schema::dropIfExists('book_fee_categories');
    }
};
