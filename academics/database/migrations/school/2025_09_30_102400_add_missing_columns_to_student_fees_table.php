<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('student_fees', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('amount');
            $table->string('transaction_id')->nullable()->after('payment_method');
            $table->string('receipt_path')->nullable()->after('transaction_id');
            $table->date('payment_date')->after('receipt_path');
            $table->text('notes')->nullable()->after('payment_date');
            
            // Add index for better performance
            $table->index(['student_id', 'fee_id']);
        });
    }

    public function down()
    {
        Schema::table('student_fees', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'transaction_id',
                'receipt_path', 
                'payment_date',
                'notes'
            ]);
            $table->dropIndex(['student_id', 'fee_id']);
        });
    }
};