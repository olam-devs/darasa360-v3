<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseLineItem extends BaseModel
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'expense_submission_id',
        'expense_item_id',
        'item_name_snapshot',
        'unit_type_snapshot',
        'quantity',
        'unit_price',
        'line_total',
        'status',
        'denial_reason',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function (self $line) {
            $line->line_total = round((float) $line->quantity * (float) $line->unit_price, 2);
        });
    }

    public function submission()
    {
        return $this->belongsTo(ExpenseSubmission::class, 'expense_submission_id');
    }

    public function item()
    {
        return $this->belongsTo(ExpenseItem::class, 'expense_item_id');
    }
}
