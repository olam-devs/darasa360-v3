<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('book_monthly_cuts')) {
            Schema::create('book_monthly_cuts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
                $table->string('name');
                $table->boolean('is_active')->default(true);
                $table->unsignedTinyInteger('day_of_month'); // 1-28 recommended
                $table->decimal('amount', 15, 2);
                $table->foreignId('particular_id')->nullable()->constrained('particulars')->nullOnDelete();
                $table->string('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('book_monthly_cut_runs')) {
            Schema::create('book_monthly_cut_runs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('book_monthly_cut_id')->constrained('book_monthly_cuts')->cascadeOnDelete();
                $table->unsignedSmallInteger('year');
                $table->unsignedTinyInteger('month'); // 1-12
                $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->nullOnDelete();
                $table->timestamps();

                $table->unique(['book_monthly_cut_id', 'year', 'month'], 'monthly_cut_once_per_month');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('book_monthly_cut_runs');
        Schema::dropIfExists('book_monthly_cuts');
    }
};
