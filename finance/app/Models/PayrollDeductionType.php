<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class PayrollDeductionType extends BaseModel
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'type',
        'default_value',
        'is_percentage',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'default_value' => 'decimal:2',
        'is_percentage' => 'boolean',
        'is_active'     => 'boolean',
    ];

    public function entryDeductions()
    {
        return $this->hasMany(PayrollEntryDeduction::class, 'deduction_type_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Total TSH amount deducted across all payroll entries for this type
    public function totalDeducted(): float
    {
        return (float) $this->entryDeductions()->sum('amount');
    }
}
