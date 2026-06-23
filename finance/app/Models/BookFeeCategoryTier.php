<?php

namespace App\Models;

class BookFeeCategoryTier extends BaseModel
{
    protected $connection = 'tenant';

    protected $fillable = [
        'book_fee_category_id',
        'amount_from',
        'amount_to',
        'fee_amount',
        'sort_order',
    ];

    protected $casts = [
        'amount_from' => 'decimal:2',
        'amount_to' => 'decimal:2',
        'fee_amount' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(BookFeeCategory::class, 'book_fee_category_id');
    }
}
