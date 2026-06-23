<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->decimal('total_fees', 10, 2)->default(0);
            $table->decimal('total_paid', 10, 2)->default(0);
            $table->decimal('total_balance', 10, 2)->default(0);
            $table->enum('fee_status', ['paid', 'partial', 'pending', 'overdue'])->default('pending');
            $table->string('parent_phone')->nullable();
            $table->string('parent_email')->nullable();
            $table->boolean('receives_sms')->default(true);
            $table->boolean('receives_in_app')->default(true);
        });
    }

    public function down()
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'total_fees',
                'total_paid', 
                'total_balance',
                'fee_status',
                'parent_phone',
                'parent_email',
                'receives_sms',
                'receives_in_app'
            ]);
        });
    }
};