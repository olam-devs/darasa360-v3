<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseCategoryPlan extends BaseModel
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'expense_category_id',
        'academic_year_id',
        'expected_amount',
        'from_date',
        'to_date',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expected_amount' => 'decimal:2',
        'from_date' => 'date',
        'to_date' => 'date',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Live sum of approved line-item totals for this plan's category, within
     * this plan's own date range - never cached. This is what makes editing
     * a plan's amount or timeline "adjust the graph simultaneously": every
     * chart/report calls this fresh, there is no snapshot anywhere to go stale.
     */
    public function actualSpent(): float
    {
        return (float) ExpenseLineItem::query()
            ->where('status', 'approved')
            ->whereHas('submission', function ($query) {
                $query->where('expense_category_id', $this->expense_category_id)
                    ->whereBetween('transaction_date', [$this->from_date, $this->to_date]);
            })
            ->sum('line_total');
    }
}
