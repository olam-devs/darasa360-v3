<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Expense extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

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
