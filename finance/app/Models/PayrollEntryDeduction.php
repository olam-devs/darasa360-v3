<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollEntryDeduction extends BaseModel
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'payroll_entry_id',
        'deduction_type_id',
        'name',
        'type',
        'amount',
        'note',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payrollEntry()
    {
        return $this->belongsTo(PayrollEntry::class);
    }

    public function deductionType()
    {
        return $this->belongsTo(PayrollDeductionType::class, 'deduction_type_id');
    }
}
