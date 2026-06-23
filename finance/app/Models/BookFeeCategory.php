<?php

namespace App\Models;

class BookFeeCategory extends BaseModel
{
    protected $connection = 'tenant';

    protected $fillable = [
        'book_id',
        'name',
        'code',
        'is_active',
        'particular_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function tiers()
    {
        return $this->hasMany(BookFeeCategoryTier::class)->orderBy('sort_order')->orderBy('amount_from');
    }

    public function particular()
    {
        return $this->belongsTo(Particular::class, 'particular_id');
    }

    public function resolveFeeForAmount(float $amount): float
    {
        $tiers = $this->relationLoaded('tiers') ? $this->tiers : $this->tiers()->get();
        foreach ($tiers as $tier) {
            $from = (float) $tier->amount_from;
            $to = $tier->amount_to === null ? null : (float) $tier->amount_to;
            if ($amount < $from) {
                continue;
            }
            if ($to !== null && $amount > $to) {
                continue;
            }

            return (float) $tier->fee_amount;
        }

        return 0.0;
    }
}
