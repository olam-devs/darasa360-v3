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
            'period_mode'=>'nullable|in:quarter,custom']);
        $today = CarbonImmutable::now('Africa/Dar_es_Salaam');
        [$currentYear, $currentQuarter] = ManagementQuarterAnalytics::quarterOf($today);
        $year = (int)($request->input('year') ?: $currentYear);
        $quarter = (int)($request->input('quarter') ?: $currentQuarter);
        $periodMode = $request->input('period_mode') ?: ($request->filled('from') || $request->filled('to') ? 'custom' : 'quarter');
        [$start, $end] = ManagementQuarterAnalytics::range($year, $quarter);
        $from = $periodMode === 'quarter' ? $start->toDateString() : ($request->input('from') ?: $start->toDateString());
        $to = $periodMode === 'quarter' ? $end->toDateString() : ($request->input('to') ?: $end->toDateString());
        validator(['from'=>$from,'to'=>$to], ['from'=>'required|date','to'=>'required|date|after_or_equal:from'])->validate();
        abort_if(CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) > 730, 422, 'Choose a period of two years or less.');
        $periodLabel = $periodMode === 'quarter' ? "Q{$quarter} {$year}" : "{$from} to {$to}";
        return compact('from','to','year','quarter','periodMode','periodLabel');
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
        ['from'=>$from,'to'=>$to,'year'=>$year,'quarter'=>$quarter,'periodMode'=>$periodMode,'periodLabel'=>$periodLabel] = $this->period($request);
        $selectedSchool = $this->selectedSchool($request);
        $schools = $selectedSchool ? collect([$selectedSchool]) : $this->access->schools($request->user());
        $nav = $this->nav($request, $selectedSchool?->id);
        $yearOptions = collect([$year,now('Africa/Dar_es_Salaam')->year]);
        $snapshots = []; $kpiRows = [];
        foreach ($schools as $school) {
            try {
                $snapshots[] = app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($school, $from, $to, $year, $quarter, $periodMode, &$yearOptions) {
                    $db = DB::connection('tenant');
                    $yearOptions = $yearOptions->merge($db->table('academic_years')->pluck('name')->filter(fn($name)=>preg_match('/^20\d{2}$|^2100$/',(string)$name))->map(fn($name)=>(int)$name));
                    $quarterReport = app(ManagementQuarterAnalytics::class)->report($year,$quarter);
                    $receipts = $this->kpis->receipts()->whereBetween('date', [$from, $to]);
                    $days = (int)CarbonImmutable::parse($from)->diffInDays(CarbonImmutable::parse($to)) + 1;
                    $previousTo = CarbonImmutable::parse($from)->subDay();
                    $previousFrom = $periodMode === 'quarter' ? ManagementQuarterAnalytics::range(...ManagementQuarterAnalytics::quarterOf($previousTo))[0] : $previousTo->subDays($days-1);
                    $previous = (float)$this->kpis->receipts()->whereBetween('date', [$previousFrom->toDateString(), $previousTo->toDateString()])->sum('debit');
                    $expenses = (float)$db->table('expense_submissions')->whereIn('status',['approved','partially_approved'])
                        ->whereBetween('transaction_date',[$from,$to])->sum('total_amount');
                    $owed = $db->table('particular_student')->selectRaw('COALESCE(SUM(GREATEST(0,sales-credit)),0) as outstanding, COALESCE(SUM(sales),0) as billed, COALESCE(SUM(credit),0) as paid')->first();
                    $pending = $db->table('expense_submissions')->where('status','pending');
                    $books = $db->table('books')->where('is_active',true)->get(['id','opening_balance']);
                    $bookMovement = $db->table('vouchers')->whereIn('book_id',$books->pluck('id'))->whereNull('voided_at')->selectRaw('COALESCE(SUM(debit-credit),0) as net')->value('net');
                    $trend = (clone $receipts)->selectRaw('DATE(date) as day, SUM(debit) as amount')->groupBy('day')->orderBy('day')->get();
                    $topDebtors = $db->table('particular_student as ps')->join('students as s','s.id','=','ps.student_id')
                        ->select('s.name')->selectRaw('SUM(GREATEST(0,ps.sales-ps.credit)) as balance')->groupBy('s.id','s.name')->having('balance','>',0)->orderByDesc('balance')->limit(5)->get();
                    // Each active book with its all-time balance and the money in/out during the selected period.
                    $bookRows = $db->table('books as b')->where('b.is_active',true)
                        ->leftJoin('vouchers as v', fn($j) => $j->on('v.book_id','=','b.id')->whereNull('v.voided_at'))
                        ->groupBy('b.id','b.name','b.bank_account_number','b.is_cash_book','b.opening_balance')
                        ->selectRaw('b.id, b.name, b.bank_account_number, b.is_cash_book, b.opening_balance,
                            COALESCE(SUM(v.debit-v.credit),0) as net,
                            COALESCE(SUM(CASE WHEN v.date BETWEEN ? AND ? THEN v.debit ELSE 0 END),0) as period_in,
                            COALESCE(SUM(CASE WHEN v.date BETWEEN ? AND ? THEN v.credit ELSE 0 END),0) as period_out', [$from,$to,$from,$to])
                        ->orderBy('b.name')->get()
                        ->map(fn($b) => ['name'=>$b->name, 'account'=>$b->bank_account_number ? preg_replace('/\s+/','',$b->bank_account_number) : null,
                            'cash'=>(bool)$b->is_cash_book, 'balance'=>(float)$b->opening_balance+(float)$b->net,
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
                        'overdue'=>(float)$db->table('particular_student')->whereNotNull('deadline')->whereDate('deadline','<',now('Africa/Dar_es_Salaam')->toDateString())->selectRaw('COALESCE(SUM(GREATEST(0,sales-credit)),0) as total')->value('total'),
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
                        'books'=>$bookRows,'monthly_collections'=>$monthlyCollections,'monthly_expenses'=>$monthlyExpenses];
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
        return view('admin.accountant.management.overview', compact('settings','schools','snapshots','kpiRows','from','to','metrics','year','quarter','periodMode','periodLabel','yearOptions','quarterLabels','nav','selectedSchool','bookGroups','deciderNames'));
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
                $groups[$key] ??= ['name'=>$b['account'] ? $b['name'] : $b['name'].' · '.self::shortName($s['name']), 'account'=>$b['account'], 'cash'=>$b['cash'], 'schools'=>[], 'balance'=>0.0, 'in'=>0.0, 'out'=>0.0];
                $groups[$key]['schools'][$s['id']] = ['balance'=>$b['balance'], 'in'=>$b['in'], 'out'=>$b['out']];
                foreach (['balance','in','out'] as $f) $groups[$key][$f] += $b[$f];
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

    /** Staff salaries for one school and month: who is paid, what is due, and the payments made. */
    public function payroll(Request $request)
    {
        abort_if($this->access->grants($request->user())->isEmpty(), 403);
        $request->validate(['month' => 'nullable|integer|between:1,12', 'year' => 'nullable|integer|between:2000,2100']);
        $selected = $this->selectedSchool($request) ?? $this->access->schools($request->user())->first();
        $today = CarbonImmutable::now('Africa/Dar_es_Salaam');
        $month = (int)($request->input('month') ?: $today->month);
        $year = (int)($request->input('year') ?: $today->year);
        $nav = $this->nav($request, $selected->id);
        $data = app(TenantDatabaseManager::class)->executeForSchool($selected, function () use ($month, $year) {
            $db = DB::connection('tenant');
            $paid = $db->table('payroll_entries as p')->leftJoin('books as b', 'b.id', '=', 'p.book_id')->where('p.month', $month)->where('p.year', $year)
                ->get(['p.id', 'p.staff_id', 'p.gross_salary', 'p.total_deductions', 'p.net_salary', 'p.payment_date', 'p.payment_method', 'p.bank_fee_amount', 'b.name as book_name', 'p.created_by'])->keyBy('staff_id');
            $staff = $db->table('staff')->where('status', 'active')->orderBy('name')->get(['id', 'name', 'position', 'monthly_salary', 'bank_name', 'bank_account']);
            $deductions = $db->table('staff_deductions')->where('is_active', true)->whereIn('staff_id', $staff->pluck('id'))->orderBy('id')
                ->get(['staff_id', 'deduction_type_id', 'name', 'type', 'default_amount'])->groupBy('staff_id');
            $books = Book::where('is_active', true)->orderBy('name')->get()
                ->map(fn ($b) => ['id' => $b->id, 'name' => $b->name, 'balance' => (float)$b->getCashViewBalance(),
                    'fees' => BookFeeCategory::where('book_id', $b->id)->where('is_active', true)->orderBy('name')->get(['id', 'name'])->toArray()])->all();
            // Staff paid this month who are no longer active still appear in the history.
            $paidNames = $db->table('staff')->whereIn('id', $paid->keys())->pluck('name', 'id');
            // Months of the year each staff member is already paid for (multi-month payments skip these).
            $paidMonths = $db->table('payroll_entries')->where('year', $year)->get(['staff_id', 'month'])
                ->groupBy('staff_id')->map(fn ($rows) => $rows->pluck('month')->map(fn ($m) => (int)$m)->unique()->values()->all())->all();
            return compact('paid', 'staff', 'deductions', 'books', 'paidNames', 'paidMonths');
        });
        $payerNames = SchoolAccountant::whereIn('id', $data['paid']->pluck('created_by')->filter()->unique())->pluck('name', 'id');
        return view('admin.accountant.management.payroll', $data + compact('month', 'year', 'nav', 'payerNames') + ['school' => $selected, 'activeSection' => 'payroll']);
    }

    /**
     * Pay salaries for the ticked staff. Each payment goes through PayrollPaymentService (the school
     * payroll screen's logic) with the balance check on; staff already paid for the month are skipped.
     */
    public function payPayroll(Request $request, int $schoolId)
    {
        $school = $this->access->school($request->user(), $schoolId);
        $grant = $this->access->grants($request->user())->firstWhere('school_id', $school->id);
        $manager = $this->access->manager($request->user());
        $base = $request->validate([
            'month' => 'required|integer|between:1,12', 'year' => 'required|integer|between:2000,2100',
            'months' => 'nullable|array|max:12', 'months.*' => 'integer|between:1,12',
            'book_id' => 'required|integer', 'payment_date' => 'required|date_format:Y-m-d',
            'payment_method' => 'required|in:cash,bank_transfer,cheque,mobile_money',
            'book_fee_category_id' => 'nullable|integer', 'reference_number' => 'nullable|string|max:255',
            'selected' => 'required|array|min:1', 'selected.*' => 'integer',
            'rows' => 'required|array', 'rows.*.gross' => 'required|numeric|min:0|max:999999999',
            'rows.*.deductions' => 'nullable|array', 'rows.*.deductions.*.name' => 'required|string|max:255',
            'rows.*.deductions.*.type' => 'required|in:fixed,percentage,insurance,penalty,other',
            'rows.*.deductions.*.amount' => 'required|numeric|min:0', 'rows.*.deductions.*.deduction_type_id' => 'nullable|integer',
        ]);
        // Several months can be paid at once with the same amounts; months already paid are skipped per staff.
        $months = collect($base['months'] ?? [$base['month']])->map(fn ($m) => (int)$m)->unique()->sort()->values()->all();
        $results = app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($base, $grant, $months) {
            abort_unless(Book::where('is_active', true)->whereKey($base['book_id'])->exists(), 422, 'Choose an active book of this school.');
            $out = [];
            foreach ($months as $month) {
                $label = date('M', mktime(0, 0, 0, $month, 1));
                foreach (array_unique($base['selected']) as $staffId) {
                    $staff = DB::connection('tenant')->table('staff')->where('id', $staffId)->where('status', 'active')->first();
                    $row = $base['rows'][$staffId] ?? null;
                    if (!$staff || !$row) { $out[] = [false, "Staff #$staffId", 'not found or inactive', $label]; continue; }
                    if (PayrollEntry::where('staff_id', $staffId)->where('month', $month)->where('year', $base['year'])->exists()) {
                        $out[] = [null, $staff->name, 'already paid', $label]; continue;
                    }
                    try {
                        [$entry, $fee] = app(PayrollPaymentService::class)->pay([
                            'staff_id' => $staffId, 'book_id' => $base['book_id'], 'month' => $month, 'year' => $base['year'],
                            'gross_salary' => $row['gross'], 'payment_date' => $base['payment_date'], 'payment_method' => $base['payment_method'],
                            'reference_number' => $base['reference_number'] ?? null, 'notes' => 'Paid from the management portal',
                            'deductions' => array_values($row['deductions'] ?? []), 'book_fee_category_id' => $base['book_fee_category_id'] ?? null,
                        ], (int)$grant->school_accountant_id, true);
                        $out[] = [true, $staff->name, (float)$entry->net_salary + (float)$fee, $label];
                    } catch (\RuntimeException $e) {
                        $out[] = [false, $staff->name, $e->getMessage(), $label];
                    }
                }
            }
            return $out;
        });
        $paid = array_filter($results, fn ($r) => $r[0] === true);
        $skipped = array_filter($results, fn ($r) => $r[0] === null);
        $failed = array_filter($results, fn ($r) => $r[0] === false);
        $monthNames = implode(', ', array_map(fn ($m) => date('M', mktime(0, 0, 0, $m, 1)), $months)).' '.$base['year'];
        if ($paid) {
            ActivityLog::create(['school_id' => $school->id, 'user_type' => 'accountant', 'user_id' => $manager->id, 'user_name' => $manager->name,
                'action' => 'management_payroll_paid',
                'description' => count($paid).' salary payment(s) for '.$monthNames.' from the management portal (TSh '
                    .number_format(array_sum(array_column($paid, 2)), 2).'): '.implode(', ', array_map(fn ($r) => "{$r[1]} ({$r[3]})", $paid)).'.',
                'ip_address' => $request->ip()]);
        }
        $redirect = redirect()->route('accountant.management.payroll', ['school' => $school->id, 'month' => $base['month'], 'year' => $base['year']])
            ->with('success', count($paid).' salary payment(s) recorded for '.$monthNames.', total TSh '.number_format(array_sum(array_column($paid, 2)), 0).' (net pay plus any transaction fees).'
                .($skipped ? ' '.count($skipped).' skipped because already paid.' : ''));
        return $failed ? $redirect->with('error', 'Not paid: '.implode('; ', array_map(fn ($r) => "{$r[1]} ({$r[3]}): {$r[2]}", $failed))) : $redirect;
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
                'students' => $db->table('students')->where('status', 'active')->orderBy('name')->get(['id', 'name', 'student_reg_no', 'class'])->toArray(),
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
