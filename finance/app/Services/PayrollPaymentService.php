<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookFeeCategory;
use App\Models\PayrollEntry;
use App\Models\PayrollEntryDeduction;
use App\Models\Staff;
use App\Models\Voucher;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Records one staff salary payment: the net-salary Payment voucher, an optional
 * transaction-fee voucher, the payroll entry and its deductions. Shared by the
 * school's payroll screen (PayrollController::storePayroll) and the management
 * portal. Runs against whichever tenant is current; the caller selects the school.
 */
class PayrollPaymentService
{
    /**
     * @param array $validated staff_id, book_id, month, year, gross_salary, payment_date, payment_method,
     *                         reference_number?, notes?, deductions[]?, book_fee_category_id?
     * @param bool $checkBalance refuse when the book cannot cover net salary plus fee
     * @return array{0: PayrollEntry, 1: ?float} the entry and the fee charged
     * @throws RuntimeException when the balance check fails
     */
    public function pay(array $validated, int $createdBy, bool $checkBalance = false): array
    {
        return DB::connection('tenant')->transaction(function () use ($validated, $createdBy, $checkBalance) {
            $gross = (float) $validated['gross_salary'];
            $deductions = $validated['deductions'] ?? [];

            // Calculate total deductions
            $totalDeductions = 0.0;
            foreach ($deductions as &$ded) {
                if (($ded['type'] ?? 'fixed') === 'percentage') {
                    // Percentage deductions are % of gross
                    $ded['amount'] = round($gross * ((float) $ded['amount'] / 100), 2);
                }
                $totalDeductions += (float) $ded['amount'];
            }
            unset($ded);

            $netSalary = max(0.0, $gross - $totalDeductions);
            $period = date('F Y', mktime(0, 0, 0, $validated['month'], 1, $validated['year']));
            $staff = Staff::findOrFail($validated['staff_id']);

            $feeCategory = empty($validated['book_fee_category_id']) ? null
                : BookFeeCategory::where('book_id', $validated['book_id'])->where('is_active', true)->find($validated['book_fee_category_id']);
            $fee = $feeCategory ? (float) $feeCategory->resolveFeeForAmount($netSalary) : 0.0;

            if ($checkBalance) {
                $book = Book::lockForUpdate()->findOrFail($validated['book_id']);
                $balance = (float) $book->getCashViewBalance();
                if ($balance < $netSalary + $fee) {
                    throw new RuntimeException(sprintf('Insufficient balance in %s: TSh %s available, TSh %s needed.',
                        $book->name, number_format($balance, 0), number_format($netSalary + $fee, 0)));
                }
            }

            // Create voucher — net salary paid from book
            $voucher = Voucher::create([
                'voucher_type'          => 'Payment',
                'date'                  => $validated['payment_date'],
                'book_id'               => $validated['book_id'],
                'debit'                 => 0,
                'credit'                => $netSalary,
                'payment_by_receipt_to' => $staff->name,
                'notes'                 => 'Salary — ' . $staff->name . ' (' . $period . ')'
                    . (! empty($validated['reference_number']) ? ' — Ref: ' . $validated['reference_number'] : ''),
                'created_by'            => $createdBy,
            ]);

            // Optional transaction fee — the accountant explicitly picks one
            // of the book's configured BookFeeCategory options (same
            // mechanism Withdrawals and Expenses use); leaving it unset cuts
            // no fee at all. Fee is based on net_salary, the amount actually
            // leaving the book, matching how Withdrawals base it on the
            // withdrawal amount.
            $feeVoucher = null;
            $feeAmount = null;
            $feeCategoryId = null;
            if ($feeCategory && $fee > 0) {
                $feeVoucher = Voucher::create([
                    'date'                  => $validated['payment_date'],
                    'student_id'            => null,
                    'particular_id'         => $feeCategory->particular_id,
                    'book_id'               => $validated['book_id'],
                    'voucher_type'          => 'Payment',
                    'debit'                 => 0,
                    'credit'                => $fee,
                    'payment_by_receipt_to' => 'Bank Transaction Fee',
                    'notes'                 => sprintf(
                        'Transaction fee (%s) for payroll — %s (%s), net TSh %s. Linked voucher #%s.',
                        $feeCategory->name,
                        $staff->name,
                        $period,
                        number_format($netSalary, 2),
                        $voucher->voucher_number
                    ),
                    'created_by' => $createdBy,
                ]);
                $feeAmount = $fee;
                $feeCategoryId = $feeCategory->id;
            }

            // Create payroll entry
            $payroll = PayrollEntry::create([
                'staff_id'         => $validated['staff_id'],
                'book_id'          => $validated['book_id'],
                'voucher_id'       => $voucher->id,
                'period'           => $period,
                'month'            => $validated['month'],
                'year'             => $validated['year'],
                'gross_salary'     => $gross,
                'total_deductions' => $totalDeductions,
                'net_salary'       => $netSalary,
                'status'           => 'paid',
                'payment_date'     => $validated['payment_date'],
                'payment_method'   => $validated['payment_method'],
                'reference_number' => $validated['reference_number'] ?? null,
                'notes'            => $validated['notes'] ?? null,
                'created_by'       => $createdBy,
                'bank_fee_voucher_id'  => $feeVoucher?->id,
                'bank_fee_amount'      => $feeAmount,
                'bank_fee_category_id' => $feeCategoryId,
            ]);

            // Save individual deductions
            foreach ($deductions as $ded) {
                PayrollEntryDeduction::create([
                    'payroll_entry_id'  => $payroll->id,
                    'deduction_type_id' => $ded['deduction_type_id'] ?? null,
                    'name'              => $ded['name'],
                    'type'              => $ded['type'],
                    'amount'            => $ded['amount'],
                    'note'              => $ded['note'] ?? null,
                ]);
            }

            return [$payroll, $feeAmount];
        });
    }
}
