<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('platform_schools', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 3)->unique();   // 3-digit school code, reusable on delete
            $table->string('slug')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('active'); // active|suspended
            $table->string('package')->nullable();

            // Which systems are enabled for this school
            $table->boolean('has_finance')->default(false);
            $table->boolean('has_academics')->default(false);
            $table->boolean('cross_jump_enabled')->default(false); // master switch for SSO handoff
            $table->boolean('parent_cross_access')->default(false); // per-role grant for parents

            // Finance tenant DB connection details
            $table->string('finance_db_name')->nullable();
            $table->string('finance_db_host')->nullable();
            $table->string('finance_db_port')->nullable();
            $table->string('finance_db_user')->nullable();
            $table->string('finance_db_pass')->nullable();

            // Academics tenant DB name (academics resolves host/user from its own env)
            $table->string('academics_db_name')->nullable();

            // Billing
            $table->string('billing_status')->nullable();
            $table->date('billing_start_date')->nullable();
            $table->date('next_billing_date')->nullable();
            $table->decimal('monthly_charge', 12, 2)->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('platform_schools');
    }
};
