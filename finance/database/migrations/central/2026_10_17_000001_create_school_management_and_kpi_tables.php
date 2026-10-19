<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    protected $connection = 'central';

    public function up(): void
    {
        $schema = Schema::connection('central');
        if (!$schema->hasTable('school_management_access')) {
            $schema->create('school_management_access', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('manager_accountant_id');
                $table->unsignedBigInteger('school_id');
                $table->unsignedBigInteger('school_accountant_id');
                $table->timestamps();
                $table->unique(['manager_accountant_id', 'school_id'], 'management_school_unique');
            });
        }
        if (!$schema->hasTable('accountant_kpi_targets')) {
            $schema->create('accountant_kpi_targets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('school_id')->index();
                $table->unsignedBigInteger('accountant_id')->index();
                $table->string('metric', 40);
                $table->date('from_date');
                $table->date('to_date');
                $table->decimal('target_value', 16, 2);
                $table->unsignedInteger('recording_days')->default(1);
                $table->unsignedBigInteger('updated_by');
                $table->timestamps();
                $table->unique(['school_id', 'accountant_id', 'metric', 'from_date', 'to_date'], 'accountant_period_metric_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('accountant_kpi_targets');
        Schema::connection('central')->dropIfExists('school_management_access');
    }
};
