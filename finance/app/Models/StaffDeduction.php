<?php

namespace App\Models;

class StaffDeduction extends BaseModel
{
    protected $connection = 'tenant';

    protected $table = 'staff_deductions';

    protected $fillable = [
        'staff_id',
        'deduction_type_id',
        'name',
        'type',
        'default_amount',
        'note',
        'is_active',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_active'      => 'boolean',
    ];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function deductionType()
    {
        return $this->belongsTo(PayrollDeductionType::class, 'deduction_type_id');
    }
}
