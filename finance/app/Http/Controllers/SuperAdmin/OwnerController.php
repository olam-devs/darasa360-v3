<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\LedgerController;
use App\Models\Central\School;
use App\Services\TenantDatabaseManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OwnerController extends Controller
{
    public function __construct(protected TenantDatabaseManager $tenantManager) {}

    // ── Live stats ───────────────────────────────────────────────────────────

    public function liveStats()
    {
        $schools  = School::active()->orderBy('name')->get();
        $results  = [];

        foreach ($schools as $school) {
            try {
                $this->tenantManager->switchToSchool($school);
                $conn = DB::connection('tenant');

                // 7-day collection trend
                $raw = $conn->table('vouchers')
                    ->where('voucher_type', 'Receipt')
                    ->whereNull('voided_at')
                    ->where('date', '>=', now()->subDays(6)->toDateString())
                    ->selectRaw('DATE(date) as day, SUM(debit) as total')
                    ->groupBy('day')->orderBy('day')->get()->pluck('total', 'day');
                $trend = [];
                for ($i = 6; $i >= 0; $i--) {
                    $day = now()->subDays($i)->toDateString();
                    $trend[$day] = (float) ($raw[$day] ?? 0);
                }

                // Collection rate
                $totalBilled    = (float) ($conn->table('particular_student')->sum('sales') ?? 0);
                $totalCollected = (float) ($conn->table('particular_student')->sum('credit') ?? 0);
                $collectionRate = $totalBilled > 0 ? round($totalCollected / $totalBilled * 100, 1) : 0;

                // Outstanding aging (SQL: bucket students-with-outstanding by days since last receipt)
                $agingRow = $conn->selectOne("
                    SELECT
                        SUM(CASE WHEN DATEDIFF(CURDATE(), COALESCE(lr.last_date,'2000-01-01')) < 30  THEN 1 ELSE 0 END) as recent,
                        SUM(CASE WHEN DATEDIFF(CURDATE(), COALESCE(lr.last_date,'2000-01-01')) BETWEEN 30 AND 89 THEN 1 ELSE 0 END) as overdue,
                        SUM(CASE WHEN DATEDIFF(CURDATE(), COALESCE(lr.last_date,'2000-01-01')) >= 90  THEN 1 ELSE 0 END) as critical
                    FROM (SELECT student_id FROM particular_student
                          GROUP BY student_id HAVING SUM(GREATEST(0,sales-credit)) > 0) owed
                    LEFT JOIN (SELECT student_id, MAX(date) as last_date FROM vouchers
                               WHERE voucher_type='Receipt' AND voided_at IS NULL GROUP BY student_id) lr
                           ON lr.student_id = owed.student_id
                ");

                // Expenses this month
                $monthExpenses = 0;
                try {
                    $monthExpenses = (float) $conn->table('expense_submissions')
                        ->whereIn('status', ['approved', 'partially_approved'])
                        ->whereYear('transaction_date', now()->year)
                        ->whereMonth('transaction_date', now()->month)
                        ->sum('total_amount');
                } catch (\Exception) {}

                $results[] = [
                    'id'               => $school->id,
                    'name'             => $school->name,
                    'logo'             => $school->logo ? asset('storage/' . $school->logo) : null,
                    'students'         => $conn->table('students')->where('status', 'active')->count(),
                    'today_collection' => (float) $conn->table('vouchers')->where('voucher_type', 'Receipt')->whereDate('date', today())->sum('debit'),
                    'month_collection' => (float) $conn->table('vouchers')->where('voucher_type', 'Receipt')->whereYear('date', now()->year)->whereMonth('date', now()->month)->sum('debit'),
                    'year_collection'  => (float) $conn->table('vouchers')->where('voucher_type', 'Receipt')->whereYear('date', now()->year)->sum('debit'),
                    'outstanding'      => (float) ($conn->table('particular_student')->selectRaw('SUM(GREATEST(0, sales - credit)) as t')->value('t') ?? 0),
                    'advance_total'    => (float) $conn->table('students')->where('status', 'active')->sum('advance_balance'),
                    'total_billed'     => $totalBilled,
                    'total_collected'  => $totalCollected,
                    'collection_rate'  => $collectionRate,
                    'month_expenses'   => $monthExpenses,
                    'aging'            => [
                        'recent'   => (int) ($agingRow->recent   ?? 0),
                        'overdue'  => (int) ($agingRow->overdue  ?? 0),
                        'critical' => (int) ($agingRow->critical ?? 0),
                    ],
                    'recent_receipts'  => $conn->table('vouchers')
                        ->join('students', 'vouchers.student_id', '=', 'students.id')
                        ->select('vouchers.id', 'vouchers.date', 'vouchers.debit as amount', 'students.id as student_id', 'students.name as student_name', 'vouchers.created_at')
                        ->where('vouchers.voucher_type', 'Receipt')
                        ->whereNull('vouchers.voided_at')
                        ->orderByDesc('vouchers.created_at')
                        ->limit(5)->get(),
                    'trend'            => $trend,
                    'error'            => null,
                ];
            } catch (\Exception $e) {
                $results[] = ['id' => $school->id, 'name' => $school->name, 'logo' => null, 'error' => $e->getMessage()];
            }
        }

        return response()->json($results);
    }

    // ── Paginated student list (per school) ──────────────────────────────────

    public function students(School $school, Request $request)
    {
        $this->tenantManager->switchToSchool($school);
        $q = trim($request->get('q', ''));

        $query = DB::connection('tenant')
            ->table('students as s')
            ->leftJoin('school_classes as c', 'c.id', '=', 's.class_id')
            ->select(
                's.id', 's.name', 's.advance_balance',
                DB::raw('c.name as class_name'),
                DB::raw('(SELECT SUM(GREATEST(0, ps.sales - ps.credit)) FROM particular_student ps WHERE ps.student_id = s.id) as outstanding')
            )
            ->where('s.status', 'active')
            ->orderBy('s.name');

        if ($q) {
            $query->where('s.name', 'like', "%{$q}%");
        }

        return response()->json($query->paginate(40));
    }

    // ── Direct school entry (no master-password; superadmin already auth'd) ──

    public function enterSchool(School $school, Request $request)
    {
        if (!$school->is_active) {
            return redirect()->route('superadmin.owner.dashboard')->with('error', 'School is inactive.');
        }

        $superAdmin = auth('superadmin')->user();

        session([
            'impersonating'                => true,
            'impersonating_super_admin_id' => $superAdmin->id,
            'current_school_slug'          => $school->slug,
            'current_school_id'            => $school->id,
        ]);

        $destinations = [
            'fee-entry'   => 'accountant.fee-entry',
            'sms'         => 'accountant.sms',
            'sms-logs'    => 'accountant.sms-logs',
            'expenses'    => 'accountant.expenses',
            'ledgers'     => 'accountant.ledgers',
            'students'    => 'accountant.students',
            'invoices'    => 'accountant.invoices-page',
            'particulars' => 'accountant.particulars',
        ];

        $to    = $request->get('to', '');
        $route = isset($destinations[$to]) ? route($destinations[$to]) : route('accountant.dashboard');

        return redirect($route)->with('success', "Now viewing {$school->name}");
    }

    // ── Cross-school student search ──────────────────────────────────────────

    public function search(Request $request)
    {
        $q       = trim($request->get('q', ''));
        $results = [];

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        foreach (School::active()->orderBy('name')->get() as $school) {
            try {
                $this->tenantManager->switchToSchool($school);

                $rows = DB::connection('tenant')
                    ->table('students as s')
                    ->leftJoin('school_classes as c', 'c.id', '=', 's.class_id')
                    ->select('s.id', 's.name', 's.advance_balance',
                             DB::raw('c.name as class_name'),
                             DB::raw('(SELECT SUM(GREATEST(0, ps.sales - ps.credit)) FROM particular_student ps WHERE ps.student_id = s.id) as outstanding'))
                    ->where('s.is_active', 1)
                    ->where('s.name', 'like', "%{$q}%")
                    ->orderBy('s.name')
                    ->limit(10)
                    ->get();

                foreach ($rows as $row) {
                    $results[] = array_merge((array) $row, [
                        'school_id'   => $school->id,
                        'school_name' => $school->name,
                    ]);
                }
            } catch (\Exception) {}
        }

        usort($results, fn($a, $b) => strcmp($a['name'], $b['name']));
        return response()->json(array_slice($results, 0, 30));
    }

    // ── Student detail (fees + recent receipts + books) ──────────────────────

    public function studentDetail(School $school, $studentId)
    {
        $this->tenantManager->switchToSchool($school);
        $conn = DB::connection('tenant');

        $student = $conn->table('students as s')
            ->leftJoin('school_classes as c', 'c.id', '=', 's.class_id')
            ->select('s.id', 's.name', 's.advance_balance', DB::raw('c.name as class_name'))
            ->where('s.id', $studentId)->first();

        if (!$student) return response()->json(['error' => 'Student not found'], 404);

        $particulars = $conn->table('particular_student as ps')
            ->join('particulars as p', 'p.id', '=', 'ps.particular_id')
            ->select('p.id', 'p.name', 'ps.sales', 'ps.credit',
                     DB::raw('GREATEST(0, ps.sales - ps.credit) as outstanding'))
            ->where('ps.student_id', $studentId)
            ->orderBy('p.name')
            ->get();

        $receipts = $conn->table('vouchers as v')
            ->leftJoin('books as b', 'b.id', '=', 'v.book_id')
            ->select('v.id', 'v.date', 'v.debit as amount', 'v.notes', DB::raw('b.name as book_name'), 'v.created_at')
            ->where('v.student_id', $studentId)
            ->where('v.voucher_type', 'Receipt')
            ->whereNull('v.voided_at')
            ->orderByDesc('v.created_at')
            ->limit(10)->get();

        $books = $conn->table('books')->select('id', 'name')->orderBy('name')->get();

        $schoolSettings = $conn->table('school_settings')->first();

        return response()->json([
            'student'          => $student,
            'particulars'      => $particulars,
            'receipts'         => $receipts,
            'books'            => $books,
            'school_name'      => $school->name,
            'school_id'        => $school->id,
            'school_logo'      => $school->logo ? asset('storage/' . $school->logo) : null,
            'school_settings'  => $schoolSettings,
        ]);
    }

    // ── Per-school analytics ─────────────────────────────────────────────────

    public function schoolAnalytics(School $school)
    {
        $this->tenantManager->switchToSchool($school);
        $conn = DB::connection('tenant');

        // ── Collection funnel ────────────────────────────────────────────────
        $totalBilled    = (float) ($conn->table('particular_student')->sum('sales') ?? 0);
        $totalCollected = (float) ($conn->table('particular_student')->sum('credit') ?? 0);
        $totalOutstanding = max(0, $totalBilled - $totalCollected);
        $collectionRate   = $totalBilled > 0 ? round($totalCollected / $totalBilled * 100, 1) : 0;

        // ── Per-particular breakdown ─────────────────────────────────────────
        $particulars = $conn->table('particular_student as ps')
            ->join('particulars as p', 'p.id', '=', 'ps.particular_id')
            ->select(
                'p.id', 'p.name',
                DB::raw('SUM(ps.sales) as total_billed'),
                DB::raw('SUM(ps.credit) as total_collected'),
                DB::raw('SUM(GREATEST(0, ps.sales - ps.credit)) as total_outstanding'),
                DB::raw('COUNT(DISTINCT ps.student_id) as student_count')
            )
            ->groupBy('p.id', 'p.name')
            ->having('total_billed', '>', 0)
            ->orderByDesc('total_outstanding')
            ->get()
            ->map(function ($p) {
                $p->collection_rate = $p->total_billed > 0
                    ? round($p->total_collected / $p->total_billed * 100, 1) : 0;
                return $p;
            });

        // ── Outstanding by class ─────────────────────────────────────────────
        $byClass = $conn->table('particular_student as ps')
            ->join('students as s', 's.id', '=', 'ps.student_id')
            ->leftJoin('school_classes as c', 'c.id', '=', 's.class_id')
            ->select(
                DB::raw('COALESCE(c.name, "No Class") as class_name'),
                DB::raw('COUNT(DISTINCT ps.student_id) as student_count'),
                DB::raw('SUM(GREATEST(0, ps.sales - ps.credit)) as outstanding')
            )
            ->where('s.status', 'active')
            ->groupBy('c.name')
            ->having('outstanding', '>', 0)
            ->orderByDesc('outstanding')
            ->get();

        // ── Top 10 debtors ───────────────────────────────────────────────────
        $topDebtors = $conn->table('particular_student as ps')
            ->join('students as s', 's.id', '=', 'ps.student_id')
            ->leftJoin('school_classes as c', 'c.id', '=', 's.class_id')
            ->leftJoin(DB::raw('(SELECT student_id, MAX(date) as last_receipt FROM vouchers WHERE voucher_type = "Receipt" AND voided_at IS NULL GROUP BY student_id) lr'), 'lr.student_id', '=', 'ps.student_id')
            ->select(
                's.id', 's.name',
                DB::raw('c.name as class_name'),
                DB::raw('SUM(GREATEST(0, ps.sales - ps.credit)) as outstanding'),
                'lr.last_receipt'
            )
            ->where('s.status', 'active')
            ->groupBy('s.id', 's.name', 'c.name', 'lr.last_receipt')
            ->having('outstanding', '>', 0)
            ->orderByDesc('outstanding')
            ->limit(10)
            ->get()
            ->map(function ($d) {
                $d->days_since_payment = $d->last_receipt
                    ? now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($d->last_receipt)->startOfDay())
                    : null;
                return $d;
            });

        // ── 6-month collection trend (monthly) ───────────────────────────────
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $months[] = ['label' => $m->format('M Y'), 'year' => $m->year, 'month' => $m->month];
        }
        $monthlyRaw = $conn->table('vouchers')
            ->where('voucher_type', 'Receipt')->whereNull('voided_at')
            ->where('date', '>=', now()->subMonths(5)->startOfMonth()->toDateString())
            ->selectRaw('YEAR(date) as y, MONTH(date) as m, SUM(debit) as total')
            ->groupByRaw('YEAR(date), MONTH(date)')->get()
            ->keyBy(fn($r) => $r->y . '-' . str_pad($r->m, 2, '0', STR_PAD_LEFT));
        $trend6 = array_map(fn($m) => [
            'label'  => $m['label'],
            'amount' => (float)($monthlyRaw[$m['year'].'-'.str_pad($m['month'],2,'0',STR_PAD_LEFT)]->total ?? 0),
        ], $months);

        // ── Expense budget burn ──────────────────────────────────────────────
        $expenseBudget    = [];
        $pendingApprovals = 0;
        try {
            $currentYearId = $conn->table('academic_years')->where('is_current', 1)->value('id');
            if ($currentYearId) {
                $expenseBudget = $conn->table('expense_categories as ec')
                    ->join('expense_category_plans as ecp', function ($j) use ($currentYearId) {
                        $j->on('ecp.expense_category_id', '=', 'ec.id')
                          ->where('ecp.academic_year_id', $currentYearId);
                    })
                    ->leftJoin(DB::raw(
                        '(SELECT expense_category_id, SUM(total_amount) as spent
                          FROM expense_submissions
                          WHERE status IN ("approved","partially_approved")
                          GROUP BY expense_category_id) es'
                    ), 'es.expense_category_id', '=', 'ec.id')
                    ->select(
                        'ec.name',
                        DB::raw('SUM(ecp.expected_amount) as budgeted'),
                        DB::raw('COALESCE(MAX(es.spent), 0) as spent')
                    )
                    ->where('ec.status', 'approved')
                    ->groupBy('ec.id', 'ec.name')
                    ->orderByDesc('budgeted')
                    ->limit(8)->get()
                    ->map(function ($e) {
                        $e->burn_rate = $e->budgeted > 0
                            ? round($e->spent / $e->budgeted * 100, 1) : 0;
                        $e->remaining = max(0, $e->budgeted - $e->spent);
                        return $e;
                    });
            }
            $pendingApprovals = $conn->table('expense_submissions')->where('status', 'pending')->count();
        } catch (\Exception) {}

        // ── SMS credits (central DB) ─────────────────────────────────────────
        $smsRemaining = max(0, (int)($school->sms_credits_assigned ?? 0) - (int)($school->sms_credits_used ?? 0));

        return response()->json([
            'school_name'      => $school->name,
            'school_id'        => $school->id,
            'funnel'           => compact('totalBilled', 'totalCollected', 'totalOutstanding', 'collectionRate'),
            'particulars'      => $particulars,
            'by_class'         => $byClass,
            'top_debtors'      => $topDebtors,
            'trend_6m'         => $trend6,
            'expense_budget'   => $expenseBudget,
            'pending_approvals'=> $pendingApprovals,
            'sms_remaining'    => $smsRemaining,
        ]);
    }

    // ── Invoice PDF download ─────────────────────────────────────────────────

    public function downloadInvoice(School $school, $studentId, Request $request)
    {
        $this->tenantManager->switchToSchool($school);
        return app(LedgerController::class)->exportStudentInvoicePdf($studentId, $request);
    }

    // ── Record receipt ───────────────────────────────────────────────────────

    public function recordReceipt(School $school, Request $request)
    {
        $validated = $request->validate([
            'date'           => 'required|date',
            'student_id'     => 'required|integer',
            'book_id'        => 'required|integer',
            'notes'          => 'nullable|string',
            'items'          => 'required|array|min:1',
            'items.*.particular_id' => 'required|integer',
            'items.*.amount'        => 'required|numeric|min:0.01',
            'advance_amount' => 'nullable|numeric|min:0',
        ]);

        $this->tenantManager->switchToSchool($school);
        $conn         = DB::connection('tenant');
        $totalAmount  = 0.0;
        $particularsApplied = [];
        $advanceAmount = (float) ($validated['advance_amount'] ?? 0);

        DB::connection('tenant')->beginTransaction();
        try {
            foreach ($validated['items'] as $item) {
                $particular  = $conn->table('particulars')->where('id', $item['particular_id'])->first();
                $amount      = (float) $item['amount'];
                $totalAmount += $amount;

                $pivot = $conn->table('particular_student')
                    ->where('student_id', $validated['student_id'])
                    ->where('particular_id', $item['particular_id'])
                    ->first();

                if (!$pivot) {
                    $conn->table('particular_student')->insert([
                        'student_id'     => $validated['student_id'],
                        'particular_id'  => $item['particular_id'],
                        'sales'          => 0, 'debit' => 0, 'credit' => 0, 'overpayment' => 0,
                        'created_at'     => now(), 'updated_at' => now(),
                    ]);
                    $pivot = $conn->table('particular_student')
                        ->where('student_id', $validated['student_id'])
                        ->where('particular_id', $item['particular_id'])
                        ->first();
                }

                $conn->table('particular_student')
                    ->where('student_id', $validated['student_id'])
                    ->where('particular_id', $item['particular_id'])
                    ->update(['credit' => (float) $pivot->credit + $amount, 'updated_at' => now()]);

                $particularsApplied[] = $particular->name ?? "Particular #{$item['particular_id']}";
            }

            if ($advanceAmount > 0) {
                $conn->table('students')->where('id', $validated['student_id'])
                    ->increment('advance_balance', $advanceAmount);
                $totalAmount += $advanceAmount;
            }

            $notes = trim($validated['notes'] ?? '') ?: sprintf(
                'Receipt — %s (%s)',
                implode(', ', $particularsApplied),
                $conn->table('students')->where('id', $validated['student_id'])->value('name')
            );

            $voucherId = $conn->table('vouchers')->insertGetId([
                'date'                  => $validated['date'],
                'student_id'            => $validated['student_id'],
                'particular_id'         => null,
                'book_id'               => $validated['book_id'],
                'voucher_type'          => 'Receipt',
                'debit'                 => $totalAmount,
                'credit'                => 0,
                'payment_by_receipt_to' => 'Receipt',
                'notes'                 => $notes,
                'created_at'            => now(),
                'updated_at'            => now(),
            ]);

            DB::connection('tenant')->commit();

            return response()->json(['voucher_id' => $voucherId, 'total' => $totalAmount], 201);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
