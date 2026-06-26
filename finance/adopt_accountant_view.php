<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

echo "============================================\n";
echo " DARASA FINANCE - ADOPT ACCOUNTANT VIEW\n";
echo " (Receipts=DR, Payments=CR)\n";
echo "============================================\n\n";

DB::beginTransaction();
try {
    $receiptTotal = (int) DB::table('vouchers')->where('voucher_type', 'Receipt')->count();
    $paymentTotal = (int) DB::table('vouchers')->where('voucher_type', 'Payment')->count();

    echo "Vouchers found:\n";
    echo "  - Receipt: {$receiptTotal}\n";
    echo "  - Payment: {$paymentTotal}\n\n";

    echo "1) Converting voucher storage...\n";

    // Convert only rows that still look like the old "bank view" storage:
    // - Receipt used to be posted as credit
    // - Payment used to be posted as debit
    $receiptConverted = DB::table('vouchers')
        ->where('voucher_type', 'Receipt')
        ->where('debit', 0)
        ->where('credit', '>', 0)
        ->update([
            'debit' => DB::raw('credit'),
            'credit' => 0,
            'updated_at' => now(),
        ]);

    $paymentConverted = DB::table('vouchers')
        ->where('voucher_type', 'Payment')
        ->where('credit', 0)
        ->where('debit', '>', 0)
        ->update([
            'credit' => DB::raw('debit'),
            'debit' => 0,
            'updated_at' => now(),
        ]);

    echo "  - Receipt rows converted: {$receiptConverted}\n";
    echo "  - Payment rows converted: {$paymentConverted}\n\n";

    echo "2) Rebuilding `particular_student` totals from vouchers...\n";
    DB::table('particular_student')->update([
        'sales' => 0,
        'credit' => 0,
    ]);

    // Sales vouchers set expected fees (sales)
    DB::statement("
        UPDATE particular_student ps
        JOIN (
            SELECT student_id, particular_id, SUM(debit) AS total
            FROM vouchers
            WHERE voucher_type = 'Sales'
              AND student_id IS NOT NULL
              AND particular_id IS NOT NULL
            GROUP BY student_id, particular_id
        ) s ON s.student_id = ps.student_id AND s.particular_id = ps.particular_id
        SET ps.sales = s.total
    ");

    // Receipt vouchers set amounts paid (credit) but are stored as debit in accountant view
    DB::statement("
        UPDATE particular_student ps
        JOIN (
            SELECT student_id, particular_id, SUM(debit) AS total
            FROM vouchers
            WHERE voucher_type = 'Receipt'
              AND student_id IS NOT NULL
              AND particular_id IS NOT NULL
            GROUP BY student_id, particular_id
        ) r ON r.student_id = ps.student_id AND r.particular_id = ps.particular_id
        SET ps.credit = r.total
    ");

    echo "  - Pivot rebuild complete.\n\n";

    DB::commit();
    echo "DONE. Accountant view is now canonical.\n";
    echo "Tip: run ledgers in CASH view for accountant records; BANK view for statement comparison.\n";
} catch (Throwable $e) {
    DB::rollBack();
    echo "\nFAILED: ".$e->getMessage()."\n";
    exit(1);
}
