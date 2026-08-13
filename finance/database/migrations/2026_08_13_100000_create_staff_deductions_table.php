<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('staff_deductions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('deduction_type_id')->nullable()->constrained('payroll_deduction_types')->nullOnDelete();
            $table->string('name');
            $table->enum('type', ['fixed', 'percentage', 'insurance', 'penalty', 'other'])->default('fixed');
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->string('note', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('staff_deductions');
    }
};
