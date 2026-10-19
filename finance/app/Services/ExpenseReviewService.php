<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookFeeCategory;
use App\Models\ExpenseItem;
use App\Models\ExpenseLineItem;
use App\Models\ExpenseSubmission;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * The single place an expense submission is decided. Used by the school's own
 * review screen (ExpenseSubmissionController::review) and by the management
 * portal, so both create vouchers and check balances identically.
 * Runs against whichever tenant is current; the caller selects the school.
 */
class ExpenseReviewService
{
    public function __construct(protected ExpenseVoucherFactory $voucherFactory) {}

    /**
     * @param array $validated decision_note, overall_decision (approve|deny), line_items[],
     *                         optional expense_category_id, book_id, book_fee_category_id
     * @throws RuntimeException when already decided, no book, or insufficient balance
     */
    public function decide(int $submissionId, array $validated, int $deciderId): void
    {
        DB::connection('tenant')->transaction(function () use ($submissionId, $validated, $deciderId) {
            $locked = ExpenseSubmission::whereKey($submissionId)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw new RuntimeException('This submission was already decided.');
            }

            if (!empty($validated['expense_category_id'])) {
                $locked->expense_category_id = $validated['expense_category_id'];
            }
            if (!empty($validated['book_id'])) {
                $locked->book_id = $validated['book_id'];
            }
            $locked->save();

            foreach ($validated['line_items'] as $lineData) {
                $line = ExpenseLineItem::where('id', $lineData['id'])
                    ->where('expense_submission_id', $locked->id)
                    ->firstOrFail();

                if (array_key_exists('quantity', $lineData) && $lineData['quantity'] !== null) {
                    $line->quantity = $lineData['quantity'];
                }
                if (array_key_exists('unit_price', $lineData) && $lineData['unit_price'] !== null) {
                    $line->unit_price = $lineData['unit_price'];
                }
                if (!empty($lineData['expense_item_id'])) {
                    $item = ExpenseItem::findOrFail($lineData['expense_item_id']);
                    $line->expense_item_id = $item->id;
                    $line->item_name_snapshot = $item->name;
                    $line->unit_type_snapshot = $item->unit_type ?? '';
                }
                $line->status = $lineData['status'];
                $line->denial_reason = $lineData['status'] === 'denied' ? ($lineData['denial_reason'] ?? null) : null;
                $line->save();
            }

            $approvedTotal = $locked->recomputeTotal('approved');
            $hasApproved = $locked->lineItems()->where('status', 'approved')->exists();
            $allApproved = !$locked->lineItems()->where('status', '!=', 'approved')->exists();

            if ($validated['overall_decision'] === 'deny' || !$hasApproved) {
                $locked->update([
                    'status' => 'denied',
                    'decided_by' => $deciderId,
                    'decided_at' => now(),
                    'decision_note' => $validated['decision_note'],
                ]);

                return;
            }

            if (!$locked->book_id) {
                throw new RuntimeException('A book must be selected before approving.');
            }

            $book = Book::with(['bankFeeTiers', 'bankFeeParticular'])->findOrFail($locked->book_id);
            $feeCategory = $this->resolveFeeCategory($book->id, $validated['book_fee_category_id'] ?? null);
            $this->voucherFactory->assertSufficientBalance($book, $approvedTotal, $feeCategory);
            $voucherData = $this->voucherFactory->create(
                $book,
                $locked->transaction_date->toDateString(),
                $approvedTotal,
                $locked->title ?: $locked->submission_number,
                $locked->description,
                $deciderId,
                $feeCategory
            );

            $locked->update(array_merge($voucherData, [
                'status' => $allApproved ? 'approved' : 'partially_approved',
                'decided_by' => $deciderId,
                'decided_at' => now(),
                'decision_note' => $validated['decision_note'],
            ]));
        });
    }

    protected function resolveFeeCategory(int $bookId, ?int $feeCategoryId): ?BookFeeCategory
    {
        if (!$feeCategoryId) {
            return null;
        }

        return BookFeeCategory::where('book_id', $bookId)
            ->where('is_active', true)
            ->find($feeCategoryId);
    }
}
