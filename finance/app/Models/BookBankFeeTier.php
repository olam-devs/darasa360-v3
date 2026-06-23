<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookBankFeeTier extends BaseModel
{
    protected $connection = 'tenant';

    protected $fillable = [
        'book_id',
        'amount_from',
        'amount_to',
        'fee_amount',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'amount_from' => 'decimal:2',
            'amount_to' => 'decimal:2',
            'fee_amount' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
