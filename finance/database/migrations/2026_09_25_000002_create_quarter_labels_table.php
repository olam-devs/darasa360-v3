<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->create('quarter_labels', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('quarter_number');
            $table->string('label', 60);
            $table->timestamps();
            $table->unique('quarter_number');
        });

        // Seed default labels
        DB::connection('tenant')->table('quarter_labels')->insert([
            ['quarter_number' => 1, 'label' => 'Q1 (Jan–Mar)', 'created_at' => now(), 'updated_at' => now()],
            ['quarter_number' => 2, 'label' => 'Q2 (Apr–Jun)', 'created_at' => now(), 'updated_at' => now()],
            ['quarter_number' => 3, 'label' => 'Q3 (Jul–Sep)', 'created_at' => now(), 'updated_at' => now()],
            ['quarter_number' => 4, 'label' => 'Q4 (Oct–Dec)', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::connection('tenant')->dropIfExists('quarter_labels');
    }
};
