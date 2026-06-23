<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->decimal('total_fees', 10, 2)->default(0);
            $table->decimal('total_collected', 10, 2)->default(0);
            $table->decimal('collection_rate', 5, 2)->default(0);
        });
    }

    public function down()
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropColumn(['total_fees', 'total_collected', 'collection_rate']);
        });
    }
};