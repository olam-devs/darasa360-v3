<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Staff table ──────────────────────────────────────────────────────
        // The model uses: staff_id, monthly_salary, date_joined, notes, created_by
        // The DB had:     employee_id, basic_salary, hire_date  (missing notes/created_by)

        Schema::table('staff', function (Blueprint $table) {
            // Rename employee_id → staff_id
            if (Schema::hasColumn('staff', 'employee_id') && ! Schema::hasColumn('staff', 'staff_id')) {
                $table->renameColumn('employee_id', 'staff_id');
            }

            // Rename basic_salary → monthly_salary
            if (Schema::hasColumn('staff', 'basic_salary') && ! Schema::hasColumn('staff', 'monthly_salary')) {
                $table->renameColumn('basic_salary', 'monthly_salary');
            }

            // Rename hire_date → date_joined
            if (Schema::hasColumn('staff', 'hire_date') && ! Schema::hasColumn('staff', 'date_joined')) {
                $table->renameColumn('hire_date', 'date_joined');
            }

            // Add missing columns
            if (! Schema::hasColumn('staff', 'notes')) {
                $table->text('notes')->nullable()->after('date_joined');
            }
            if (! Schema::hasColumn('staff', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->after('notes');
            }
        });

        // ── PayrollEntry table ───────────────────────────────────────────────
        // The model uses: staff_id, amount, month, year, payment_date, book_id, payment_method, reference_number, notes, created_by
        // The DB has:     staff_id, book_id, period(string), basic_salary, allowances, deductions, net_salary, status, payment_date, payment_method, reference_number, notes, created_by, approved_by

        Schema::table('payroll_entries', function (Blueprint $table) {
            // Add month + year columns (controller uses these separately)
            if (! Schema::hasColumn('payroll_entries', 'month')) {
                $table->unsignedTinyInteger('month')->default(1)->after('period');
            }
            if (! Schema::hasColumn('payroll_entries', 'year')) {
                $table->unsignedSmallInteger('year')->default(2024)->after('month');
            }

            // Add gross_salary alias (rename basic_salary if still there, else add)
            if (Schema::hasColumn('payroll_entries', 'basic_salary') && ! Schema::hasColumn('payroll_entries', 'gross_salary')) {
                $table->renameColumn('basic_salary', 'gross_salary');
            } elseif (! Schema::hasColumn('payroll_entries', 'gross_salary')) {
                $table->decimal('gross_salary', 15, 2)->default(0)->after('year');
            }

            // Ensure net_salary exists (it's in migration, just making sure)
            if (! Schema::hasColumn('payroll_entries', 'net_salary')) {
                $table->decimal('net_salary', 15, 2)->default(0)->after('gross_salary');
            }

            // Add total_deductions column for quick reporting
            if (! Schema::hasColumn('payroll_entries', 'total_deductions')) {
                $table->decimal('total_deductions', 15, 2)->default(0)->after('net_salary');
            }

            // Add voucher_id (model has it, DB may not)
            if (! Schema::hasColumn('payroll_entries', 'voucher_id')) {
                $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->onDelete('set null');
            }
        });

        // ── Payroll Deduction Types (master list) ────────────────────────────
        if (! Schema::hasTable('payroll_deduction_types')) {
            Schema::create('payroll_deduction_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');                                     // e.g. "PAYE Tax", "NSSF", "Health Insurance"
                $table->enum('type', ['fixed', 'percentage', 'insurance', 'penalty', 'other'])->default('fixed');
                $table->decimal('default_value', 15, 2)->default(0);       // Fixed amount OR percentage rate
                $table->boolean('is_percentage')->default(false);           // true = default_value is a %
                $table->boolean('is_active')->default(true);
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // ── Per-entry deductions ─────────────────────────────────────────────
        if (! Schema::hasTable('payroll_entry_deductions')) {
            Schema::create('payroll_entry_deductions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payroll_entry_id')->constrained()->onDelete('cascade');
                $table->foreignId('deduction_type_id')->nullable()->constrained('payroll_deduction_types')->onDelete('set null');
                $table->string('name');                                     // snapshot of deduction name
                $table->enum('type', ['fixed', 'percentage', 'insurance', 'penalty', 'other'])->default('fixed');
                $table->decimal('amount', 15, 2);                          // actual TSH amount deducted
                $table->text('note')->nullable();
                $table->timestamps();

                $table->index('payroll_entry_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entry_deductions');
        Schema::dropIfExists('payroll_deduction_types');

        Schema::table('payroll_entries', function (Blueprint $table) {
            foreach (['month', 'year', 'total_deductions', 'voucher_id'] as $col) {
                if (Schema::hasColumn('payroll_entries', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('staff', function (Blueprint $table) {
            foreach (['notes', 'created_by'] as $col) {
                if (Schema::hasColumn('staff', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
