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
     * Save monthly budgets for a category+year. Supports both single-month
     * entries and multi-month groups (consecutive months sharing one budget).
     * Replaces all existing plans for that category+year atomically.
     */
    public function storeMonthly(Request $request, ExpenseCategory $category)
    {
        $validated = $request->validate([
            'academic_year_id'                 => 'required|exists:academic_years,id',
            'months'                           => 'present|array',
            'months.*.month_key'               => 'required_with:months|date_format:Y-m',
            'months.*.expected_amount'         => 'required_with:months|numeric|min:0',
            'groups'                           => 'present|array',
            'groups.*.from_month'              => 'required_with:groups|date_format:Y-m',
            'groups.*.to_month'                => 'required_with:groups|date_format:Y-m',
            'groups.*.expected_amount'         => 'required_with:groups|numeric|min:0',
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

            foreach (($validated['groups'] ?? []) as $group) {
                $amount = (float) $group['expected_amount'];
                if ($amount <= 0) {
                    continue;
                }
                [$fy, $fm] = explode('-', $group['from_month']);
                [$ty, $tm] = explode('-', $group['to_month']);
                $firstDay = "{$fy}-{$fm}-01";
                $lastDay  = date('Y-m-t', strtotime("{$ty}-{$tm}-01"));
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
     * When the selected category has any multi-month group plans, the timeline
     * collapses those months into a single bucket so the chart shows one bar
     * pair per group instead of individual monthly bars.
     *
     * monthly_plans contains ALL plans for the category+year for the planning
     * panel, tagged with type='month'|'group' so the UI can reconstruct groups.
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

        $academicYear = AcademicYear::find($validated['academic_year_id']);
        $fromDate = $validated['from_date']
            ?? ($academicYear ? $academicYear->start_date->toDateString() : now()->startOfYear()->toDateString());
        $toDate = $validated['to_date']
            ?? ($academicYear
                ? min($academicYear->end_date->toDateString(), now()->toDateString())
                : now()->toDateString());

        // --- Budget (main accountant only) ---
        $expectedAmount = 0.0;
        $monthlyPlans   = [];
        $allPlans       = collect();

        if ($isMain) {
            $allPlans = ExpenseCategoryPlan::where('academic_year_id', $validated['academic_year_id'])
                ->when($categoryId, fn ($q) => $q->where('expense_category_id', $categoryId))
                ->get()
                ->sortBy(fn ($p) => $p->from_date->toDateString());

            $expectedAmount = (float) $allPlans
                ->filter(fn ($p) => $p->from_date->toDateString() <= $toDate
                                 && $p->to_date->toDateString()   >= $fromDate)
                ->sum('expected_amount');

            if ($categoryId) {
                $monthlyPlans = $allPlans->map(function ($p) {
                    $fromMonth = $p->from_date->format('Y-m');
                    $toMonth   = $p->to_date->format('Y-m');
                    return $fromMonth === $toMonth
                        ? ['type' => 'month', 'month_key' => $fromMonth, 'expected_amount' => (float) $p->expected_amount]
                        : ['type' => 'group', 'from_month' => $fromMonth, 'to_month' => $toMonth, 'expected_amount' => (float) $p->expected_amount];
                })->values()->all();
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

        $isDaily = DateBucketer::isDaily($fromDate, $toDate);

        $hasGroups = $isMain && $allPlans->contains(
            fn ($p) => $p->from_date->format('Y-m') !== $p->to_date->format('Y-m')
        );

        if ($isDaily) {
            $timeline         = DateBucketer::bucket($fromDate, $toDate, $lineItems,
                fn ($l) => $l->submission?->transaction_date, fn ($l) => $l->line_total);
            $plannedPerBucket = array_fill(0, count($timeline), 0);
        } elseif ($hasGroups) {
            [$timeline, $plannedPerBucket] = $this->buildGroupedBuckets($fromDate, $toDate, $allPlans, $lineItems);
        } else {
            // Standard monthly bucketing (no groups)
            $plansByMonth = $allPlans->keyBy(fn ($p) => $p->from_date->format('Y-m'));
            $timeline = DateBucketer::bucket($fromDate, $toDate, $lineItems,
                fn ($l) => $l->submission?->transaction_date, fn ($l) => $l->line_total);
            $plannedPerBucket = [];
            $cursor = new DateTime($fromDate);
            $end    = new DateTime($toDate);
            while ($cursor <= $end) {
                $plan = $plansByMonth->get($cursor->format('Y-m'));
                $plannedPerBucket[] = $plan ? (float) $plan->expected_amount : 0;
                $cursor->modify('+1 month');
            }
        }

        return response()->json([
            'expected_amount'    => $expectedAmount,
            'actual_amount'      => $actualAmount,
            'timeline'           => $timeline,
            'planned_per_bucket' => $plannedPerBucket,
            'monthly_plans'      => $monthlyPlans,
        ]);
    }

    private function buildGroupedBuckets(string $fromDate, string $toDate, $allPlans, $lineItems): array
    {
        $planRanges = $allPlans->map(fn ($p) => [
            'from_month' => $p->from_date->format('Y-m'),
            'to_month'   => $p->to_date->format('Y-m'),
            'from_date'  => $p->from_date->toDateString(),
            'to_date'    => $p->to_date->toDateString(),
            'amount'     => (float) $p->expected_amount,
            'is_group'   => $p->from_date->format('Y-m') !== $p->to_date->format('Y-m'),
        ])->keyBy('from_month')->all();

        $buckets          = [];
        $coveredUntilMonth = null;
        $cursor = new DateTime($fromDate);
        $end    = new DateTime($toDate);

        while ($cursor <= $end) {
            $monthKey = $cursor->format('Y-m');

            if ($coveredUntilMonth && $monthKey <= $coveredUntilMonth) {
                $cursor->modify('+1 month');
                continue;
            }

            $plan = $planRanges[$monthKey] ?? null;

            if ($plan && $plan['is_group']) {
                $coveredUntilMonth = $plan['to_month'];
                $buckets[] = [
                    'label'   => $this->groupBucketLabel($plan['from_date'], $plan['to_date']),
                    'from'    => $plan['from_date'],
                    'to'      => $plan['to_date'],
                    'planned' => $plan['amount'],
                    'actual'  => 0.0,
                ];
            } else {
                $monthStart = $cursor->format('Y-m') . '-01';
                $monthEnd   = date('Y-m-t', strtotime($monthStart));
                $buckets[] = [
                    'label'   => $cursor->format('M Y'),
                    'from'    => $monthStart,
                    'to'      => $monthEnd,
                    'planned' => $plan ? $plan['amount'] : 0.0,
                    'actual'  => 0.0,
                ];
            }

            $cursor->modify('+1 month');
        }

        foreach ($lineItems as $item) {
            $txDate = $item->submission?->transaction_date;
            if (! $txDate) {
                continue;
            }
            $txStr = is_string($txDate) ? $txDate : $txDate->toDateString();
            foreach ($buckets as $i => $bucket) {
                if ($txStr >= $bucket['from'] && $txStr <= $bucket['to']) {
                    $buckets[$i]['actual'] += (float) $item->line_total;
                    break;
                }
            }
        }

        return [
            array_map(fn ($b) => ['label' => $b['label'], 'amount' => $b['actual']], $buckets),
            array_map(fn ($b) => $b['planned'], $buckets),
        ];
    }

    private function groupBucketLabel(string $fromDate, string $toDate): string
    {
        $from = new DateTime($fromDate);
        $to   = new DateTime($toDate);
        return $from->format('Y') === $to->format('Y')
            ? $from->format('M') . '–' . $to->format('M Y')
            : $from->format('M Y') . '–' . $to->format('M Y');
    }
}
