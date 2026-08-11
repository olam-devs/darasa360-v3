<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Legacy single-amount expense records - replaced by ExpenseSubmission
 * (category/item/approval-based). Kept read-only for historical reference
 * (Voucher::expense(), old ledger entries) - table renamed to
 * legacy_expenses, nothing writes to it anymore.
 */
class Expense extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

    protected $table = 'legacy_expenses';

    protected $fillable = [
        'expense_name',
        'transaction_date',
        'book_id',
        'amount',
        'bank_fee_amount',
        'description',
        'status',
        'voucher_id',
        'bank_fee_voucher_id',
        'processed_by',
        'processed_at',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'bank_fee_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // Auto-generate expense_number (unique, NOT NULL column the controller never set)
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (! $expense->expense_number) {
                $lastNumber = (int) substr((string) static::latest('id')->value('expense_number'), -6);
                $expense->expense_number = 'EXP'.str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    // Relationships
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function bankFeeVoucher()
    {
        return $this->belongsTo(Voucher::class, 'bank_fee_voucher_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
