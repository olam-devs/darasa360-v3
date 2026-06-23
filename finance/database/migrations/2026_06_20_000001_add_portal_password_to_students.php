<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        Schema::connection('tenant')->table('students', function (Blueprint $table) {
            $table->string('portal_password')->nullable()->after('status');
            $table->timestamp('portal_password_set_at')->nullable()->after('portal_password');
            $table->string('portal_password_set_by')->nullable()->after('portal_password_set_at');
        });
    }

    public function down(): void
    {
        Schema::connection('tenant')->table('students', function (Blueprint $table) {
            $table->dropColumn(['portal_password', 'portal_password_set_at', 'portal_password_set_by']);
        });
    }
};
