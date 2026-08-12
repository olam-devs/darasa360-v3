<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\ExpenseCategory;
use App\Models\ExpenseCategoryPlan;
use App\Models\ExpenseLineItem;
use App\Support\DateBucketer;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpenseCategoryPlanController extends Controller
{
    public function show(Request $request, ExpenseCategory $category)
    {
        return response()->json([
            'plans' => $category->plans()->with('academicYear')->orderByDesc('from_date')->get(),
        ]);
    }

    /**
     * Save monthly budgets for a category+year. One plan record per month.
     * Replaces all existing plans for that category+year atomically.
     * Only months with amount > 0 are stored; omitting a month clears it.
     */
    public function storeMonthly(Request $request, ExpenseCategory $category)
    {
        $validated = $request->validate([
            'academic_year_id'                 => 'required|exists:academic_years,id',
            'months'                           => 'present|array',
            'months.*.month_key'               => 'required_with:months|date_format:Y-m',
            'months.*.expected_amount'         => 'required_with:months|numeric|min:0',
        ]);

        DB::transaction(function () use ($category, $validated, $request) {
            $category->plans()->where('academic_year_id', $validated['academic_year_id'])->delete();

            foreach ($validated['months'] as $month) {
                $amount = (float) $month['expected_amount'];
                if ($amount <= 0) {
                    continue;
                }
                [$year, $mon] = explode('-', $month['month_key']);
                $firstDay = "{$year}-{$mon}-01";
                $lastDay  = date('Y-m-t', strtotime($firstDay));

                $category->plans()->create([
                    'academic_year_id' => $validated['academic_year_id'],
                    'expected_amount'  => $amount,
                    'from_date'        => $firstDay,
                    'to_date'          => $lastDay,
                    'created_by'       => $request->user()->id,
                ]);
            }
        });

        return response()->json(['message' => 'Budget plans saved.']);
    }

    public function update(Request $request, ExpenseCategoryPlan $plan)
    {
        $validated = $request->validate([
            'expected_amount' => 'required|numeric|min:0',
            'from_date'       => 'required|date',
            'to_date'         => 'required|date|after_or_equal:from_date',
        ]);

        $plan->update($validated + ['updated_by' => $request->user()->id]);

        return response()->json(['plan' => $plan]);
    }

    /**
     * Expected-vs-actual chart data.
     *
     * planned_per_bucket is now an array (one entry per timeline bucket) — 0
     * where no plan exists for that month, so the Budget bar only appears for
     * months the main accountant has actually budgeted.
     *
     * monthly_plans contains ALL plans for the selected category+year
     * (unfiltered by date) so the planning panel can pre-fill every month.
     */
    public function chart(Request $request)
    {
        $validated = $request->validate([
            'category_id'      => 'nullable|exists:expense_categories,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'from_date'        => 'nullable|date',
            'to_date'          => 'nullable|date|after_or_equal:from_date',
        ]);

        $categoryId = $validated['category_id'] ?? null;
        $isMain     = (bool) ($request->user()->is_main_accountant ?? false);

        // Default to the academic year's actual date span (not just Jan 1 of the current year).
        $academicYear = AcademicYear::find($validated['academic_year_id']);
        $fromDate = $validated['from_date']
            ?? ($academicYear ? $academicYear->start_date->toDateString() : now()->startOfYear()->toDateString());
        $toDate = $validated['to_date']
            ?? ($academicYear
                ? min($academicYear->end_date->toDateString(), now()->toDateString())
                : now()->toDateString());

        // --- Budget (main accountant only) ---
        $expectedAmount  = 0.0;
        $monthlyPlans    = [];
        $plansByMonth    = collect();

        if ($isMain) {
            // All plans for the chart range (for planned_per_bucket alignment).
            $plansByMonth = ExpenseCategoryPlan::where('academic_year_id', $validated['academic_year_id'])
                ->when($categoryId, fn ($q) => $q->where('expense_category_id', $categoryId))
                ->whereDate('from_date', '<=', $toDate)
                ->whereDate('to_date',   '>=', $fromDate)
                ->get(['from_date', 'expected_amount'])
                ->keyBy(fn ($p) => date('Y-m', strtotime($p->from_date)));

            $expectedAmount = (float) $plansByMonth->sum('expected_amount');

            // All plans for the full year — for the planning panel pre-fill.
            if ($categoryId) {
                $monthlyPlans = ExpenseCategoryPlan::where('expense_category_id', $categoryId)
                    ->where('academic_year_id', $validated['academic_year_id'])
                    ->get(['from_date', 'expected_amount'])
                    ->map(fn ($p) => [
                        'month_key'       => date('Y-m', strtotime($p->from_date)),
                        'expected_amount' => (float) $p->expected_amount,
                    ])
                    ->values()
                    ->all();
            }
        }

        // --- Actual spend ---
        $lineItems = ExpenseLineItem::query()
            ->where('status', 'approved')
            ->whereHas('submission', function ($q) use ($categoryId, $validated, $fromDate, $toDate) {
                $q->where('academic_year_id', $validated['academic_year_id'])
                    ->whereBetween('transaction_date', [$fromDate, $toDate]);
                if ($categoryId) {
                    $q->where('expense_category_id', $categoryId);
                }
            })
            ->with('submission:id,transaction_date')
            ->get(['id', 'expense_submission_id', 'line_total']);

        $actualAmount = (float) $lineItems->sum('line_total');

        $timeline = DateBucketer::bucket(
            $fromDate,
            $toDate,
            $lineItems,
            fn (ExpenseLineItem $line) => $line->submission?->transaction_date,
            fn (ExpenseLineItem $line) => $line->line_total,
        );

        // --- planned_per_bucket as array aligned to timeline ---
        $isDaily = DateBucketer::isDaily($fromDate, $toDate);

        if ($isMain && $plansByMonth->isNotEmpty() && !$isDaily) {
            $plannedPerBucket = [];
            $cursor = new DateTime($fromDate);
            $end    = new DateTime($toDate);
            while ($cursor <= $end) {
                $key              = $cursor->format('Y-m');
                $plan             = $plansByMonth->get($key);
                $plannedPerBucket[] = $plan ? (float) $plan->expected_amount : 0;
                $cursor->modify('+1 month');
            }
        } else {
            // Daily view or no plans: no Budget bars shown.
            $plannedPerBucket = array_fill(0, count($timeline), 0);
        }

        return response()->json([
            'expected_amount'   => $expectedAmount,
            'actual_amount'     => $actualAmount,
            'timeline'          => $timeline,
            'planned_per_bucket' => $plannedPerBucket,
            'monthly_plans'     => $monthlyPlans,
        ]);
    }
}
