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
            $table->unsignedBigInteger('staff_id');
            $table->unsignedBigInteger('deduction_type_id')->nullable();
            $table->string('name');
            $table->enum('type', ['fixed', 'percentage', 'insurance', 'penalty', 'other'])->default('fixed');
            $table->decimal('default_amount', 15, 2)->default(0);
            $table->string('note', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // FKs only work on InnoDB; MyISAM tenants skip them via DB_ENGINE env.
            // Eloquent relationships handle the logical constraint either way.
            if (config('database.connections.tenant.engine', 'InnoDB') !== 'MyISAM') {
                $table->foreign('staff_id')->references('id')->on('staff')->cascadeOnDelete();
                $table->foreign('deduction_type_id')->references('id')->on('payroll_deduction_types')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('staff_deductions');
    }
};
