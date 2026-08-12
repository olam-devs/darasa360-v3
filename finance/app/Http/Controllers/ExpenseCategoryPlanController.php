<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Models\ExpenseCategoryPlan;
use App\Models\ExpenseLineItem;
use App\Support\DateBucketer;
use Illuminate\Http\Request;

class ExpenseCategoryPlanController extends Controller
{
    /**
     * A category's plans (budget over a date range). Blocked for non-main
     * accountants if the category's show_budget_to_others is off.
     */
    public function show(Request $request, ExpenseCategory $category)
    {
        if (!$this->canViewBudget($request, $category)) {
            return response()->json(['error' => 'The main accountant has not made this category\'s budget visible to other accountants.'], 403);
        }

        return response()->json([
            'plans' => $category->plans()->with('academicYear')->orderByDesc('from_date')->get(),
        ]);
    }

    public function store(Request $request, ExpenseCategory $category)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'expected_amount' => 'required|numeric|min:0',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $plan = $category->plans()->create($validated + ['created_by' => $request->user()->id]);

        return response()->json(['plan' => $plan], 201);
    }

    public function update(Request $request, ExpenseCategoryPlan $plan)
    {
        $validated = $request->validate([
            'expected_amount' => 'required|numeric|min:0',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $plan->update($validated + ['updated_by' => $request->user()->id]);

        return response()->json(['plan' => $plan]);
    }

    public function toggleVisibility(ExpenseCategory $category)
    {
        $category->update(['show_budget_to_others' => !$category->show_budget_to_others]);

        return response()->json(['category' => $category]);
    }

    /**
     * Expected-vs-actual chart data. Recomputed live on every call - never
     * cached - so editing a plan's amount/timeline is reflected immediately
     * on the next fetch, per explicit request.
     */
    public function chart(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'nullable|exists:expense_categories,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $categoryId = $validated['category_id'] ?? null;
        $isMain = (bool) ($request->user()->is_main_accountant ?? false);

        $fromDate = $validated['from_date'] ?? now()->startOfYear()->toDateString();
        $toDate = $validated['to_date'] ?? now()->toDateString();

        // Budget figures are main-accountant-only.
        $expectedAmount = 0.0;
        $currentPlan = null;
        if ($isMain) {
            if ($categoryId) {
                $expectedAmount = (float) ExpenseCategoryPlan::where('expense_category_id', $categoryId)
                    ->where('academic_year_id', $validated['academic_year_id'])
                    ->whereDate('from_date', '<=', $toDate)
                    ->whereDate('to_date', '>=', $fromDate)
                    ->sum('expected_amount');

                // Return plan details so the edit form can auto-populate.
                $currentPlan = ExpenseCategoryPlan::where('expense_category_id', $categoryId)
                    ->where('academic_year_id', $validated['academic_year_id'])
                    ->orderByDesc('from_date')
                    ->first(['id', 'expected_amount', 'from_date', 'to_date', 'academic_year_id']);
            } else {
                $expectedAmount = (float) ExpenseCategoryPlan::where('academic_year_id', $validated['academic_year_id'])
                    ->whereDate('from_date', '<=', $toDate)
                    ->whereDate('to_date', '>=', $fromDate)
                    ->sum('expected_amount');
            }
        }

        $lineItemsQuery = ExpenseLineItem::query()
            ->where('status', 'approved')
            ->whereHas('submission', function ($query) use ($categoryId, $validated, $fromDate, $toDate) {
                $query->where('academic_year_id', $validated['academic_year_id'])
                    ->whereBetween('transaction_date', [$fromDate, $toDate]);
                if ($categoryId) {
                    $query->where('expense_category_id', $categoryId);
                }
            })
            ->with('submission:id,transaction_date');

        $lineItems = $lineItemsQuery->get(['id', 'expense_submission_id', 'line_total']);
        $actualAmount = (float) $lineItems->sum('line_total');

        $timeline = DateBucketer::bucket(
            $fromDate,
            $toDate,
            $lineItems,
            fn (ExpenseLineItem $line) => $line->submission?->transaction_date,
            fn (ExpenseLineItem $line) => $line->line_total,
        );

        // Distribute expected evenly across timeline buckets for the planned line.
        $bucketCount = count($timeline);
        $plannedPerBucket = ($isMain && $expectedAmount > 0 && $bucketCount > 0)
            ? round($expectedAmount / $bucketCount, 2)
            : 0;

        return response()->json([
            'expected_amount' => $expectedAmount,
            'actual_amount' => $actualAmount,
            'timeline' => $timeline,
            'planned_per_bucket' => $plannedPerBucket,
            'current_plan' => $currentPlan,
        ]);
    }

    protected function canViewBudget(Request $request, ExpenseCategory $category): bool
    {
        $user = $request->user();

        return $category->show_budget_to_others || (bool) ($user->is_main_accountant ?? false);
    }
}
