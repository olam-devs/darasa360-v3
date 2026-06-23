<?php

namespace App\Models;

class BookMonthlyCut extends BaseModel
{
    protected $connection = 'tenant';

    protected $fillable = [
        'book_id',
        'name',
        'is_active',
        'day_of_month',
        'amount',
        'particular_id',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'day_of_month' => 'integer',
        'amount' => 'decimal:2',
    ];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function particular()
    {
        return $this->belongsTo(Particular::class, 'particular_id');
    }

    public function runs()
    {
        return $this->hasMany(BookMonthlyCutRun::class);
    }
}
