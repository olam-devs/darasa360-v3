<?php

namespace App\Models;

class BookMonthlyCutRun extends BaseModel
{
    protected $connection = 'tenant';

    protected $fillable = [
        'book_monthly_cut_id',
        'year',
        'month',
        'voucher_id',
    ];

    protected $casts = [
        'year' => 'integer',
        'month' => 'integer',
    ];

    public function cut()
    {
        return $this->belongsTo(BookMonthlyCut::class, 'book_monthly_cut_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }
}
