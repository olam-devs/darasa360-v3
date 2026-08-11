<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookFeeCategory;
use App\Models\Voucher;
use RuntimeException;

/**
 * Generalized replacement for the old ExpenseController::createExpenseVouchers()/
 * cashViewBalance() - same voucher-creation and bank-fee logic, but driven off
 * a plain (book, date, total, title, description) instead of an Expense model,
 * so ExpenseSubmissionController can reuse it for both an immediate
 * main-accountant-composed approval and a later reviewed/approved submission.
 *
 * Bank fee resolution: if the main accountant explicitly selects a
 * BookFeeCategory (same mechanism the Books/Withdrawal flow already uses,
 * configured per book with its own amount tiers), that's used - this is the
 * "allow" step. If they don't pick one, we fall back to the book's own
 * Book::resolveBankFeeForWithdrawalAmount()/BookBankFeeTier auto-tier, kept
 * for any book that has that older mechanism configured directly (it has no
 * setup UI anywhere, but nothing should silently stop honoring it).
 */
class ExpenseVoucherFactory
{
    /**
     * @throws RuntimeException if the book's cash balance can't cover the
     *   amount plus any applicable bank fee.
     */
    public function assertSufficientBalance(Book $book, float $totalAmount, ?BookFeeCategory $feeCategory = null): void
    {
        $fee = $this->resolveFee($book, $totalAmount, $feeCategory);
        $required = $totalAmount + $fee;
        $balance = $book->getCashViewBalance();

        if ($balance < $required) {
            throw new RuntimeException(sprintf(
                'Insufficient cash balance. Current cash balance: TSh %s, Required: TSh %s%s',
                number_format($balance, 2),
                number_format($required, 2),
                $fee > 0 ? ' (expense '.number_format($totalAmount, 2).' + transaction fee '.number_format($fee, 2).')' : ''
            ));
        }
    }

    /**
     * Creates the main expense voucher (and a second transaction-fee voucher,
     * if a fee applies) for the given amount. Caller is responsible for
     * calling assertSufficientBalance() first and wrapping this in a DB
     * transaction alongside its own row updates.
     *
     * @return array{voucher_id: int, bank_fee_voucher_id: ?int, bank_fee_amount: ?float, bank_fee_category_id: ?int}
     */
    public function create(Book $book, string $transactionDate, float $totalAmount, string $title, ?string $description = null, ?int $createdBy = null, ?BookFeeCategory $feeCategory = null): array
    {
        $createdBy = $createdBy ?? auth()->id();

        $mainVoucher = Voucher::create([
            'date' => $transactionDate,
            'student_id' => null,
            'particular_id' => null,
            'book_id' => $book->id,
            'voucher_type' => 'Payment',
            'debit' => 0,
            'credit' => $totalAmount,
            'payment_by_receipt_to' => $title,
            'notes' => $description,
            'created_by' => $createdBy,
        ]);

        $feeVoucherId = null;
        $bankFeeAmount = null;
        $feeCategoryId = null;

        if ($feeCategory) {
            $fee = $feeCategory->resolveFeeForAmount($totalAmount);
            if ($fee > 0) {
                $feeVoucher = Voucher::create([
                    'date' => $transactionDate,
                    'student_id' => null,
                    'particular_id' => $feeCategory->particular_id,
                    'book_id' => $book->id,
                    'voucher_type' => 'Payment',
                    'debit' => 0,
                    'credit' => $fee,
                    'payment_by_receipt_to' => 'Bank Transaction Fee',
                    'notes' => sprintf(
                        'Transaction fee (%s) for expense "%s" (withdrawal TSh %s). Linked voucher #%s.',
                        $feeCategory->name,
                        $title,
                        number_format($totalAmount, 2),
                        $mainVoucher->voucher_number
                    ),
                    'created_by' => $createdBy,
                ]);
                $feeVoucherId = $feeVoucher->id;
                $bankFeeAmount = $fee;
                $feeCategoryId = $feeCategory->id;
            }
        } else {
            $fee = $book->resolveBankFeeForWithdrawalAmount($totalAmount);
            if ($fee > 0 && $book->bank_fees_enabled && $book->bank_fee_particular_id && !$book->is_cash_book) {
                $feeVoucher = Voucher::create([
                    'date' => $transactionDate,
                    'student_id' => null,
                    'particular_id' => $book->bank_fee_particular_id,
                    'book_id' => $book->id,
                    'voucher_type' => 'Payment',
                    'debit' => 0,
                    'credit' => $fee,
                    'payment_by_receipt_to' => 'Bank transaction fee',
                    'notes' => sprintf(
                        'Bank transaction fee for expense "%s" (withdrawal TSh %s). Linked voucher #%s.',
                        $title,
                        number_format($totalAmount, 2),
                        $mainVoucher->voucher_number
                    ),
                    'created_by' => $createdBy,
                ]);
                $feeVoucherId = $feeVoucher->id;
                $bankFeeAmount = $fee;
            }
        }

        return [
            'voucher_id' => $mainVoucher->id,
            'bank_fee_voucher_id' => $feeVoucherId,
            'bank_fee_amount' => $bankFeeAmount,
            'bank_fee_category_id' => $feeCategoryId,
        ];
    }

    protected function resolveFee(Book $book, float $totalAmount, ?BookFeeCategory $feeCategory): float
    {
        return $feeCategory
            ? $feeCategory->resolveFeeForAmount($totalAmount)
            : $book->resolveBankFeeForWithdrawalAmount($totalAmount);
    }

    /**
     * Reverses a previously-created expense voucher pair (e.g. on cancel).
     */
    public function reverse(?int $voucherId, ?int $bankFeeVoucherId): void
    {
        if ($bankFeeVoucherId) {
            Voucher::where('id', $bankFeeVoucherId)->delete();
        }
        if ($voucherId) {
            Voucher::where('id', $voucherId)->delete();
        }
    }
}
