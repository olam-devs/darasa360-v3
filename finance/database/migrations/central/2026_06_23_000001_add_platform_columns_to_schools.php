<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::connection('central')->table('schools', function (Blueprint $table) {
            $table->string('code', 3)->nullable()->unique()->after('slug');
            $table->boolean('has_finance')->default(true)->after('code');
            $table->boolean('has_academics')->default(false)->after('has_finance');
            $table->boolean('cross_jump_enabled')->default(false)->after('has_academics');
            $table->boolean('parent_cross_access')->default(false)->after('cross_jump_enabled');
            $table->string('academics_db_name')->nullable()->after('parent_cross_access');
            $table->unsignedBigInteger('platform_school_id')->nullable()->after('academics_db_name');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('schools', function (Blueprint $table) {
            $table->dropColumn(['code', 'has_finance', 'has_academics', 'cross_jump_enabled', 'parent_cross_access', 'academics_db_name', 'platform_school_id']);
        });
    }
};
