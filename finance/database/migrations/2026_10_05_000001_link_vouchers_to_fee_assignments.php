<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    protected $connection = 'tenant';
    public function up(): void {
        if (! Schema::connection('tenant')->hasColumn('vouchers', 'particular_student_id')) {
            Schema::connection('tenant')->table('vouchers', function (Blueprint $table) {
                $table->unsignedBigInteger('particular_student_id')->nullable()->index();
            });
        }
    }
    public function down(): void {
        if (Schema::connection('tenant')->hasColumn('vouchers', 'particular_student_id')) {
            Schema::connection('tenant')->table('vouchers', fn (Blueprint $table) => $table->dropColumn('particular_student_id'));
        }
    }
};
