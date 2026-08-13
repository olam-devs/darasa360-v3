<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExpenseCategory extends BaseModel
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'status',
        'show_budget_to_others',
        'proposed_by',
        'decided_by',
        'decided_at',
        'decision_note',
        'is_system',
    ];

    protected $casts = [
        'show_budget_to_others' => 'boolean',
        'is_system'             => 'boolean',
        'decided_at'            => 'datetime',
    ];

    public function plans()
    {
        return $this->hasMany(ExpenseCategoryPlan::class);
    }

    public function submissions()
    {
        return $this->hasMany(ExpenseSubmission::class);
    }

    /**
     * The plan currently in effect "now" (today falls within its date
     * range), if any. A category can have plans from different periods;
     * this is the one relevant for "current" chart/report views.
     */
    public function currentPlan()
    {
        return $this->plans()
            ->whereDate('from_date', '<=', now())
            ->whereDate('to_date', '>=', now())
            ->latest('id')
            ->first();
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }
}
