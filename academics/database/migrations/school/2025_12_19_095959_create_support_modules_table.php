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
    Schema::connection('tenant')->create('support_modules', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->text('description')->nullable();
      $table->string('slug')->nullable();
      $table->string('icon')->default('bx bx-help-circle');
      $table->string('color')->default('#00A8E8');
      $table->boolean('is_active')->default(true);
      $table->integer('ticket_count')->default(0);
      $table->timestamps();
    });

    // Insert default modules
    DB::connection('tenant')->table('support_modules')->insert([
      ['name' => 'Administration', 'icon' => 'bx bx-briefcase', 'color' => '#7B68EE', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Academic', 'icon' => 'bx bx-book-open', 'color' => '#FF8C00', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Accounts', 'icon' => 'bx bx-dollar-circle', 'color' => '#4169E1', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Operations', 'icon' => 'bx bx-cog', 'color' => '#FF1493', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Digital Learning', 'icon' => 'bx bx-laptop', 'color' => '#00CED1', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Communication', 'icon' => 'bx bx-message-dots', 'color' => '#DC143C', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Technical Issue', 'icon' => 'bx bx-wrench', 'color' => '#32CD32', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
      ['name' => 'Other', 'icon' => 'bx bx-help-circle', 'color' => '#808080', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::connection('tenant')->dropIfExists('support_modules');
  }
};
