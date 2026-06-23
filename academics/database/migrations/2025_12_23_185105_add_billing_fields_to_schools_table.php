<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->integer('total_users')->default(0)->after('package');
            $table->integer('active_users')->default(0)->after('total_users');
            $table->decimal('price_per_user', 10, 2)->default(0.00)->after('active_users');
            $table->decimal('monthly_charge', 10, 2)->default(0.00)->after('price_per_user');
            $table->date('billing_start_date')->nullable()->after('monthly_charge');
            $table->date('next_billing_date')->nullable()->after('billing_start_date');
            $table->enum('billing_status', ['active', 'suspended', 'overdue'])->default('active')->after('next_billing_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schools', function (Blueprint $table) {
            $table->dropColumn([
                'total_users',
                'active_users',
                'price_per_user',
                'monthly_charge',
                'billing_start_date',
                'next_billing_date',
                'billing_status'
            ]);
        });
    }
};
