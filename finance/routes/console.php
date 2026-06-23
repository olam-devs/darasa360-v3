<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('schema:verify', function () {
    $this->info('Darasa Finance schema verification');
    $this->line('Database: '.(config('database.connections.mysql.database') ?? 'mysql'));
    $this->newLine();

    $requiredTables = [
        'academic_years',
        'school_classes',
        'students',
        'particulars',
        'particular_student',
        'vouchers',
        'books',
        'book_transactions',
        'scholarships',
    ];

    $missingTables = [];
    foreach ($requiredTables as $t) {
        if (! Schema::hasTable($t)) {
            $missingTables[] = $t;
        }
    }

    if ($missingTables !== []) {
        $this->error('Missing required tables: '.implode(', ', $missingTables));
        $this->warn('Stop here: migrations must be run / base schema incomplete.');

        return 1;
    }

    $requiredColumns = [
        'vouchers' => [
            'id', 'voucher_type', 'date', 'debit', 'credit',
            'voucher_no', 'voucher_number',
            'student_id', 'particular_id', 'book_id',
        ],
        'particular_student' => [
            'student_id', 'particular_id', 'academic_year_id',
            'sales', 'credit',
            'debit', 'overpayment',
        ],
        'school_classes' => [
            'id', 'name', 'is_active',
        ],
        'students' => [
            'id', 'name', 'student_reg_no', 'status',
            'class_id', 'class',
            'advance_balance',
        ],
        'book_transactions' => [
            'id', 'book_id', 'transaction_type', 'amount', 'transaction_date',
            'voucher_id',
            // newer reconciliation / fee fields (may be nullable)
            'fee_category_id', 'fee_voucher_id',
            'cancelled_at', 'cancel_reason', 'replaced_by_transaction_id',
        ],
        'scholarships' => [
            'id',
            'student_id', 'particular_id', 'academic_year_id',
            'original_amount', 'forgiven_amount', 'remaining_amount',
            'is_active',
        ],
    ];

    $missing = [];
    foreach ($requiredColumns as $table => $cols) {
        foreach ($cols as $col) {
            if (! Schema::hasColumn($table, $col)) {
                $missing[] = $table.'.'.$col;
            }
        }
    }

    // Index checks (best-effort; not fatal if permissions vary)
    $indexMissing = [];
    try {
        $schUnique = DB::select("SHOW INDEX FROM `scholarships` WHERE Key_name = 'sch_unique'");
        if (empty($schUnique)) {
            $indexMissing[] = 'scholarships.sch_unique(student_id, particular_id, academic_year_id)';
        }
    } catch (Throwable $e) {
        $this->warn('Could not check indexes: '.$e->getMessage());
    }

    if ($missing === [] && $indexMissing === []) {
        $this->info('OK: schema looks compatible with analytics + ledgers.');

        return 0;
    }

    if ($missing !== []) {
        $this->error('Missing required columns:');
        foreach ($missing as $m) {
            $this->line(' - '.$m);
        }
    }

    if ($indexMissing !== []) {
        $this->warn('Missing recommended indexes/constraints:');
        foreach ($indexMissing as $m) {
            $this->line(' - '.$m);
        }
    }

    $this->warn('Result: schema mismatch detected. Run migrations / alignment fixes.');

    return 2;
})->purpose('Verify tenant DB schema for analytics/ledgers');
