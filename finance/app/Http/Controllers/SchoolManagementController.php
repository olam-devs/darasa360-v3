<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookFeeCategory;
use App\Models\Central\ActivityLog;
use App\Models\Central\School;
use App\Models\Central\SchoolAccountant;
use App\Models\ExpenseCategory;
use App\Models\ExpenseCategoryPlan;
use App\Models\ExpenseLineItem;
use App\Models\ExpenseSubmission;
use App\Models\PayrollEntry;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\StaffDeduction;
use App\Services\AccountantKpiService;
use App\Services\ExpenseReviewService;
use App\Services\PayrollPaymentService;
use Illuminate\Support\Str;
use App\Services\ManagementQuarterAnalytics;
use App\Services\SchoolManagementAccess;
use App\Services\TenantDatabaseManager;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SchoolManagementController extends Controller
{
    public function __construct(protected SchoolManagementAccess $access, protected AccountantKpiService $kpis) {}

    private function period(Request $request): array
    {
        $request->validate(['from'=>'nullable|date_format:Y-m-d', 'to'=>'nullable|date_format:Y-m-d',
            'year'=>'nullable|integer|between:2000,2100','quarter'=>'nullable|integer|between:1,4',
            'quarters'=>'nullable|array|max:4','quarters.*'=>'integer|between:1,4',
            'period_mode'=>'nullable|in:quarter,custom']);
        $today = CarbonImmutable::now('Africa/Dar_es_Salaam');
        [$currentYear, $currentQuarter] = ManagementQuarterAnalytics::quarterOf($today);
        $year = (int)($request->input('year') ?: $currentYear);
        // Ticked quarters form one continuous span (ticking Q1 and Q3 includes Q2); "quarter" alone still works.
        $ticked = collect($request->input('quarters', []))->map(fn ($q) => (int)$q)->filter();
        $quarter = $ticked->isNotEmpty() ? $ticked->min() : (int)($request->input('quarter') ?: $currentQuarter);
        $quarterTo = $ticked->isNotEmpty() ? $ticked->max() : $quarter;
        $periodMode = $request->input('period_mode') ?: ($request->filled('from') || $request->filled('to') ? 'custom' : 'quarter');
        $start = ManagementQuarterAnalytics::range($year, $quarter)[0];
        $end = ManagementQuarterAnalytics::range($year, $quarterTo)[1];
        $from = $periodMode === 'quarter' ? $start->toDateString() : ($request->input('from') ?: $start->toDateString());
        $to = $periodMode === 'quarter' ? $end->toDateString() : ($request->input('to') ?: $end->toDateString());
        validator(['from'=>$from,'to'=>$to], ['from'=>'required|date','to'=>'required|date|after_or_equal:from'])->validate();
        abort_if(CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > 730, 422, 'Choose a period of two years or less.');
        $qLabel = ManagementQuarterAnalytics::spanLabel($quarter, $quarterTo);
        $periodLabel = $periodMode === 'quarter' ? "{$qLabel} {$year}" : "{$from} to {$to}";
        return compact('from','to','year','quarter','quarterTo','qLabel','periodMode','periodLabel');
    }

    public static function shortName(string $name): string
    {
        return Str::title(Str::afterLast($name, ' - '));
    }

    /** Sidebar data: every granted school with its pending-approval count. */
    private function nav(Request $request, ?int $selected = null): array
    {
        $schools = [];
        foreach ($this->access->schools($request->user()) as $school) {
            try {
                $pending = app(TenantDatabaseManager::class)->executeForSchool($school, fn () => DB::connection('tenant')->table('expense_submissions')->where('status', 'pending')->count());
            } catch (\Throwable $e) {
                report($e);
                $pending = 0;
            }
            $schools[] = ['id' => $school->id, 'name' => $school->name, 'short' => self::shortName($school->name), 'pending' => $pending];
        }
        return ['schools' => $schools, 'selected' => $selected, 'pending' => array_sum(array_column($schools, 'pending'))];
    }

    private function selectedSchool(Request $request): ?School
    {
        $request->validate(['school' => 'nullable|integer|min:1']);
        return $request->filled('school') ? $this->access->school($request->user(), (int)$request->input('school')) : null;
    }

    public function index(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $settings = (object)['school_name'=>'School management · all schools','logo_path'=>null];
        ['from'=>$from,'to'=>$to,'year'=>$year,'quarter'=>$quarter,'quarterTo'=>$quarterTo,'qLabel'=>$qLabel,'periodMode'=>$periodMode,'periodLabel'=>$periodLabel] = $this->period($request);
        $selectedSchool = $this->selectedSchool($request);
        $schools = $selectedSchool ? collect([$selectedSchool]) : $this->access->schools($request->user());
        $nav = $this->nav($request, $selectedSchool?->id);
        $yearOptions = collect([$year,now('Africa/Dar_es_Salaam')->year]);
        $snapshots = []; $kpiRows = [];
        foreach ($schools as $school) {
            try {
                $snapshots[] = app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($school, $from, $to, $year, $quarter, $quarterTo, $periodMode, &$yearOptions) {
                    $db = DB::connection('tenant');
                    $yearOptions = $yearOptions->merge($db->table('academic_years')->pluck('name')->filter(fn($name)=>preg_match('/^20\d{2}$|^2100$/',(string)$name))->map(fn($name)=>(int)$name));
                    $quarterReport = app(ManagementQuarterAnalytics::class)->report($year,$quarter,$quarterTo);
                    $receipts = $this->kpis->receipts()->whereBetween('date', [$from, $to]);
                    $days = (int)CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) + 1;
                    $previousTo = CarbonImmutable::parse($from)->subDay();
                    // One quarter compares with the quarter before; a span or custom dates with the same number of days before.
                    $previousFrom = $periodMode === 'quarter' && $quarter === $quarterTo ? ManagementQuarterAnalytics::range(...ManagementQuarterAnalytics::quarterOf($previousTo))[0] : $previousTo->subDays($days-1);
                    $previous = (float)$this->kpis->receipts()->whereBetween('date', [$previousFrom->toDateString(), $previousTo->toDateString()])->sum('debit');
                    $expenses = (float)$db->table('expense_submissions')->whereIn('status',['approved','partially_approved'])
                        ->whereBetween('transaction_date',[$from,$to])->sum('total_amount');
                    // Fee figures follow the selected year and quarter (each fee's assigned quarter).
                    $yearIds = $db->table('academic_years')->where('name', (string)$year)->pluck('id');
                    $earlierYearIds = $db->table('academic_years')->get(['id', 'name'])->filter(fn ($y) => is_numeric($y->name) && (int)$y->name < $year)->pluck('id');
                    $inQuarter = fn () => $db->table('particular_student as ps')->whereIn('ps.academic_year_id', $yearIds)->whereBetween('ps.quarter', [$quarter, $quarterTo]);
                    $owed = $inQuarter()->selectRaw('COALESCE(SUM(GREATEST(0,ps.sales-ps.credit)),0) as outstanding, COALESCE(SUM(ps.sales),0) as billed, COALESCE(SUM(ps.credit),0) as paid')->first();
                    $todayDate = now('Africa/Dar_es_Salaam')->toDateString();
                    $quarterOverdue = (float)$inQuarter()->whereNotNull('ps.deadline')->whereDate('ps.deadline', '<', $todayDate)->selectRaw('COALESCE(SUM(GREATEST(0,ps.sales-ps.credit)),0) as t')->value('t');
                    // Older debt stays visible: unpaid fees of earlier quarters this year and of earlier years.
                    $earlierUnpaid = (float)$db->table('particular_student as ps')->where(fn ($q) => $q->whereIn('ps.academic_year_id', $earlierYearIds)
                            ->orWhere(fn ($q) => $q->whereIn('ps.academic_year_id', $yearIds)->where('ps.quarter', '<', $quarter)))
                        ->selectRaw('COALESCE(SUM(GREATEST(0,ps.sales-ps.credit)),0) as t')->value('t');
                    $pending = $db->table('expense_submissions')->where('status','pending');
                    // Book balance at the end of the period, or today while the period is still running.
                    $balanceDate = min($to, $todayDate);
                    $books = $db->table('books')->where('is_active',true)->get(['id','opening_balance']);
                    $bookMovement = $db->table('vouchers')->whereIn('book_id',$books->pluck('id'))->whereNull('voided_at')->whereDate('date', '<=', $balanceDate)->selectRaw('COALESCE(SUM(debit-credit),0) as net')->value('net');
                    $trend = (clone $receipts)->selectRaw('DATE(date) as day, SUM(debit) as amount')->groupBy('day')->orderBy('day')->get();
                    $topDebtors = $inQuarter()->join('students as s','s.id','=','ps.student_id')
                        ->select('s.name')->selectRaw('SUM(GREATEST(0,ps.sales-ps.credit)) as balance')->groupBy('s.id','s.name')->having('balance','>',0)->orderByDesc('balance')->limit(5)->get();
                    // Each active book with its all-time balance and the money in/out during the selected period.
                    $bookRows = $db->table('books as b')->where('b.is_active',true)
                        ->leftJoin('vouchers as v', fn($j) => $j->on('v.book_id','=','b.id')->whereNull('v.voided_at'))
                        ->groupBy('b.id','b.name','b.bank_account_number','b.is_cash_book','b.opening_balance')
                        ->selectRaw('b.id, b.name, b.bank_account_number, b.is_cash_book, b.opening_balance,
                            COALESCE(SUM(CASE WHEN v.date <= ? THEN v.debit-v.credit ELSE 0 END),0) as net,
                            COALESCE(SUM(CASE WHEN v.date < ? THEN v.debit-v.credit ELSE 0 END),0) as net_before,
                            COALESCE(SUM(CASE WHEN v.date BETWEEN ? AND ? THEN v.debit ELSE 0 END),0) as period_in,
                            COALESCE(SUM(CASE WHEN v.date BETWEEN ? AND ? THEN v.credit ELSE 0 END),0) as period_out', [$balanceDate,$from,$from,$to,$from,$to])
                        ->orderBy('b.name')->get()
                        // Balances follow the period: at its start, and at its end (or today while it is running).
                        ->map(fn($b) => ['name'=>$b->name, 'account'=>$b->bank_account_number ? preg_replace('/\s+/','',$b->bank_account_number) : null,
                            'cash'=>(bool)$b->is_cash_book, 'balance'=>(float)$b->opening_balance+(float)$b->net, 'start'=>(float)$b->opening_balance+(float)$b->net_before,
                            'in'=>(float)$b->period_in, 'out'=>(float)$b->period_out])->all();
                    // Month-by-month figures for the selected calendar year (charts).
                    $monthly = fn($rows) => collect(range(1,12))->mapWithKeys(fn($m) => [$m => (float)($rows[$m] ?? 0)])->all();
                    $monthlyCollections = $monthly($this->kpis->receipts()->whereYear('date',$year)->selectRaw('MONTH(date) as m, SUM(debit) as t')->groupBy('m')->pluck('t','m'));
                    $monthlyExpenses = $monthly($db->table('expense_submissions')->whereIn('status',['approved','partially_approved'])
                        ->whereYear('transaction_date',$year)->selectRaw('MONTH(transaction_date) as m, SUM(total_amount) as t')->groupBy('m')->pluck('t','m'));
                    return ['id'=>$school->id,'name'=>$school->name,'error'=>false,
                        'collections'=>(float)(clone $receipts)->sum('debit'),'previous'=>$previous,'expenses'=>$expenses,
                        'students'=>$db->table('students')->where('status','active')->count(),
                        'outstanding'=>(float)$owed->outstanding, 'billed'=>(float)$owed->billed,'paid'=>(float)$owed->paid,
                        'overdue'=>$quarterOverdue, 'earlier_unpaid'=>$earlierUnpaid, 'balance_date'=>$balanceDate,
                        'pending'=>(clone $pending)->count(), 'oldest_pending'=>(clone $pending)->min('created_at'),
                        'pending_items'=>$db->table('expense_submissions as e')->leftJoin('expense_categories as c','c.id','=','e.expense_category_id')
                            ->leftJoin('books as bk','bk.id','=','e.book_id')
                            ->where('e.status','pending')->orderBy('e.created_at')->limit(100)
                            ->get(['e.id','e.submission_number','e.title','e.total_amount','e.transaction_date','e.created_at','c.name as category_name','bk.name as book_name']),
                        // Decisions made in the selected period (by decision date), newest first.
                        'decided_items'=>$db->table('expense_submissions as e')->leftJoin('expense_categories as c','c.id','=','e.expense_category_id')
                            ->leftJoin('books as bk','bk.id','=','e.book_id')
                            ->whereIn('e.status',['approved','partially_approved','denied','cancelled'])
                            ->whereBetween('e.decided_at',[$from.' 00:00:00',$to.' 23:59:59'])->orderByDesc('e.decided_at')->limit(300)
                            ->get(['e.id','e.submission_number','e.title','e.total_amount','e.status','e.decided_at','e.decided_by','e.transaction_date','c.name as category_name','bk.name as book_name']),
                        'book_balance'=>(float)$books->sum('opening_balance')+(float)$bookMovement,
                        'advance'=>(float)$db->table('students')->sum('advance_balance'),
                        'trend'=>$trend,'debtors'=>$topDebtors,'quarter_report'=>$quarterReport,
                        'books'=>$bookRows,'monthly_collections'=>$monthlyCollections,'monthly_expenses'=>$monthlyExpenses,
                        'budget'=>$this->budgetVsActual($db, $from, $to, $periodMode === 'quarter' ? [$year, $quarter, $quarterTo] : null)];
                });
                $kpiRows = array_merge($kpiRows, $this->kpis->rows($school, $from, $to));
            } catch (\Throwable $e) {
                report($e);
                // Do not present unavailable school data as zero or leak database errors.
                $snapshots = array_values(array_filter($snapshots, fn($s) => $s['id'] !== $school->id));
                $snapshots[] = ['id'=>$school->id,'name'=>$school->name,'error'=>true];
            }
        }
        $yearOptions = $yearOptions->unique()->sortDesc()->values();
        $quarterLabels = ManagementQuarterAnalytics::LABELS;
        $metrics = AccountantKpiService::METRICS;
        $bookGroups = $this->groupBooks($snapshots);
        $deciderNames = SchoolAccountant::whereIn('id', collect($snapshots)->where('error', false)->flatMap(fn ($s) => collect($s['decided_items'])->pluck('decided_by'))->filter()->unique())->pluck('name', 'id');
        $budgetRows = $this->groupBudgets($snapshots);
        return view('admin.accountant.management.overview', compact('settings','schools','snapshots','kpiRows','from','to','metrics','year','quarter','quarterTo','qLabel','periodMode','periodLabel','yearOptions','quarterLabels','nav','selectedSchool','bookGroups','deciderNames','budgetRows'));
    }

    /**
     * Budget against approved spending per expense category, for the current tenant. Each budget plan that
     * overlaps the period is compared with approved spending inside the plan's own dates (budgets have their
     * own windows, not quarters). Spending in the period with no overlapping budget is listed as unbudgeted.
     */
    private function budgetVsActual($db, string $from, string $to, ?array $quarterSpan = null): array
    {
        $approved = fn () => $db->table('expense_submissions')->whereIn('status', ['approved', 'partially_approved']);
        $plans = $db->table('expense_category_plans as p')->join('expense_categories as c', 'c.id', '=', 'p.expense_category_id')
            ->where('p.from_date', '<=', $to)->where('p.to_date', '>=', $from)->orderBy('c.name')
            ->get(['p.id', 'p.expense_category_id', 'c.name', 'p.expected_amount', 'p.from_date', 'p.to_date']);
        // With quarters selected, keep only budgets whose home quarter (the quarter their dates mostly cover) is
        // selected, so Q4 does not show a Q3 budget whose window ends a few days into Q4.
        if ($quarterSpan) {
            [$year, $q1, $q2] = $quarterSpan;
            $plans = $plans->filter(function ($p) use ($year, $q1, $q2) {
                [$homeYear, $homeQuarter] = $this->homeQuarter($p->from_date, $p->to_date);
                return $homeYear === $year && $homeQuarter >= $q1 && $homeQuarter <= $q2;
            })->values();
        }
        // Salaries paid through Payroll count against the salary category's budget, by each payment's budget term.
        $salaryCat = $this->salaryCategoryId($db);
        $payrollUsed = $salaryCat ? $this->payrollUsageByPlan($db, $db->table('expense_category_plans')->where('expense_category_id', $salaryCat)->get(['id', 'from_date', 'to_date'])) : [];
        // Salary spending recorded as expenses under any salary-type category (e.g. "Payroll") also uses the salary budget.
        $salaryLike = $salaryCat ? $db->table('expense_categories')->whereRaw("name REGEXP 'salar|wage|mshahara|payroll'")->pluck('id')->push($salaryCat)->unique()->all() : [];
        $rows = [];
        foreach ($plans as $p) {
            $rows[$p->expense_category_id] ??= ['category' => $p->name, 'budget' => 0.0, 'spent' => 0.0, 'payroll' => 0.0, 'windows' => []];
            $rows[$p->expense_category_id]['budget'] += (float)$p->expected_amount;
            $cats = (int)$p->expense_category_id === $salaryCat ? $salaryLike : [$p->expense_category_id];
            $rows[$p->expense_category_id]['spent'] += (float)$approved()->whereIn('expense_category_id', $cats)
                ->whereBetween('transaction_date', [$p->from_date, $p->to_date])->sum('total_amount');
            if ((int)$p->expense_category_id === $salaryCat && isset($payrollUsed[$p->id])) {
                $rows[$p->expense_category_id]['spent'] += $payrollUsed[$p->id];
                $rows[$p->expense_category_id]['payroll'] += $payrollUsed[$p->id];
            }
            $rows[$p->expense_category_id]['windows'][] = $p->from_date.' – '.$p->to_date;
        }
        $unbudgeted = $db->table('expense_submissions as e')->join('expense_categories as c', 'c.id', '=', 'e.expense_category_id')
            ->whereIn('e.status', ['approved', 'partially_approved'])->whereBetween('e.transaction_date', [$from, $to])
            ->whereNotIn('e.expense_category_id', array_merge(array_keys($rows), isset($rows[$salaryCat]) ? $salaryLike : []))
            // Spending covered by the category's budget of another term is not "unbudgeted".
            ->whereNotExists(fn ($q) => $q->from('expense_category_plans as pp')->whereColumn('pp.expense_category_id', 'e.expense_category_id')
                ->whereColumn('pp.from_date', '<=', 'e.transaction_date')->whereColumn('pp.to_date', '>=', 'e.transaction_date'))
            ->groupBy('c.name')->selectRaw('c.name, SUM(e.total_amount) as t')->pluck('t', 'name');
        foreach ($unbudgeted as $name => $t) $rows['u:'.$name] = ['category' => $name, 'budget' => 0.0, 'spent' => (float)$t, 'payroll' => 0.0, 'windows' => []];
        // Salary expenses dated outside every salary budget window would otherwise be missed: list them as unbudgeted.
        if (isset($rows[$salaryCat])) {
            $windows = $db->table('expense_category_plans')->where('expense_category_id', $salaryCat)->get(['from_date', 'to_date']);
            $outside = (float)$approved()->whereIn('expense_category_id', $salaryLike)->whereBetween('transaction_date', [$from, $to])
                ->where(function ($q) use ($windows) { foreach ($windows as $w) $q->whereNotBetween('transaction_date', [$w->from_date, $w->to_date]); })
                ->sum('total_amount');
            if ($outside > 0) $rows['u:salaries'] = ['category' => 'Salaries outside a salary budget', 'budget' => 0.0, 'spent' => $outside, 'payroll' => 0.0, 'windows' => []];
        }
        return array_values($rows);
    }

    /** The expense category salary payments count against: a salary-type category with budgets, else any salary-type one. */
    private function salaryCategoryId($db): ?int
    {
        $cats = $db->table('expense_categories as c')->whereRaw("c.name REGEXP 'salar|wage|mshahara|payroll'")
            ->selectRaw('c.id, c.name, (SELECT COUNT(*) FROM expense_category_plans p WHERE p.expense_category_id = c.id) as plans')->get();
        return ($cats->first(fn ($c) => $c->plans > 0 && preg_match('/salar|wage|mshahara/i', $c->name))
            ?? $cats->first(fn ($c) => $c->plans > 0) ?? $cats->first())?->id;
    }

    /** Budget term (year, quarter) of a payroll entry: the one written in its note, else the quarter of the salary month. */
    public static function payrollTerm(object $entry): array
    {
        if (preg_match('/Budget term Q([1-4]) (\d{4})/', (string)($entry->notes ?? ''), $m)) return [(int)$m[2], (int)$m[1]];
        return [(int)$entry->year, ManagementQuarterAnalytics::quarterOf(CarbonImmutable::create((int)$entry->year, (int)$entry->month, 1))[1]];
    }

    /** [year, quarter] that a date window mostly covers. */
    private function homeQuarter(string $from, string $to): array
    {
        $best = [0, 0]; $bestDays = 0;
        for ($y = (int)substr($from, 0, 4); $y <= (int)substr($to, 0, 4); $y++) {
            foreach (range(1, 4) as $q) {
                [$qs, $qe] = ManagementQuarterAnalytics::range($y, $q);
                $s = max($qs->toDateString(), $from); $e = min($qe->toDateString(), $to);
                $days = $s <= $e ? CarbonImmutable::parse($s)->diffInDays(CarbonImmutable::parse($e)) + 1 : 0;
                if ($days > $bestDays) { $best = [$y, $q]; $bestDays = $days; }
            }
        }
        return $best;
    }

    /** The salary budget plan that covers a term best (budgets have their own date windows). */
    private function planForTerm($plans, int $year, int $quarter): ?object
    {
        [$qs, $qe] = ManagementQuarterAnalytics::range($year, $quarter);
        $best = null; $bestDays = 0;
        foreach ($plans as $p) {
            $s = max($qs->toDateString(), $p->from_date); $e = min($qe->toDateString(), $p->to_date);
            $days = $s <= $e ? CarbonImmutable::parse($s)->diffInDays(CarbonImmutable::parse($e)) + 1 : 0;
            if ($days > $bestDays) { $best = $p; $bestDays = $days; }
        }
        return $best;
    }

    /** Gross payroll per salary budget plan id (current tenant), each payment assigned by its budget term. */
    private function payrollUsageByPlan($db, $plans): array
    {
        $used = [];
        foreach ($db->table('payroll_entries')->get(['year', 'month', 'notes', 'gross_salary']) as $e) {
            [$y, $q] = self::payrollTerm($e);
            if ($plan = $this->planForTerm($plans, $y, $q)) $used[$plan->id] = ($used[$plan->id] ?? 0) + (float)$e->gross_salary;
        }
        return $used;
    }

    /** Salary budget of the current tenant for each quarter of a year: plan dates, budget, used, left. */
    private function salaryBudget($db, int $year): array
    {
        $catId = $this->salaryCategoryId($db);
        if (!$catId) return ['category' => null, 'terms' => []];
        $plans = $db->table('expense_category_plans')->where('expense_category_id', $catId)->get(['id', 'expected_amount', 'from_date', 'to_date']);
        $used = $this->payrollUsageByPlan($db, $plans);
        $terms = [];
        foreach (range(1, 4) as $q) {
            $plan = $this->planForTerm($plans, $year, $q);
            $terms[$q] = $plan ? ['window' => $plan->from_date.' – '.$plan->to_date, 'budget' => (float)$plan->expected_amount, 'used' => $used[$plan->id] ?? 0.0] : null;
        }
        return ['category' => $db->table('expense_categories')->where('id', $catId)->value('name'), 'terms' => $terms];
    }

    /** Budget rows of all schools combined by category name (the schools name categories slightly differently). */
    private function groupBudgets(array $snapshots): array
    {
        $groups = [];
        foreach ($snapshots as $s) {
            if ($s['error']) continue;
            foreach ($s['budget'] as $r) {
                $key = preg_replace('/[^a-z0-9]+/', '', strtolower($r['category']));
                $groups[$key] ??= ['category' => $r['category'], 'budget' => 0.0, 'spent' => 0.0, 'payroll' => 0.0, 'schools' => []];
                $groups[$key]['budget'] += $r['budget'];
                $groups[$key]['spent'] += $r['spent'];
                $groups[$key]['payroll'] += $r['payroll'];
                $groups[$key]['schools'][$s['id']] = $r;
            }
        }
        uasort($groups, fn ($a, $b) => max($b['budget'], $b['spent']) <=> max($a['budget'], $a['spent']));
        return array_values($groups);
    }

    /**
     * The schools share bank accounts, so books with the same account number are one account.
     * Books without a number (cash books) stay per school.
     */
    private function groupBooks(array $snapshots): array
    {
        $groups = [];
        foreach ($snapshots as $s) {
            if ($s['error']) continue;
            foreach ($s['books'] as $b) {
                $key = $b['account'] ?: 'school-'.$s['id'].'-'.$b['name'];
                $groups[$key] ??= ['name'=>$b['account'] ? $b['name'] : $b['name'].' · '.self::shortName($s['name']), 'account'=>$b['account'], 'cash'=>$b['cash'], 'schools'=>[], 'balance'=>0.0, 'start'=>0.0, 'in'=>0.0, 'out'=>0.0];
                $groups[$key]['schools'][$s['id']] = ['balance'=>$b['balance'], 'start'=>$b['start'], 'in'=>$b['in'], 'out'=>$b['out']];
                foreach (['balance','start','in','out'] as $f) $groups[$key][$f] += $b[$f];
            }
        }
        // Bank accounts first (largest balance first), then cash books.
        uasort($groups, fn($a, $b) => [$a['cash'], -$a['balance']] <=> [$b['cash'], -$b['balance']]);
        return array_values($groups);
    }

    /** One pending submission, reviewed inside the portal without switching accounts. */
    public function reviewExpense(Request $request, int $schoolId, int $submissionId)
    {
        $school = $this->access->school($request->user(), $schoolId);
        $nav = $this->nav($request, $school->id);
        $html = app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($school, $submissionId, $nav) {
            $submission = ExpenseSubmission::with(['lineItems.item', 'category', 'book'])->findOrFail($submissionId);
            $books = Book::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'balance' => (float)$b->getCashViewBalance(),
                    'fees' => BookFeeCategory::where('book_id', $b->id)->where('is_active', true)->orderBy('name')->get(['id', 'name'])->toArray()]);
            $categories = ExpenseCategory::where('status', 'approved')->orderBy('name')->get(['id', 'name']);
            $budget = (float)ExpenseCategoryPlan::where('expense_category_id', $submission->expense_category_id)
                ->where('academic_year_id', $submission->academic_year_id)->sum('expected_amount');
            $spent = (float)ExpenseLineItem::where('status', 'approved')->whereHas('submission', fn ($q) => $q
                ->where('expense_category_id', $submission->expense_category_id)->where('academic_year_id', $submission->academic_year_id))->sum('line_total');
            $submitter = SchoolAccountant::find($submission->submitted_by)?->name ?? 'Unknown';
            $decider = $submission->decided_by ? (SchoolAccountant::find($submission->decided_by)?->name ?? 'Unknown') : null;
            // Rendered here so every lazy lookup still reads this school's database.
            return view('admin.accountant.management.expense-review', compact('school', 'submission', 'books', 'categories', 'budget', 'spent', 'submitter', 'decider', 'nav')
                + ['activeSection' => 'approvals'])->render();
        });
        return response($html);
    }

    public function decideExpense(Request $request, int $schoolId, int $submissionId)
    {
        $school = $this->access->school($request->user(), $schoolId);
        $grant = $this->access->grants($request->user())->firstWhere('school_id', $school->id);
        $manager = $this->access->manager($request->user());
        try {
            $decision = app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($request, $submissionId, $grant) {
                // Validation runs against this school's database (exists rules use the tenant connection).
                $validated = $request->validate([
                    'decision_note' => 'nullable|string|max:2000',
                    'overall_decision' => 'required|in:approve,deny',
                    'expense_category_id' => 'nullable|exists:expense_categories,id',
                    'book_id' => 'nullable|exists:books,id',
                    'book_fee_category_id' => 'nullable|exists:book_fee_categories,id',
                    'line_items' => 'required|array|min:1',
                    'line_items.*.id' => 'required|exists:expense_line_items,id',
                    'line_items.*.status' => 'required|in:approved,denied',
                    'line_items.*.quantity' => 'nullable|numeric|min:0.001',
                    'line_items.*.unit_price' => 'nullable|numeric|min:0',
                    'line_items.*.denial_reason' => 'nullable|string|max:500',
                ]);
                if ($validated['overall_decision'] === 'deny') {
                    // Denying the whole request keeps the submitted amounts and denies every item.
                    $validated['line_items'] = array_map(fn ($line) => ['id' => $line['id'], 'status' => 'denied',
                        'denial_reason' => ($line['denial_reason'] ?? null) ?: ($validated['decision_note'] ?? null)], $validated['line_items']);
                }
                $validated['decision_note'] ??= null;
                // Decisions are recorded under Ritha's own account in that school, as before.
                app(ExpenseReviewService::class)->decide($submissionId, $validated, (int)$grant->school_accountant_id);
                $s = ExpenseSubmission::findOrFail($submissionId);
                return ['status' => $s->status, 'number' => $s->submission_number, 'total' => (float)$s->total_amount];
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
        ActivityLog::create(['school_id' => $school->id, 'user_type' => 'accountant', 'user_id' => $manager->id, 'user_name' => $manager->name,
            'action' => 'management_expense_'.$decision['status'],
            'description' => "{$decision['number']} {$decision['status']} from the management portal (TSh ".number_format($decision['total'], 2).').',
            'ip_address' => $request->ip()]);
        $label = ['approved' => 'approved', 'partially_approved' => 'partly approved', 'denied' => 'denied'][$decision['status']] ?? $decision['status'];
        return redirect()->to(route('accountant.management', ['school' => $school->id]).'#approvals')
            ->with('success', "{$decision['number']} {$label} for ".self::shortName($school->name).'.');
    }

    /**
     * Approve several pending expenses at once, in full, as submitted. Each one is decided on its own
     * (same service as a single review), so a refusal such as an insufficient balance skips only that one.
     * pay_from: "submitted" keeps each expense's chosen book; "account:<number>" or "cash" picks the
     * matching book in each expense's school (the schools share bank accounts).
     */
    public function bulkApprove(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $data = $request->validate(['items' => 'required|array|min:1|max:200', 'items.*' => ['required', 'regex:/^\d+:\d+$/'],
            'pay_from' => ['required', 'regex:/^(submitted|cash|account:[0-9A-Za-z]+)$/']]);
        $manager = $this->access->manager($request->user());
        $bySchool = collect($data['items'])->map(fn ($i) => array_map('intval', explode(':', $i)))->groupBy(0);
        $done = []; $failed = [];
        foreach ($bySchool as $schoolId => $items) {
            $school = $this->access->school($request->user(), (int)$schoolId);
            $grant = $this->access->grants($request->user())->firstWhere('school_id', $school->id);
            $short = self::shortName($school->name);
            $results = app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($items, $data, $grant) {
                $books = Book::where('is_active', true)->get();
                $target = match (true) {
                    $data['pay_from'] === 'submitted' => null,
                    $data['pay_from'] === 'cash' => $books->firstWhere('is_cash_book', true),
                    default => $books->first(fn ($b) => preg_replace('/\s+/', '', (string)$b->bank_account_number) === substr($data['pay_from'], 8)),
                };
                $out = [];
                foreach ($items as [, $submissionId]) {
                    $s = ExpenseSubmission::with('lineItems')->find($submissionId);
                    if (!$s || $s->status !== 'pending') { $out[] = [false, $s?->submission_number ?? "#$submissionId", 'already decided or not found']; continue; }
                    $bookId = $data['pay_from'] === 'submitted' ? $s->book_id : $target?->id;
                    if (!$bookId) { $out[] = [false, $s->submission_number, $data['pay_from'] === 'submitted' ? 'no book chosen' : 'this school has no such account']; continue; }
                    try {
                        app(ExpenseReviewService::class)->decide($s->id, ['overall_decision' => 'approve', 'decision_note' => null, 'book_id' => $bookId,
                            'line_items' => $s->lineItems->map(fn ($l) => ['id' => $l->id, 'status' => 'approved'])->all()], (int)$grant->school_accountant_id);
                        $out[] = [true, $s->submission_number, (float)$s->fresh()->total_amount];
                    } catch (\RuntimeException $e) {
                        $out[] = [false, $s->submission_number, $e->getMessage()];
                    }
                }
                return $out;
            });
            foreach ($results as [$ok, $number, $detail]) {
                if ($ok) {
                    $done[] = ['school' => $short, 'number' => $number, 'total' => $detail];
                    ActivityLog::create(['school_id' => $school->id, 'user_type' => 'accountant', 'user_id' => $manager->id, 'user_name' => $manager->name,
                        'action' => 'management_expense_approved', 'description' => "$number approved (bulk) from the management portal (TSh ".number_format($detail, 2).').',
                        'ip_address' => $request->ip()]);
                } else {
                    $failed[] = "$short $number: $detail";
                }
            }
        }
        $message = count($done).' expense(s) approved, total TSh '.number_format(array_sum(array_column($done, 'total')), 0).'.';
        $redirect = redirect()->to(route('accountant.management', $request->only('school')).'#approvals')->with('success', $message);
        return $failed ? $redirect->with('error', 'Not approved: '.implode('; ', $failed)) : $redirect;
    }

    /**
     * Staff salaries for one school (?school=) or all granted schools, for a month and year:
     * who is paid, what is due, and the payments made. Rows are keyed "<schoolId>_<staffId>".
     */
    public function payroll(Request $request)
    {
        return $this->staffPage($request, 'payroll');
    }

    /** Staff list for one school or all schools: register, edit and move staff. */
    public function staff(Request $request)
    {
        return $this->staffPage($request, 'staff');
    }

    /** Shared loader for the Payroll and Staff pages (same staff, deductions and paid months). */
    private function staffPage(Request $request, string $page)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $request->validate(['month' => 'nullable|integer|between:1,12', 'year' => 'nullable|integer|between:2000,2100']);
        $selected = $this->selectedSchool($request);
        $schools = $selected ? collect([$selected]) : $this->access->schools($request->user());
        $today = CarbonImmutable::now('Africa/Dar_es_Salaam');
        $month = (int)($request->input('month') ?: $today->month);
        $year = (int)($request->input('year') ?: $today->year);
        $nav = $this->nav($request, $selected?->id);
        $staff = collect(); $deductions = []; $paidMonths = []; $paid = collect(); $booksBySchool = []; $salaryBudgets = [];
        foreach ($schools as $school) {
            $d = app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($month, $year) {
                $db = DB::connection('tenant');
                $staff = $db->table('staff')->where('status', 'active')->orderBy('name')->get(['id', 'staff_id', 'name', 'position', 'monthly_salary', 'phone', 'bank_name', 'bank_account']);
                return [
                    'staff' => $staff,
                    'deductions' => $db->table('staff_deductions')->where('is_active', true)->whereIn('staff_id', $staff->pluck('id'))->orderBy('id')
                        ->get(['id', 'staff_id', 'deduction_type_id', 'name', 'type', 'default_amount'])->groupBy('staff_id'),
                    'paidMonths' => $db->table('payroll_entries')->where('year', $year)->get(['staff_id', 'month'])
                        ->groupBy('staff_id')->map(fn ($r) => $r->pluck('month')->map(fn ($m) => (int)$m)->unique()->values()->all()),
                    'paid' => $db->table('payroll_entries as p')->leftJoin('books as b', 'b.id', '=', 'p.book_id')->leftJoin('staff as s', 's.id', '=', 'p.staff_id')
                        ->where('p.month', $month)->where('p.year', $year)->orderBy('s.name')
                        ->get(['p.staff_id', 's.name', 'p.gross_salary', 'p.total_deductions', 'p.net_salary', 'p.payment_date', 'p.payment_method', 'p.bank_fee_amount', 'b.name as book_name', 'p.created_by']),
                    'salary' => $this->salaryBudget($db, $year),
                    'books' => Book::where('is_active', true)->orderBy('name')->get()
                        ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'account' => $b->bank_account_number ? preg_replace('/\s+/', '', $b->bank_account_number) : null,
                            'cash' => (bool)$b->is_cash_book, 'balance' => (float)$b->getCashViewBalance(),
                            'fees' => BookFeeCategory::where('book_id', $b->id)->where('is_active', true)->orderBy('name')->get(['id', 'name'])->toArray()])->all(),
                ];
            });
            $short = self::shortName($school->name);
            foreach ($d['staff'] as $s) {
                $key = $school->id.'_'.$s->id;
                $staff->push((object)((array)$s + ['key' => $key, 'school_id' => $school->id, 'school' => $short]));
                $deductions[$key] = $d['deductions']->get($s->id, collect());
                $paidMonths[$key] = $d['paidMonths']->get($s->id, []);
            }
            $paid = $paid->merge($d['paid']->map(fn ($p) => (object)((array)$p + ['school' => $short])));
            $booksBySchool[$school->id] = $d['books'];
            $salaryBudgets[$school->id] = $d['salary'] + ['school' => $short];
        }
        // All-schools mode pays from a shared account: one option per bank account number, plus each school's cash book.
        $accountOptions = collect($booksBySchool)->flatten(1)->filter(fn ($b) => $b['account'])->groupBy('account')
            ->map(fn ($g, $acct) => ['value' => 'account:'.$acct, 'label' => $g->first()['name'].' · '.$acct, 'balance' => $g->sum('balance')])->values()->all();
        $payerNames = SchoolAccountant::whereIn('id', $paid->pluck('created_by')->filter()->unique())->pluck('name', 'id');
        $allSchools = $this->access->schools($request->user());
        return view('admin.accountant.management.'.$page, compact('staff', 'deductions', 'paidMonths', 'paid', 'booksBySchool', 'accountOptions', 'salaryBudgets',
            'month', 'year', 'nav', 'payerNames', 'selected', 'allSchools') + ['activeSection' => 'payroll']);
    }

    /**
     * Pay salaries for the ticked staff (keys "<schoolId>_<staffId>") for one or more months. Each payment
     * goes through PayrollPaymentService (the school payroll screen's logic) with the balance check on;
     * months a person is already paid for are skipped. pay_from: "book:<id>" (one school only),
     * "account:<number>" or "cash" (each school's matching book).
     */
    public function payPayroll(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $manager = $this->access->manager($request->user());
        $base = $request->validate([
            'month' => 'required|integer|between:1,12', 'year' => 'required|integer|between:2000,2100',
            'months' => 'nullable|array|max:12', 'months.*' => 'integer|between:1,12', 'school' => 'nullable|integer',
            'pay_from' => ['required', 'regex:/^(book:\d+|cash|account:[0-9A-Za-z]+)$/'], 'payment_date' => 'required|date_format:Y-m-d',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,mobile_money',
            'book_fee_category_id' => 'nullable|integer', 'reference_number' => 'nullable|string|max:255',
            'budget_term' => 'nullable|in:auto,1,2,3,4',
            'selected' => 'required|array|min:1', 'selected.*' => ['required', 'regex:/^\d+_\d+$/'],
            'rows' => 'required|array', 'rows.*.gross' => 'required|numeric|min:0|max:999999999',
            'rows.*.deductions' => 'nullable|array', 'rows.*.deductions.*.name' => 'required|string|max:255',
            'rows.*.deductions.*.type' => 'required|in:fixed,percentage,insurance,penalty,other',
            'rows.*.deductions.*.amount' => 'required|numeric|min:0', 'rows.*.deductions.*.deduction_type_id' => 'nullable|integer',
        ]);
        // Several months can be paid at once with the same amounts; months already paid are skipped per staff.
        $months = collect($base['months'] ?? [$base['month']])->map(fn ($m) => (int)$m)->unique()->sort()->values()->all();
        $bySchool = collect(array_unique($base['selected']))->groupBy(fn ($k) => (int)explode('_', $k)[0]);
        $results = [];
        foreach ($bySchool as $schoolId => $keys) {
            $school = $this->access->school($request->user(), (int)$schoolId);
            $grant = $this->access->grants($request->user())->firstWhere('school_id', $school->id);
            $short = self::shortName($school->name);
            $out = app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($base, $grant, $months, $keys, $short, $bySchool) {
                $books = Book::where('is_active', true)->get();
                $book = match (true) {
                    str_starts_with($base['pay_from'], 'book:') => $bySchool->count() === 1 ? $books->firstWhere('id', (int)substr($base['pay_from'], 5)) : null,
                    $base['pay_from'] === 'cash' => $books->firstWhere('is_cash_book', true),
                    default => $books->first(fn ($b) => preg_replace('/\s+/', '', (string)$b->bank_account_number) === substr($base['pay_from'], 8)),
                };
                $out = [];
                foreach ($months as $month) {
                    $label = date('M', mktime(0, 0, 0, $month, 1));
                    foreach ($keys as $key) {
                        $staffId = (int)explode('_', $key)[1];
                        $staff = DB::connection('tenant')->table('staff')->where('id', $staffId)->where('status', 'active')->first();
                        $row = $base['rows'][$key] ?? null;
                        $who = ($staff->name ?? "Staff #$staffId")." · $short";
                        if (!$staff || !$row) { $out[] = [false, $who, 'not found or inactive', $label]; continue; }
                        if (!$book) { $out[] = [false, $who, 'this school has no such account', $label]; continue; }
                        if (PayrollEntry::where('staff_id', $staffId)->where('month', $month)->where('year', $base['year'])->exists()) {
                            $out[] = [null, $who, 'already paid', $label]; continue;
                        }
                        try {
                            [$entry, $fee] = app(PayrollPaymentService::class)->pay([
                                'staff_id' => $staffId, 'book_id' => $book->id, 'month' => $month, 'year' => $base['year'],
                                'gross_salary' => $row['gross'], 'payment_date' => $base['payment_date'], 'payment_method' => $base['payment_method'],
                                'reference_number' => $base['reference_number'] ?? null,
                                // Budget term: chosen for the batch, or the quarter of the salary month.
                                'notes' => 'Paid from the management portal · Budget term Q'.(in_array($base['budget_term'] ?? 'auto', ['1', '2', '3', '4'], true) ? $base['budget_term'] : ManagementQuarterAnalytics::quarterOf(CarbonImmutable::create($base['year'], $month, 1))[1]).' '.$base['year'],
                                'deductions' => array_values($row['deductions'] ?? []),
                                'book_fee_category_id' => str_starts_with($base['pay_from'], 'book:') ? ($base['book_fee_category_id'] ?? null) : null,
                            ], (int)$grant->school_accountant_id, true);
                            $out[] = [true, $who, (float)$entry->net_salary + (float)$fee, $label];
                        } catch (\RuntimeException $e) {
                            $out[] = [false, $who, $e->getMessage(), $label];
                        }
                    }
                }
                return $out;
            });
            $schoolPaid = array_filter($out, fn ($r) => $r[0] === true);
            if ($schoolPaid) {
                ActivityLog::create(['school_id' => $school->id, 'user_type' => 'accountant', 'user_id' => $manager->id, 'user_name' => $manager->name,
                    'action' => 'management_payroll_paid',
                    'description' => count($schoolPaid).' salary payment(s) from the management portal (TSh '.number_format(array_sum(array_column($schoolPaid, 2)), 2).'): '
                        .implode(', ', array_map(fn ($r) => "{$r[1]} ({$r[3]} {$base['year']})", $schoolPaid)).'.',
                    'ip_address' => $request->ip()]);
            }
            $results = array_merge($results, $out);
        }
        $paid = array_filter($results, fn ($r) => $r[0] === true);
        $skipped = array_filter($results, fn ($r) => $r[0] === null);
        $failed = array_filter($results, fn ($r) => $r[0] === false);
        $monthNames = implode(', ', array_map(fn ($m) => date('M', mktime(0, 0, 0, $m, 1)), $months)).' '.$base['year'];
        $redirect = redirect()->route('accountant.management.payroll', array_filter(['school' => $base['school'] ?? null, 'month' => $base['month'], 'year' => $base['year']]))
            ->with('success', count($paid).' salary payment(s) recorded for '.$monthNames.', total TSh '.number_format(array_sum(array_column($paid, 2)), 0).' (net pay plus any transaction fees).'
                .($skipped ? ' '.count($skipped).' skipped because already paid.' : ''));
        return $failed ? $redirect->with('error', 'Not paid: '.implode('; ', array_map(fn ($r) => "{$r[1]} ({$r[3]}): {$r[2]}", $failed))) : $redirect;
    }

    /**
     * Register one or more staff members (rows staff[i]), each in one of the granted schools, with optional
     * NSSF (10%) and PAYE standing deductions. Rows left without a name are ignored.
     */
    public function registerStaff(Request $request)
    {
        $request->merge(['staff' => array_values(array_filter((array)$request->input('staff', []), fn ($r) => trim((string)($r['name'] ?? '')) !== ''))]);
        $data = $request->validate(['return_school' => 'nullable|integer', 'staff' => 'required|array|min:1|max:50',
            'staff.*.school_id' => 'required|integer', 'staff.*.name' => 'required|string|max:255', 'staff.*.position' => 'required|string|max:255',
            'staff.*.monthly_salary' => 'required|numeric|min:0|max:999999999', 'staff.*.phone' => 'nullable|string|max:255',
            'staff.*.bank_name' => 'nullable|string|max:255', 'staff.*.bank_account' => 'nullable|string|max:255',
            'staff.*.nssf' => 'nullable|boolean', 'staff.*.paye' => 'nullable|numeric|min:0|max:999999999'],
            ['staff.required' => 'Enter at least one staff member with a name.']);
        $manager = $this->access->manager($request->user());
        $done = [];
        foreach (collect($data['staff'])->groupBy('school_id') as $schoolId => $rows) {
            $school = $this->access->school($request->user(), (int)$schoolId);
            $grant = $this->access->grants($request->user())->firstWhere('school_id', $school->id);
            app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($rows, $grant) {
                DB::connection('tenant')->transaction(function () use ($rows, $grant) {
                    foreach ($rows as $row) {
                        $id = $this->createStaffRow($row, (int)$grant->school_accountant_id);
                        if (!empty($row['nssf'])) StaffDeduction::create(['staff_id' => $id, 'name' => 'NSSF', 'type' => 'percentage', 'default_amount' => 10, 'is_active' => true]);
                        if (!empty($row['paye'])) StaffDeduction::create(['staff_id' => $id, 'name' => 'PAYEE', 'type' => 'fixed', 'default_amount' => $row['paye'], 'is_active' => true]);
                    }
                });
            });
            ActivityLog::create(['school_id' => $school->id, 'user_type' => 'accountant', 'user_id' => $manager->id, 'user_name' => $manager->name,
                'action' => 'management_staff_registered', 'description' => 'Registered from the management portal: '
                    .$rows->map(fn ($r) => "{$r['name']} ({$r['position']}, TSh ".number_format($r['monthly_salary'], 0).')')->implode(', ').'.',
                'ip_address' => $request->ip()]);
            foreach ($rows as $r) $done[] = $r['name'].' ('.self::shortName($school->name).')';
        }
        return redirect()->to(route('accountant.management.staff', array_filter(['school' => $data['return_school'] ?? null])))
            ->with('success', count($done).' staff registered: '.implode(', ', $done).'.');
    }

    /**
     * Edit a staff member's details and standing deductions: change amounts, remove (deactivate) or add.
     * Payments already made keep the deductions they were made with.
     */
    public function updateStaff(Request $request)
    {
        $data = $request->validate(['key' => ['required', 'regex:/^\d+_\d+$/'], 'return_school' => 'nullable|integer',
            'name' => 'required|string|max:255', 'position' => 'required|string|max:255', 'monthly_salary' => 'required|numeric|min:0|max:999999999',
            'phone' => 'nullable|string|max:255', 'bank_name' => 'nullable|string|max:255', 'bank_account' => 'nullable|string|max:255',
            'deductions' => 'nullable|array', 'deductions.*.amount' => 'required|numeric|min:0|max:999999999', 'deductions.*.remove' => 'nullable|boolean',
            'new_deductions' => 'nullable|array', 'new_deductions.*.name' => 'nullable|string|max:255',
            'new_deductions.*.type' => 'nullable|in:fixed,percentage', 'new_deductions.*.amount' => 'nullable|numeric|min:0|max:999999999']);
        [$schoolId, $staffId] = array_map('intval', explode('_', $data['key']));
        $school = $this->access->school($request->user(), $schoolId);
        $manager = $this->access->manager($request->user());
        $changes = app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($data, $staffId) {
            $db = DB::connection('tenant');
            return $db->transaction(function () use ($db, $data, $staffId) {
                $before = $db->table('staff')->where('id', $staffId)->where('status', 'active')->first() ?? abort(404);
                $db->table('staff')->where('id', $staffId)->update(['name' => $data['name'], 'position' => $data['position'], 'monthly_salary' => $data['monthly_salary'],
                    'phone' => $data['phone'] ?? null, 'bank_name' => $data['bank_name'] ?? null, 'bank_account' => $data['bank_account'] ?? null, 'updated_at' => now()]);
                $changes = [];
                if ((float)$before->monthly_salary !== (float)$data['monthly_salary']) $changes[] = 'salary '.number_format($before->monthly_salary, 0).' → '.number_format($data['monthly_salary'], 0);
                foreach ($data['deductions'] ?? [] as $dedId => $d) {
                    $row = StaffDeduction::where('staff_id', $staffId)->where('is_active', true)->find($dedId);
                    if (!$row) continue;
                    if (!empty($d['remove'])) { $row->update(['is_active' => false]); $changes[] = "removed {$row->name}"; }
                    elseif ((float)$row->default_amount !== (float)$d['amount']) { $changes[] = "{$row->name} ".(float)$row->default_amount.' → '.(float)$d['amount']; $row->update(['default_amount' => $d['amount']]); }
                }
                foreach ($data['new_deductions'] ?? [] as $d) {
                    if (trim((string)($d['name'] ?? '')) === '' || !isset($d['amount'])) continue;
                    StaffDeduction::create(['staff_id' => $staffId, 'name' => trim($d['name']), 'type' => $d['type'] ?? 'fixed', 'default_amount' => $d['amount'], 'is_active' => true]);
                    $changes[] = 'added '.trim($d['name']);
                }
                return $changes;
            });
        });
        ActivityLog::create(['school_id' => $school->id, 'user_type' => 'accountant', 'user_id' => $manager->id, 'user_name' => $manager->name,
            'action' => 'management_staff_updated', 'description' => "Updated {$data['name']} from the management portal".($changes ? ': '.implode('; ', $changes) : '').'.',
            'ip_address' => $request->ip()]);
        return redirect()->to(route('accountant.management.staff', array_filter(['school' => $data['return_school'] ?? null])))
            ->with('success', "{$data['name']} updated".($changes ? ': '.implode('; ', $changes) : '').'.');
    }

    /**
     * Move a staff member to another school: the record and active standing deductions are copied to the
     * new school and the old record is set inactive. Payments already made stay in the old school's books.
     */
    public function moveStaff(Request $request)
    {
        $data = $request->validate(['key' => ['required', 'regex:/^\d+_\d+$/'], 'to_school_id' => 'required|integer', 'return_school' => 'nullable|integer']);
        [$fromId, $staffId] = array_map('intval', explode('_', $data['key']));
        $from = $this->access->school($request->user(), $fromId);
        $to = $this->access->school($request->user(), (int)$data['to_school_id']);
        abort_if($from->id === $to->id, 422, 'Choose a different school.');
        $manager = $this->access->manager($request->user());
        $toGrant = $this->access->grants($request->user())->firstWhere('school_id', $to->id);
        [$person, $deds] = app(TenantDatabaseManager::class)->executeForSchool($from, fn () => [
            (array)(DB::connection('tenant')->table('staff')->where('id', $staffId)->where('status', 'active')->first() ?? abort(404)),
            DB::connection('tenant')->table('staff_deductions')->where('staff_id', $staffId)->where('is_active', true)->get(['name', 'type', 'default_amount', 'note'])->map(fn ($d) => (array)$d)->all(),
        ]);
        app(TenantDatabaseManager::class)->executeForSchool($to, function () use ($person, $deds, $toGrant, $from) {
            DB::connection('tenant')->transaction(function () use ($person, $deds, $toGrant, $from) {
                $id = $this->createStaffRow(['notes' => trim(($person['notes'] ?? '')."\nMoved from ".self::shortName($from->name).' on '.now('Africa/Dar_es_Salaam')->toDateString().'.')] + $person, (int)$toGrant->school_accountant_id);
                // Deduction types are per school, so the copies keep name/type/amount without the old type id.
                foreach ($deds as $d) StaffDeduction::create(['staff_id' => $id, 'name' => $d['name'], 'type' => $d['type'], 'default_amount' => $d['default_amount'], 'note' => $d['note'], 'is_active' => true]);
            });
        });
        app(TenantDatabaseManager::class)->executeForSchool($from, fn () => DB::connection('tenant')->table('staff')->where('id', $staffId)->update([
            'status' => 'inactive', 'notes' => trim(($person['notes'] ?? '')."\nMoved to ".self::shortName($to->name).' on '.now('Africa/Dar_es_Salaam')->toDateString().'.'), 'updated_at' => now()]));
        ActivityLog::create(['school_id' => $to->id, 'user_type' => 'accountant', 'user_id' => $manager->id, 'user_name' => $manager->name,
            'action' => 'management_staff_moved', 'description' => "Moved {$person['name']} from ".self::shortName($from->name).' to '.self::shortName($to->name).'.',
            'ip_address' => $request->ip()]);
        return redirect()->to(route('accountant.management.staff', array_filter(['school' => $data['return_school'] ?? null])))
            ->with('success', "{$person['name']} moved from ".self::shortName($from->name).' to '.self::shortName($to->name).'. Earlier payments stay in '.self::shortName($from->name).'.');
    }

    /** Insert a staff row in the current tenant with the next free numeric staff code. */
    private function createStaffRow(array $data, int $createdBy): int
    {
        $db = DB::connection('tenant');
        $next = (int)$db->table('staff')->whereRaw("staff_id REGEXP '^[0-9]+$'")->max(DB::raw('CAST(staff_id AS UNSIGNED)')) + 1;
        while ($db->table('staff')->where('staff_id', (string)$next)->exists()) $next++;
        return $db->table('staff')->insertGetId([
            'name' => $data['name'], 'staff_id' => (string)$next, 'position' => $data['position'], 'department' => $data['department'] ?? null,
            'monthly_salary' => $data['monthly_salary'], 'phone' => $data['phone'] ?? null, 'email' => $data['email'] ?? null,
            'bank_name' => $data['bank_name'] ?? null, 'bank_account' => $data['bank_account'] ?? null,
            'status' => 'active', 'date_joined' => $data['date_joined'] ?? now('Africa/Dar_es_Salaam')->toDateString(),
            'notes' => $data['notes'] ?? null, 'created_by' => $createdBy, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** Students with fees past their deadline (today), per school or all schools, with their invoices. */
    public function overdue(Request $request)
    {
        return $this->studentListPage($request, 'overdue');
    }

    /** Students holding advance credit (prepaid money not yet applied to fees), with their invoices. */
    public function advance(Request $request)
    {
        return $this->studentListPage($request, 'advance');
    }

    private function studentListPage(Request $request, string $kind)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $selected = $this->selectedSchool($request);
        $schools = $selected ? collect([$selected]) : $this->access->schools($request->user());
        $nav = $this->nav($request, $selected?->id);
        $rows = collect();
        foreach ($schools as $school) {
            $short = self::shortName($school->name);
            $list = app(TenantDatabaseManager::class)->executeForSchool($school, fn () => $this->studentsFor($kind));
            foreach ($list as $r) $rows->push((object)((array)$r + ['key' => $school->id.'_'.$r->id, 'school_id' => $school->id, 'school' => $short]));
        }
        $rows = $rows->sortByDesc($kind === 'overdue' ? 'overdue' : 'advance_balance')->values();
        return view('admin.accountant.management.student-list', compact('kind', 'rows', 'selected', 'nav') + ['activeSection' => $kind]);
    }

    /** Students of the current tenant for the overdue or advance list. */
    private function studentsFor(string $kind)
    {
        $db = DB::connection('tenant');
        $today = now('Africa/Dar_es_Salaam')->toDateString();
        $unpaid = $db->table('particular_student')->selectRaw('student_id, SUM(GREATEST(0, sales-credit)) as unpaid')->groupBy('student_id');
        $base = $db->table('students as s')->where('s.status', 'active')->leftJoinSub($unpaid, 'u', 'u.student_id', '=', 's.id')
            ->select('s.id', 's.name', 's.student_reg_no', 's.class', 's.parent_name', 's.parent_phone_1', 's.advance_balance')->selectRaw('COALESCE(u.unpaid, 0) as unpaid');
        if ($kind === 'advance') return $base->where('s.advance_balance', '>', 0)->get();
        $overdue = $db->table('particular_student')->whereNotNull('deadline')->whereDate('deadline', '<', $today)->whereColumn('sales', '>', 'credit')
            ->selectRaw('student_id, SUM(sales-credit) as overdue, MIN(deadline) as oldest_deadline')->groupBy('student_id');
        return $base->joinSub($overdue, 'o', 'o.student_id', '=', 's.id')->addSelect('o.overdue', 'o.oldest_deadline')->get();
    }

    /**
     * Download invoices for a list of students across one or more schools.
     * mode=pdf: one PDF (one per school, zipped, when several schools); mode=zip: one PDF per student, zipped.
     * Students are given as keys "<schoolId>_<studentId>", or kind=overdue|advance|all for that whole list.
     */
    public function downloadInvoiceBundle(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $data = $request->validate(['mode' => 'required|in:pdf,zip', 'kind' => 'nullable|in:overdue,advance,all', 'school' => 'nullable|integer',
            'students' => 'nullable|array|max:2000', 'students.*' => ['regex:/^\d+_\d+$/'], 'format' => 'nullable|in:accountant,parent', 'heading' => 'nullable|string|max:80']);
        $schools = !empty($data['school']) ? collect([$this->access->school($request->user(), (int)$data['school'])]) : $this->access->schools($request->user());
        $picked = collect($data['students'] ?? [])->groupBy(fn ($k) => (int)explode('_', $k)[0])->map(fn ($g) => $g->map(fn ($k) => (int)explode('_', $k)[1])->all());
        $label = ['overdue' => 'overdue', 'advance' => 'advance', 'all' => 'all-students'][$data['kind'] ?? ''] ?? 'selected';
        $files = []; $ledger = app(LedgerController::class);
        foreach ($schools as $school) {
            if ($picked->isNotEmpty() && !$picked->has($school->id)) continue;
            $short = self::shortName($school->name);
            $files += app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($request, $data, $picked, $school, $short, $ledger, $schools, $label) {
                $ids = $picked->get($school->id) ?? (($data['kind'] ?? 'all') === 'all' ? null : $this->studentsFor($data['kind'])->pluck('id')->all());
                if ($ids === []) return [];
                $sub = Request::create('/', 'GET', array_filter(['format' => $data['format'] ?? 'parent', 'heading' => $data['heading'] ?? 'INVOICE:', 'students' => $ids]));
                $students = $ledger->studentsForInvoiceExport($sub);
                if ($students->isEmpty()) return [];
                if ($data['mode'] === 'zip') return $ledger->studentInvoiceFiles($students, $sub, $schools->count() > 1 ? $short.'/' : '');
                [$bytes, $name] = $ledger->allStudentsInvoicesPdf($sub, "$short $label");
                return [$name => $bytes];
            });
        }
        abort_if(empty($files), 404, 'No students to export.');
        if (count($files) === 1 && $data['mode'] === 'pdf') {
            return response(reset($files), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="'.array_key_first($files).'"']);
        }
        return response()->download($ledger->zipFiles($files), "invoices-$label-".now('Africa/Dar_es_Salaam')->format('Y-m-d').'.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }

    /** Fee statements (invoices) for one school, generated without switching accounts. */
    public function invoices(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $selected = $this->selectedSchool($request) ?? $this->access->schools($request->user())->first();
        $nav = $this->nav($request, $selected->id);
        $data = app(TenantDatabaseManager::class)->executeForSchool($selected, function () {
            $db = DB::connection('tenant');
            return [
                'classes' => SchoolClass::where('is_active', true)->orderBy('name')->get(['id', 'name'])->toArray(),
                'students' => $db->table('students')->where('status', 'active')->orderBy('name')->get(['id', 'name', 'student_reg_no', 'class', 'class_id'])->toArray(),
                'balances' => $db->table('particular_student')->selectRaw('student_id, SUM(GREATEST(0, sales-credit)) as balance')->groupBy('student_id')->pluck('balance', 'student_id')->toArray(),
            ];
        });
        return view('admin.accountant.management.invoices', $data + ['school' => $selected, 'nav' => $nav, 'activeSection' => 'invoices']);
    }

    public function downloadInvoices(Request $request, int $schoolId)
    {
        $school = $this->access->school($request->user(), $schoolId);
        $request->validate(['scope' => 'required|in:all,class,student', 'class' => 'required_if:scope,class|nullable|integer',
            'student' => 'required_if:scope,student|nullable|integer', 'format' => 'nullable|in:accountant,parent',
            'heading' => 'nullable|string|max:80', 'preview' => 'nullable|in:1']);
        return app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($request) {
            $ledger = app(LedgerController::class);
            if ($request->input('scope') === 'student') {
                $response = $ledger->exportStudentInvoicePdf((int)$request->input('student'), $request);
                // A preview is a view; render it while this school's database is selected.
                return $response instanceof \Illuminate\Contracts\View\View ? response($response->render()) : $response;
            }
            if ($request->input('scope') === 'all') {
                $request->query->remove('class');
            }
            return $ledger->exportAllStudentsInvoicesPdf($request);
        });
    }

    public function mine(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(),403);
        return redirect()->to(route('accountant.management',$request->only('from','to','year','quarter','period_mode')).'#targets');
    }

    public function saveTarget(Request $request)
    {
        $data=$request->validate([
            'school_id'=>'required|integer','accountant_id'=>'required|integer',
            'metric'=>'required|in:'.implode(',',array_keys(AccountantKpiService::METRICS)),
            'from_date'=>'required|date_format:Y-m-d','to_date'=>'required|date_format:Y-m-d|after_or_equal:from_date',
            'target_value'=>'required|numeric|gt:0|max:999999999999.99','recording_days'=>'required|integer|between:0,30',
            'year'=>'nullable|integer|between:2000,2100','quarter'=>'nullable|integer|between:1,4','period_mode'=>'nullable|in:quarter,custom',
        ]);
        $school=$this->access->school($request->user(), (int)$data['school_id']);
        abort_unless(SchoolAccountant::whereKey($data['accountant_id'])->where('school_id',$school->id)->where('is_active',true)->exists(),422,'Choose an active accountant in this school.');
        validator($data, ['target_value'=>$data['metric']==='recording_timeliness'?'numeric|max:100':($data['metric']==='receipt_count'?'integer':'numeric')])->validate();
        abort_if(CarbonImmutable::parse($data['from_date'])->diffInDays(CarbonImmutable::parse($data['to_date'])) > 730,422,'Choose a period of two years or less.');
        $key = array_intersect_key($data,array_flip(['school_id','accountant_id','metric','from_date','to_date']));
        DB::connection('central')->transaction(function () use ($request,$key,$data) {
            $existing=DB::connection('central')->table('accountant_kpi_targets')->where($key)->first();
            DB::connection('central')->table('accountant_kpi_targets')->updateOrInsert($key,[
                'target_value'=>$data['target_value'],'recording_days'=>$data['recording_days'],
                'updated_by'=>$this->access->manager($request->user())->id,'created_at'=>$existing?->created_at ?? now(),'updated_at'=>now()]);
            ActivityLog::create(['school_id'=>$data['school_id'],'user_type'=>'accountant','user_id'=>$this->access->manager($request->user())->id,
                'user_name'=>$this->access->manager($request->user())->name,'action'=>'kpi_target_updated',
                'description'=>json_encode(['target'=>$data,'previous'=>$existing?->target_value]),'ip_address'=>$request->ip()]);
        });
        return redirect()->to(route('accountant.management',array_merge(['from'=>$data['from_date'],'to'=>$data['to_date']],$request->only('year','quarter','period_mode'))).'#targets')->with('success','Accountant target saved.');
    }

    public function enter(Request $request, int $schoolId)
    {
        $this->access->school($request->user(),$schoolId);
        $grant=$this->access->grants($request->user())->firstWhere('school_id',$schoolId);
        $account=SchoolAccountant::findOrFail($grant->school_accountant_id);
        $routes=['dashboard'=>'accountant.dashboard','expenses'=>'accountant.expenses','fee-entry'=>'accountant.fee-entry','overdue'=>'accountant.overdue','ledgers'=>'accountant.ledgers','invoices'=>'accountant.invoices-page'];
        $request->validate(['page'=>'required|in:'.implode(',',array_keys($routes)),'submission_id'=>'nullable|integer|min:1']);
        $manager=$this->access->manager($request->user());
        ActivityLog::create(['school_id'=>$schoolId,'user_type'=>'accountant','user_id'=>$manager->id,'user_name'=>$manager->name,
            'action'=>'management_school_opened','description'=>'Opened school through management overview.','ip_address'=>$request->ip()]);
        Auth::guard('web')->login($account);
        $request->session()->regenerate();
        $request->session()->put('school_management_manager_id',$manager->id);
        $request->session()->forget(['impersonating','impersonating_super_admin_id','current_school_slug','current_school_id']);
        $params=$request->input('page')==='expenses' ? ['tab'=>'review','submission'=>$request->input('submission_id')] : [];
        return redirect()->route($routes[$request->input('page')],$params);
    }

    public function accountSettings(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(),403);
        $manager=$this->access->manager($request->user());
        Auth::guard('web')->login($manager);
        $request->session()->regenerate();
        $request->session()->forget('school_management_manager_id');
        return redirect()->route('profile.edit');
    }
}
