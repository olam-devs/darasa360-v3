<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollEntry extends BaseModel
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'staff_id',
        'book_id',
        'voucher_id',
        'period',
        'month',
        'year',
        'gross_salary',
        'total_deductions',
        'net_salary',
        'status',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'gross_salary'     => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_salary'       => 'decimal:2',
        'payment_date'     => 'date',
    ];

    // Relationships
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function deductions()
    {
        return $this->hasMany(PayrollEntryDeduction::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
