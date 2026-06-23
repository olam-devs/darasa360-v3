<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Base migration for tenant databases.
 * Creates all necessary tables for a school database.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Users table
        if (! Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email', 191)->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default('accountant');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Password reset tokens
        if (! Schema::hasTable('password_reset_tokens')) {
            Schema::create('password_reset_tokens', function (Blueprint $table) {
                $table->string('email', 191)->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }

        // Sessions
        if (! Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id', 191)->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }

        // Cache
        if (! Schema::hasTable('cache')) {
            Schema::create('cache', function (Blueprint $table) {
                $table->string('key', 191)->primary();
                $table->mediumText('value');
                $table->integer('expiration');
            });
        }

        if (! Schema::hasTable('cache_locks')) {
            Schema::create('cache_locks', function (Blueprint $table) {
                $table->string('key', 191)->primary();
                $table->string('owner');
                $table->integer('expiration');
            });
        }

        // Jobs
        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->id();
                $table->string('queue', 191)->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (! Schema::hasTable('job_batches')) {
            Schema::create('job_batches', function (Blueprint $table) {
                $table->string('id', 191)->primary();
                $table->string('name');
                $table->integer('total_jobs');
                $table->integer('pending_jobs');
                $table->integer('failed_jobs');
                $table->longText('failed_job_ids');
                $table->mediumText('options')->nullable();
                $table->integer('cancelled_at')->nullable();
                $table->integer('created_at');
                $table->integer('finished_at')->nullable();
            });
        }

        if (! Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('uuid', 191)->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        // School Settings
        if (! Schema::hasTable('school_settings')) {
            Schema::create('school_settings', function (Blueprint $table) {
                $table->id();
                $table->string('school_name');
                $table->string('po_box')->nullable();
                $table->string('region')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('logo_path')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_account_number')->nullable();
                $table->string('bank_account_name')->nullable();
                $table->string('address')->nullable();
                $table->timestamps();
            });
        }

        // School Classes
        if (! Schema::hasTable('school_classes')) {
            Schema::create('school_classes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();
            });
        }

        // Students
        if (! Schema::hasTable('students')) {
            Schema::create('students', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('student_reg_no', 191)->unique();
                $table->string('class')->nullable();
                $table->foreignId('class_id')->nullable()->constrained('school_classes')->onDelete('set null');
                $table->string('phone')->nullable();
                $table->string('parent_phone_1')->nullable();
                $table->string('parent_phone_2')->nullable();
                $table->string('parent_name')->nullable();
                $table->string('parent_email')->nullable();
                $table->string('gender')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->string('address')->nullable();
                $table->string('status')->default('active');
                $table->timestamps();
            });
        }

        // Books (Cash/Bank accounts)
        if (! Schema::hasTable('books')) {
            Schema::create('books', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type')->default('cash');
                $table->string('description')->nullable();
                $table->string('account_number')->nullable();
                $table->string('bank_name')->nullable();
                $table->decimal('opening_balance', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Particulars (Fee items)
        if (! Schema::hasTable('particulars')) {
            Schema::create('particulars', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('description')->nullable();
                $table->decimal('default_amount', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Academic Years
        if (! Schema::hasTable('academic_years')) {
            Schema::create('academic_years', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_current')->default(false);
                $table->timestamps();
            });
        }

        // Particular-Student pivot table
        if (! Schema::hasTable('particular_student')) {
            Schema::create('particular_student', function (Blueprint $table) {
                $table->id();
                $table->foreignId('particular_id')->constrained()->onDelete('cascade');
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('academic_year_id')->nullable()->constrained()->onDelete('set null');
                $table->decimal('sales', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->date('deadline')->nullable();
                $table->timestamps();

                $table->unique(['particular_id', 'student_id', 'academic_year_id'], 'ps_unique');
            });
        }

        // Vouchers (Transactions)
        if (! Schema::hasTable('vouchers')) {
            Schema::create('vouchers', function (Blueprint $table) {
                $table->id();
                $table->string('voucher_no', 191)->unique();
                $table->foreignId('student_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('particular_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('book_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('academic_year_id')->nullable()->constrained()->onDelete('set null');
                $table->string('voucher_type');
                $table->decimal('amount', 15, 2)->default(0);
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('credit', 15, 2)->default(0);
                $table->string('description')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('reference_number')->nullable();
                $table->string('payment_by_receipt_to')->nullable();
                $table->text('notes')->nullable();
                $table->date('transaction_date')->nullable();
                $table->date('date')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // Expenses
        if (! Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('book_id')->nullable()->constrained()->onDelete('set null');
                $table->string('expense_number', 191)->unique();
                $table->string('expense_name')->nullable();
                $table->string('category');
                $table->string('description');
                $table->decimal('amount', 15, 2);
                $table->date('expense_date')->nullable();
                $table->date('transaction_date')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('reference_number')->nullable();
                $table->string('recipient')->nullable();
                $table->string('status')->default('approved');
                $table->text('notes')->nullable();
                $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->onDelete('set null');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // Suspense Accounts
        if (! Schema::hasTable('suspense_accounts')) {
            Schema::create('suspense_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('book_id')->nullable()->constrained()->onDelete('set null');
                $table->string('reference_number', 191)->unique();
                $table->decimal('amount', 15, 2);
                $table->decimal('resolved_amount', 15, 2)->default(0);
                $table->boolean('resolved')->default(false);
                $table->string('payer_name')->nullable();
                $table->string('payer_phone')->nullable();
                $table->string('payment_method')->nullable();
                $table->date('received_date')->nullable();
                $table->date('date')->nullable();
                $table->text('description')->nullable();
                $table->string('status')->default('pending');
                $table->foreignId('resolved_student_id')->nullable()->constrained('students')->onDelete('set null');
                $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('resolved_at')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->onDelete('set null');
                $table->timestamps();
            });
        }

        // Staff
        if (! Schema::hasTable('staff')) {
            Schema::create('staff', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('employee_id', 191)->unique();
                $table->string('position');
                $table->string('department')->nullable();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->decimal('basic_salary', 15, 2)->default(0);
                $table->string('bank_name')->nullable();
                $table->string('bank_account')->nullable();
                $table->string('status')->default('active');
                $table->date('hire_date')->nullable();
                $table->timestamps();
            });
        }

        // Payroll Entries
        if (! Schema::hasTable('payroll_entries')) {
            Schema::create('payroll_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('staff_id')->constrained()->onDelete('cascade');
                $table->foreignId('book_id')->nullable()->constrained()->onDelete('set null');
                $table->string('period');
                $table->decimal('basic_salary', 15, 2);
                $table->decimal('allowances', 15, 2)->default(0);
                $table->decimal('deductions', 15, 2)->default(0);
                $table->decimal('net_salary', 15, 2);
                $table->string('status')->default('pending');
                $table->date('payment_date')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('reference_number')->nullable();
                $table->text('notes')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // SMS Logs
        if (! Schema::hasTable('sms_logs')) {
            Schema::create('sms_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('student_id')->nullable()->constrained()->onDelete('set null');
                $table->foreignId('sent_by')->nullable()->constrained('users')->onDelete('set null');
                $table->string('recipient_phone');
                $table->text('message');
                $table->string('message_id')->nullable();
                $table->string('status')->default('pending');
                $table->integer('status_code')->nullable();
                $table->string('status_description')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->integer('sms_count')->default(1);
                $table->timestamps();
            });
        }

        // SMS Templates
        if (! Schema::hasTable('sms_templates')) {
            Schema::create('sms_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('message_en');
                $table->text('message_sw')->nullable();
                $table->string('type')->default('custom');
                $table->boolean('is_active')->default(true);
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // Bank Accounts
        if (! Schema::hasTable('bank_accounts')) {
            Schema::create('bank_accounts', function (Blueprint $table) {
                $table->id();
                $table->string('account_name');
                $table->string('account_number');
                $table->string('bank_name');
                $table->string('branch')->nullable();
                $table->string('swift_code')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Bank API Settings
        if (! Schema::hasTable('bank_api_settings')) {
            Schema::create('bank_api_settings', function (Blueprint $table) {
                $table->id();
                $table->string('bank_name');
                $table->string('api_url')->nullable();
                $table->string('api_key')->nullable();
                $table->string('api_secret')->nullable();
                $table->string('account_id')->nullable();
                $table->boolean('is_active')->default(false);
                $table->json('settings')->nullable();
                $table->timestamps();
            });
        }

        // Bank Transactions
        if (! Schema::hasTable('bank_transactions')) {
            Schema::create('bank_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('bank_account_id')->nullable()->constrained()->onDelete('set null');
                $table->string('transaction_id', 191)->unique();
                $table->string('reference_number')->nullable();
                $table->string('type');
                $table->decimal('amount', 15, 2);
                $table->decimal('balance_after', 15, 2)->nullable();
                $table->string('payer_name')->nullable();
                $table->string('payer_account')->nullable();
                $table->text('description')->nullable();
                $table->timestamp('transaction_date');
                $table->string('status')->default('pending');
                $table->foreignId('matched_student_id')->nullable()->constrained('students')->onDelete('set null');
                $table->foreignId('processed_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();
            });
        }

        // Headmasters
        if (! Schema::hasTable('headmasters')) {
            Schema::create('headmasters', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('registration_number', 191)->unique();
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Book Transactions
        if (! Schema::hasTable('book_transactions')) {
            Schema::create('book_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('from_book_id')->constrained('books')->onDelete('cascade');
                $table->foreignId('to_book_id')->constrained('books')->onDelete('cascade');
                $table->decimal('amount', 15, 2);
                $table->string('description')->nullable();
                $table->string('reference_number')->nullable();
                $table->date('transaction_date');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamps();
            });
        }

        // Scholarships
        if (! Schema::hasTable('scholarships')) {
            Schema::create('scholarships', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('sponsor_name');
                $table->text('description')->nullable();
                $table->enum('discount_type', ['percentage', 'fixed'])->default('percentage');
                $table->decimal('discount_value', 15, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Scholarship-Student pivot table
        if (! Schema::hasTable('scholarship_student')) {
            Schema::create('scholarship_student', function (Blueprint $table) {
                $table->id();
                $table->foreignId('scholarship_id')->constrained()->onDelete('cascade');
                $table->foreignId('student_id')->constrained()->onDelete('cascade');
                $table->foreignId('academic_year_id')->nullable()->constrained()->onDelete('set null');
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->timestamps();

                $table->unique(['scholarship_id', 'student_id', 'academic_year_id'], 'ss_unique');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scholarship_student');
        Schema::dropIfExists('scholarships');
        Schema::dropIfExists('book_transactions');
        Schema::dropIfExists('headmasters');
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_api_settings');
        Schema::dropIfExists('bank_accounts');
        Schema::dropIfExists('sms_templates');
        Schema::dropIfExists('sms_logs');
        Schema::dropIfExists('payroll_entries');
        Schema::dropIfExists('staff');
        Schema::dropIfExists('suspense_accounts');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('vouchers');
        Schema::dropIfExists('particular_student');
        Schema::dropIfExists('academic_years');
        Schema::dropIfExists('particulars');
        Schema::dropIfExists('books');
        Schema::dropIfExists('students');
        Schema::dropIfExists('school_classes');
        Schema::dropIfExists('school_settings');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('job_batches');
        Schema::dropIfExists('jobs');
        Schema::dropIfExists('cache_locks');
        Schema::dropIfExists('cache');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
