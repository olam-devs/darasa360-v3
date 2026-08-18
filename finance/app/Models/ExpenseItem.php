<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;

class ExpenseItem extends BaseModel
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'unit_type',
        'created_by',
    ];

    public function lineItems()
    {
        return $this->hasMany(ExpenseLineItem::class);
    }

    /**
     * Last $limit approved occurrences of this item, latest-first, each with
     * the date/category/unit price it was recorded at - the price-creep
     * check the main accountant sees while reviewing a submission. Ordered
     * at the DB level (join on the parent submission's transaction_date),
     * not fetched-then-sorted, so this stays cheap even for a heavily-used item.
     */
    public function priceHistory(int $limit = 10): Collection
    {
        return $this->lineItems()
            ->where('expense_line_items.status', 'approved')
            ->join('expense_submissions', 'expense_submissions.id', '=', 'expense_line_items.expense_submission_id')
            ->join('expense_categories', 'expense_categories.id', '=', 'expense_submissions.expense_category_id')
            ->orderByDesc('expense_submissions.transaction_date')
            ->limit($limit)
            ->get([
                'expense_submissions.transaction_date as date',
                'expense_categories.name as category',
                'expense_line_items.unit_price',
                'expense_line_items.quantity',
                'expense_line_items.unit_type_snapshot as unit',
            ])
            ->map(fn ($row) => [
                'date'       => $row->date,
                'category'   => $row->category,
                'unit_price' => (float) $row->unit_price,
                'quantity'   => (float) $row->quantity,
                'unit'       => $row->unit ?? '',
            ]);
    }
}
