<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Student extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'student_reg_no',
        'name',
        'gender',
        'class_id',
        'class',
        'parent_id',
        'phone',
        'email',
        'parent_phone_1',
        'parent_phone_2',
        'admission_date',
        'status',
        'advance_balance',
    ];

    protected $casts = [
        'admission_date' => 'date',
        'advance_balance' => 'decimal:2',
    ];

    // Relationships
    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function particulars()
    {
        return $this->belongsToMany(Particular::class, 'particular_student')
            ->withPivot('sales', 'debit', 'credit', 'overpayment', 'deadline', 'academic_year_id')
            ->withTimestamps();
    }

    public function particularsForAcademicYear($academicYearId)
    {
        return $this->belongsToMany(Particular::class, 'particular_student')
            ->withPivot('sales', 'debit', 'credit', 'overpayment', 'deadline', 'academic_year_id')
            ->wherePivot('academic_year_id', $academicYearId)
            ->withTimestamps();
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    public function smsLogs()
    {
        return $this->hasMany(SmsLog::class);
    }

    public function suspenseAccounts()
    {
        return $this->hasMany(SuspenseAccount::class, 'resolved_student_id');
    }

    public function scholarships()
    {
        return $this->hasMany(Scholarship::class);
    }

    public function activeScholarships()
    {
        return $this->hasMany(Scholarship::class)->where('is_active', true);
    }

    /**
     * Check if student has scholarship for a specific particular
     */
    public function hasScholarshipFor($particularId, $academicYearId = null)
    {
        $query = $this->scholarships()->where('particular_id', $particularId)->where('is_active', true);
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->exists();
    }

    /**
     * Get scholarship for a specific particular
     */
    public function getScholarshipFor($particularId, $academicYearId = null)
    {
        $query = $this->scholarships()->where('particular_id', $particularId)->where('is_active', true);
        if ($academicYearId) {
            $query->where('academic_year_id', $academicYearId);
        }

        return $query->first();
    }

    // Helper methods
    public function getBalance()
    {
        $particulars = $this->particulars()->get();
        $totalSales = $particulars->sum('pivot.sales');
        $totalDebit = $particulars->sum('pivot.debit');
        $totalCredit = $particulars->sum('pivot.credit');

        return $totalSales + $totalDebit - $totalCredit;
    }
}
