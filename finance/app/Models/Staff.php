<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Staff extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'staff_id',
        'position',
        'department',
        'monthly_salary',
        'phone',
        'email',
        'bank_name',
        'bank_account',
        'date_joined',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'monthly_salary' => 'decimal:2',
        'date_joined'    => 'date',
    ];

    // Relationships
    public function payrollEntries()
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function deductionTypes()
    {
        return $this->hasManyThrough(PayrollEntryDeduction::class, PayrollEntry::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Total gross paid to this staff (sum of all payroll entries gross_salary)
    public function totalGrossPaid(): float
    {
        return (float) $this->payrollEntries()->sum('gross_salary');
    }

    // Total deductions across all payroll entries
    public function totalDeductions(): float
    {
        return (float) $this->payrollEntries()->sum('total_deductions');
    }

    // Total net paid (take-home)
    public function totalNetPaid(): float
    {
        return (float) $this->payrollEntries()->sum('net_salary');
    }
}
