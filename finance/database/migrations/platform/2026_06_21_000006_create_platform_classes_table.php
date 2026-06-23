<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'platform';

    public function up(): void
    {
        Schema::connection('platform')->create('platform_classes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('school_id');
            $table->string('name');
            $table->string('level')->nullable();
            $table->string('stream')->nullable();

            // Mapped tenant ids so sync resolves the right class per system
            $table->unsignedBigInteger('finance_class_id')->nullable();
            $table->unsignedBigInteger('academics_class_id')->nullable();

            $table->boolean('synced_finance')->default(false);
            $table->boolean('synced_academics')->default(false);
            $table->timestamps();

            $table->index('school_id');
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('platform_classes');
    }
};
