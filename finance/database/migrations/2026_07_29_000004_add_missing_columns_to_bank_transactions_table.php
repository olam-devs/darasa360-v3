<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * BankAPIController/BankTransaction were written against a redesigned
     * `bank_transactions` table (control_number, bank_name, account_number,
     * processing_status, sms_sent, payer_phone, is_reconciled, plus direct
     * student/book/voucher/suspense_account links) - but no migration ever
     * added those columns, so the original 0001_01_01 schema (bank_account_id,
     * reference_number, balance_after, payer_account, status,
     * matched_student_id, processed_by/at) is still all that exists.
     * Every webhook/simulate call 500s outright (confirmed via
     * "Unknown column 'control_number'"). Adds the columns the current
     * code actually uses; leaves the old, now-unused columns in place
     * rather than risk data loss renaming them (no real bank_transactions
     * rows exist on any known environment yet).
     */
    public function up(): void
    {
        if (! Schema::hasTable('bank_transactions')) {
            return;
        }

        Schema::table('bank_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('bank_transactions', 'control_number')) {
                $table->string('control_number')->nullable()->after('transaction_id');
            }
            if (! Schema::hasColumn('bank_transactions', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('control_number');
            }
            if (! Schema::hasColumn('bank_transactions', 'account_number')) {
                $table->string('account_number')->nullable()->after('bank_name');
            }
            if (! Schema::hasColumn('bank_transactions', 'reference')) {
                $table->string('reference')->nullable()->after('account_number');
            }
            if (! Schema::hasColumn('bank_transactions', 'payer_phone')) {
                $table->string('payer_phone')->nullable()->after('payer_name');
            }
            if (! Schema::hasColumn('bank_transactions', 'is_reconciled')) {
                $table->boolean('is_reconciled')->default(false)->after('status');
            }
            if (! Schema::hasColumn('bank_transactions', 'processing_status')) {
                $table->string('processing_status')->default('pending')->after('is_reconciled');
            }
            if (! Schema::hasColumn('bank_transactions', 'processing_notes')) {
                $table->text('processing_notes')->nullable()->after('processing_status');
            }
            if (! Schema::hasColumn('bank_transactions', 'sms_sent')) {
                $table->boolean('sms_sent')->default(false)->after('processing_notes');
            }
            if (! Schema::hasColumn('bank_transactions', 'payment_id')) {
                $table->unsignedBigInteger('payment_id')->nullable()->after('sms_sent');
            }
            if (! Schema::hasColumn('bank_transactions', 'student_id')) {
                $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('set null')->after('payment_id');
            }
            if (! Schema::hasColumn('bank_transactions', 'book_id')) {
                $table->foreignId('book_id')->nullable()->constrained('books')->onDelete('set null')->after('student_id');
            }
            if (! Schema::hasColumn('bank_transactions', 'voucher_id')) {
                $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->onDelete('set null')->after('book_id');
            }
            if (! Schema::hasColumn('bank_transactions', 'suspense_account_id')) {
                $table->foreignId('suspense_account_id')->nullable()->constrained('suspense_accounts')->onDelete('set null')->after('voucher_id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('bank_transactions')) {
            return;
        }

        Schema::table('bank_transactions', function (Blueprint $table) {
            foreach ([
                'suspense_account_id', 'voucher_id', 'book_id', 'student_id', 'payment_id',
                'sms_sent', 'processing_notes', 'processing_status', 'is_reconciled',
                'payer_phone', 'reference', 'account_number', 'bank_name', 'control_number',
            ] as $col) {
                if (Schema::hasColumn('bank_transactions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
