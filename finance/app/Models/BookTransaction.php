<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookTransaction extends BaseModel
{
    protected $fillable = [
        // Legacy transfer schema compatibility
        'from_book_id',
        'to_book_id',
        'description',
        'book_id',
        'transaction_type',
        'fee_category_id',
        'amount',
        'transaction_date',
        'reference_number',
        'short_notes',
        'full_details',
        'voucher_id',
        'fee_voucher_id',
        'created_by',
        'cancelled_at',
        'cancel_reason',
        'replaced_by_transaction_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Get the book this transaction belongs to
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Get the voucher associated with this transaction
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    public function feeVoucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class, 'fee_voucher_id');
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(BookFeeCategory::class, 'fee_category_id');
    }

    /**
     * Get the user who created this transaction
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if this is a deposit
     */
    public function isDeposit(): bool
    {
        return $this->transaction_type === 'deposit';
    }

    /**
     * Check if this is a withdrawal
     */
    public function isWithdrawal(): bool
    {
        return $this->transaction_type === 'withdrawal';
    }

    /**
     * Get display name for the transaction type
     */
    public function getTypeDisplayAttribute(): string
    {
        return $this->isDeposit() ? 'Bank Deposit' : 'Bank Withdrawal';
    }
}
