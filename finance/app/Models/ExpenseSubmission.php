<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * submitted_by/decided_by store a central App\Models\Central\SchoolAccountant
 * id (via auth()->id() on the 'web' guard), not a tenant users.id - same
 * pre-existing convention as Voucher::created_by/PayrollEntry::approved_by.
 * No FK constraint to `users` on those columns (see the migration) so a
 * coincidental id collision can never trigger a violation.
 */
class ExpenseSubmission extends BaseModel
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'submission_number',
        'expense_category_id',
        'book_id',
        'academic_year_id',
        'transaction_date',
        'title',
        'description',
        'total_amount',
        'status',
        'submitted_by',
        'decided_by',
        'decided_at',
        'decision_note',
        'voucher_id',
        'bank_fee_voucher_id',
        'bank_fee_amount',
        'bank_fee_category_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_amount' => 'decimal:2',
        'decided_at' => 'datetime',
        'bank_fee_amount' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function (self $submission) {
            if (!$submission->submission_number) {
                $lastNumber = (int) substr((string) static::latest('id')->value('submission_number'), -6);
                $submission->submission_number = 'EXP'.str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
            }
        });
    }

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function lineItems()
    {
        return $this->hasMany(ExpenseLineItem::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function bankFeeVoucher()
    {
        return $this->belongsTo(Voucher::class, 'bank_fee_voucher_id');
    }

    public function bankFeeCategory()
    {
        return $this->belongsTo(BookFeeCategory::class, 'bank_fee_category_id');
    }

    /**
     * Recompute total_amount from line items and persist it. Called after
     * any line-item mutation (store, review/edit, approve/deny). $onlyStatus
     * controls which lines count - 'approved' when finalizing a decision,
     * null (all pending/approved) while still being composed/edited.
     */
    public function recomputeTotal(?string $onlyStatus = null): float
    {
        $query = $this->lineItems();
        if ($onlyStatus) {
            $query->where('status', $onlyStatus);
        }

        $total = (float) $query->sum('line_total');
        $this->total_amount = $total;
        $this->save();

        return $total;
    }
}
