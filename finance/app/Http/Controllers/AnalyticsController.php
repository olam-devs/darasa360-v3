<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Particular;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Voucher;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function getClassPaymentStatsForRange(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $dateFrom = Carbon::parse($validated['from_date'])->startOfDay();
        $dateTo = Carbon::parse($validated['to_date'])->endOfDay();

        return response()->json([
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'class_stats' => $this->getClassPaymentStats($dateFrom, $dateTo),
        ]);
    }

    public function getAnalytics($period = 'month')
    {
        [$dateFrom, $dateTo] = $this->getPeriodDates($period);

        // Fee Expected/Collected should depend ONLY on student particular assignments (NOT book deposits).
        // Scholarships reduce the expected amount (forgiven), and collections are what has been paid (pivot credit).
        $assignmentTotals = DB::table('particular_student as ps')
            ->leftJoin('scholarships as sch', function ($join) {
                $join->on('sch.student_id', '=', 'ps.student_id')
                    ->on('sch.particular_id', '=', 'ps.particular_id')
                    ->where('sch.is_active', '=', 1)
                    // null-safe match on academic_year_id (both null or equal)
                    ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
            })
            ->selectRaw('COALESCE(SUM(ps.sales), 0) as expected_gross')
            ->selectRaw('COALESCE(SUM(ps.credit), 0) as collected_total')
            ->selectRaw('COALESCE(SUM(COALESCE(sch.forgiven_amount, 0)), 0) as scholarships_total')
            ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0)), 0) as expected_net')
            ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0) - ps.credit, 0)), 0) as outstanding_net')
            ->first();

        $expectedGross = (float) ($assignmentTotals->expected_gross ?? 0);
        $expectedNet = (float) ($assignmentTotals->expected_net ?? 0);
        $totalCollectedPivot = (float) ($assignmentTotals->collected_total ?? 0);
        $totalScholarships = (float) ($assignmentTotals->scholarships_total ?? 0);

        // Period collections for trend/cards should also exclude non-fee receipts (bank deposits etc).
        // Receipt vouchers are stored as DR in canonical accountant view.
        $totalCollections = (float) Voucher::whereBetween('date', [$dateFrom, $dateTo])
            ->where('voucher_type', 'Receipt')
            ->whereNotNull('student_id')
            ->whereNotNull('particular_id')
            ->sum('debit');

        // Total Expenses (Payments are stored as CR in canonical accountant view)
        $totalExpenses = Voucher::whereBetween('date', [$dateFrom, $dateTo])
            ->where('voucher_type', 'Payment')
            ->sum('credit');

        // Active students
        $activeStudents = Student::where('status', 'active')->count();

        // Outstanding balances (net of scholarships)
        $outstandingBalance = (float) ($assignmentTotals->outstanding_net ?? 0);

        // Collection trend for line graph (daily for week/month, monthly for year)
        $collectionTrend = $this->getCollectionTrend($period, $dateFrom, $dateTo);

        // Books distribution for pie chart (fee collections only, filtered by period)
        $booksDistribution = $this->getBooksDistribution($dateFrom, $dateTo);

        // Actual current balances in books (after all deposits/withdrawals/fees/cuts/adjustments)
        $booksBalances = $this->getBooksBalances();

        // Particulars data for bar chart
        $particularsData = $this->getParticularsData();

        // Student payment completion status by class (collected amounts match selected period)
        $classStats = $this->getClassPaymentStats($dateFrom, $dateTo);

        return response()->json([
            'period' => $period,
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'summary' => [
                // Assignment-based summary (not period-bound)
                'total_expected_gross' => $expectedGross,
                'total_scholarships' => $totalScholarships,
                'total_expected' => $expectedNet,
                'total_collected_pivot' => $totalCollectedPivot,
                'outstanding_balance' => $outstandingBalance,

                // Period-bound fee collections (exclude book deposits)
                'total_collections' => $totalCollections,
                'total_expenses' => $totalExpenses,
                'net_income' => $totalCollections - $totalExpenses,
                'active_students' => $activeStudents,

                // Book balances (actual money in books)
                'books_total_balance' => (float) ($booksBalances['total_balance'] ?? 0),
            ],
            'collection_trend' => $collectionTrend,
            'books_distribution' => $booksDistribution,
            'books_balances' => $booksBalances,
            'particulars_data' => $particularsData,
            'class_stats' => $classStats,
        ]);
    }

    private function getPeriodDates($period)
    {
        $now = now();

        return match ($period) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'weekly', 'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'monthly', 'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'yearly', 'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            default => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
        };
    }

    private function feeReceiptsInRange($dateFrom, $dateTo)
    {
        return Voucher::whereBetween('date', [$dateFrom, $dateTo])
            ->where('voucher_type', 'Receipt')
            ->whereNotNull('student_id')
            ->whereNotNull('particular_id');
    }

    private function getCollectionTrend($period, $dateFrom, $dateTo)
    {
        if ($period === 'today') {
            // Hourly for today
            $collections = $this->feeReceiptsInRange($dateFrom, $dateTo)
                ->selectRaw('HOUR(created_at) as hour, SUM(debit) as amount')
                ->groupBy('hour')
                ->orderBy('hour')
                ->get()
                ->keyBy(fn ($row) => (int) $row->hour);

            $trend = [];
            for ($i = 0; $i < 24; $i++) {
                $collection = $collections->get($i);
                $trend[] = [
                    'label' => sprintf('%02d:00', $i),
                    'expected' => 0, // We don't track hourly expectations
                    'amount' => $collection ? (float) $collection->amount : 0,
                ];
            }

            return $trend;
        }

        if (in_array($period, ['weekly', 'week', 'monthly', 'month'])) {
            // Daily
            $collections = $this->feeReceiptsInRange($dateFrom, $dateTo)
                ->select(DB::raw('DATE(date) as date'), DB::raw('SUM(debit) as amount'))
                ->groupBy(DB::raw('DATE(date)'))
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            $trend = [];
            $current = $dateFrom->copy();
            while ($current <= $dateTo) {
                $dateKey = $current->toDateString();
                $collection = $collections->get($dateKey);

                $trend[] = [
                    'label' => $current->format('M d'),
                    'expected' => 0,
                    'amount' => $collection ? (float) $collection->amount : 0,
                ];
                $current->addDay();
            }

            return $trend;
        }

        // Yearly - monthly aggregation
        $collections = $this->feeReceiptsInRange($dateFrom, $dateTo)
            ->selectRaw('MONTH(date) as month, SUM(debit) as amount')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $trend = [];
        for ($i = 1; $i <= 12; $i++) {
            $collection = $collections->get($i);
            $trend[] = [
                'label' => date('M', mktime(0, 0, 0, $i, 1)),
                'expected' => 0,
                'amount' => $collection ? (float) $collection->amount : 0,
            ];
        }

        return $trend;
    }

    private function getBooksDistribution($dateFrom, $dateTo)
    {
        $books = Book::all();
        $distribution = [];

        foreach ($books as $book) {
            // Get collections (credits from receipts) for this book in the period
            $collections = Voucher::where('book_id', $book->id)
                ->where('voucher_type', 'Receipt')
                ->whereBetween('date', [$dateFrom, $dateTo])
                ->whereNotNull('student_id')
                ->whereNotNull('particular_id')
                ->sum('debit');

            if ($collections > 0) {
                $distribution[] = [
                    'name' => $book->name,
                    'amount' => $collections,
                ];
            }
        }

        return $distribution;
    }

    private function getBooksBalances(): array
    {
        $rows = DB::table('books as b')
            ->leftJoin('vouchers as v', 'v.book_id', '=', 'b.id')
            ->select('b.id', 'b.name', 'b.opening_balance')
            ->selectRaw('COALESCE(SUM(v.debit - v.credit), 0) as movements')
            ->groupBy('b.id', 'b.name', 'b.opening_balance')
            ->orderBy('b.name')
            ->get();

        $books = $rows->map(function ($r) {
            $opening = (float) ($r->opening_balance ?? 0);
            $mov = (float) ($r->movements ?? 0);

            return [
                'id' => (int) $r->id,
                'name' => $r->name,
                'balance' => $opening + $mov,
            ];
        })->values()->all();

        return [
            'books' => $books,
            'total_balance' => array_reduce($books, fn ($c, $b) => $c + (float) $b['balance'], 0.0),
        ];
    }

    private function getParticularsData()
    {
        $particulars = Particular::where('is_active', true)->get();
        $data = [];

        foreach ($particulars as $particular) {
            $row = DB::table('particular_student as ps')
                ->leftJoin('scholarships as sch', function ($join) {
                    $join->on('sch.student_id', '=', 'ps.student_id')
                        ->on('sch.particular_id', '=', 'ps.particular_id')
                        ->where('sch.is_active', '=', 1)
                        ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
                })
                ->where('ps.particular_id', $particular->id)
                ->selectRaw('COALESCE(SUM(ps.sales), 0) as expected_gross')
                ->selectRaw('COALESCE(SUM(ps.credit), 0) as collected')
                ->selectRaw('COALESCE(SUM(COALESCE(sch.forgiven_amount, 0)), 0) as scholarships')
                ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0)), 0) as expected_net')
                ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0) - ps.credit, 0)), 0) as outstanding')
                ->first();

            $expectedGross = (float) ($row->expected_gross ?? 0);
            $expectedNet = (float) ($row->expected_net ?? 0);
            $collected = (float) ($row->collected ?? 0);
            $scholarships = (float) ($row->scholarships ?? 0);
            $outstanding = (float) ($row->outstanding ?? 0);

            if ($expectedGross > 0 || $collected > 0 || $scholarships > 0) {
                $data[] = [
                    'particular_id' => $particular->id,
                    'particular_name' => $particular->name,
                    'expected' => $expectedNet,
                    'collected' => $collected,
                    'outstanding' => $outstanding,
                    'scholarships' => $scholarships,
                ];
            }
        }

        return $data;
    }

    private function getClassPaymentStats($dateFrom = null, $dateTo = null)
    {
        $classes = SchoolClass::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        $stats = [];

        foreach ($classes as $class) {
            $studentIds = $class->students()
                ->where('status', 'active')
                ->pluck('id')
                ->all();

            if ($studentIds === []) {
                continue;
            }

            $perStudentExpected = DB::table('particular_student as ps')
                ->leftJoin('scholarships as sch', function ($join) {
                    $join->on('sch.student_id', '=', 'ps.student_id')
                        ->on('sch.particular_id', '=', 'ps.particular_id')
                        ->where('sch.is_active', '=', 1)
                        ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
                })
                ->whereIn('ps.student_id', $studentIds)
                ->groupBy('ps.student_id')
                ->select('ps.student_id')
                ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0)), 0) as expected')
                ->get();

            $expectedByStudent = $perStudentExpected
                ->keyBy('student_id')
                ->map(fn ($r) => (float) ($r->expected ?? 0));

            // Collected: either pivot total (all-time) or receipts within date range
            if ($dateFrom && $dateTo) {
                $collectedByStudent = Voucher::whereBetween('date', [$dateFrom, $dateTo])
                    ->where('voucher_type', 'Receipt')
                    ->whereNotNull('student_id')
                    ->whereNotNull('particular_id')
                    ->whereIn('student_id', $studentIds)
                    ->select('student_id', DB::raw('SUM(debit) as collected'))
                    ->groupBy('student_id')
                    ->get()
                    ->keyBy('student_id')
                    ->map(fn ($r) => (float) ($r->collected ?? 0));
            } else {
                $collectedByStudent = DB::table('particular_student as ps')
                    ->whereIn('ps.student_id', $studentIds)
                    ->groupBy('ps.student_id')
                    ->select('ps.student_id')
                    ->selectRaw('COALESCE(SUM(ps.credit), 0) as collected')
                    ->get()
                    ->keyBy('student_id')
                    ->map(fn ($r) => (float) ($r->collected ?? 0));
            }

            $totalStudents = count($studentIds);
            $completedStudents = collect($studentIds)->filter(function ($sid) use ($expectedByStudent, $collectedByStudent) {
                $expected = (float) ($expectedByStudent[$sid] ?? 0);
                $collected = (float) ($collectedByStudent[$sid] ?? 0);

                return $expected > 0 && $collected >= $expected;
            })->count();

            $totalExpected = (float) $expectedByStudent->sum();
            $totalCollected = (float) $collectedByStudent->sum();

            $collectionRate = $totalExpected > 0
                ? ($totalCollected / $totalExpected) * 100
                : 0;

            $stats[] = [
                'class_name' => $class->name,
                'total_students' => $totalStudents,
                'completed_students' => $completedStudents,
                'expected_amount' => $totalExpected,
                'collected_amount' => $totalCollected,
                'collection_rate' => round($collectionRate, 1),
            ];
        }

        return $stats;
    }

    // Add helper endpoint for getting all particulars
    public function getParticulars()
    {
        $particulars = Particular::where('is_active', true)
            ->select('id', 'name')
            ->get();

        return response()->json($particulars);
    }

    // Add helper endpoint for student payment summary
    public function getStudentPaymentSummary($studentId)
    {
        $validated = request()->validate([
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $dateFrom = ! empty($validated['from_date'])
            ? Carbon::parse($validated['from_date'])->startOfDay()
            : null;
        $dateTo = ! empty($validated['to_date'])
            ? Carbon::parse($validated['to_date'])->endOfDay()
            : null;

        $student = Student::with(['schoolClass', 'particulars'])->findOrFail($studentId);

        // Expected (net of scholarships)
        $expectedRow = DB::table('particular_student as ps')
            ->leftJoin('scholarships as sch', function ($join) {
                $join->on('sch.student_id', '=', 'ps.student_id')
                    ->on('sch.particular_id', '=', 'ps.particular_id')
                    ->where('sch.is_active', '=', 1)
                    ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
            })
            ->where('ps.student_id', $student->id)
            ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0)), 0) as expected_net')
            ->first();

        $totalExpectedNet = (float) ($expectedRow->expected_net ?? 0);

        // Collected: either pivot total (all-time) or receipts within date range
        if ($dateFrom && $dateTo) {
            $totalCollected = (float) Voucher::whereBetween('date', [$dateFrom, $dateTo])
                ->where('voucher_type', 'Receipt')
                ->where('student_id', $student->id)
                ->whereNotNull('particular_id')
                ->sum('debit');
        } else {
            $totalCollected = (float) DB::table('particular_student')
                ->where('student_id', $student->id)
                ->sum('credit');
        }

        $totalAssignments = $student->particulars->count();

        // Completed assignments definition aligns with expected vs collected for this view
        $completedAssignments = 0;
        if ($totalAssignments > 0) {
            $perParticularExpected = DB::table('particular_student as ps')
                ->leftJoin('scholarships as sch', function ($join) {
                    $join->on('sch.student_id', '=', 'ps.student_id')
                        ->on('sch.particular_id', '=', 'ps.particular_id')
                        ->where('sch.is_active', '=', 1)
                        ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
                })
                ->where('ps.student_id', $student->id)
                ->groupBy('ps.particular_id')
                ->select('ps.particular_id')
                ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0)), 0) as expected')
                ->get()
                ->keyBy('particular_id')
                ->map(fn ($r) => (float) ($r->expected ?? 0));

            if ($dateFrom && $dateTo) {
                $perParticularCollected = Voucher::whereBetween('date', [$dateFrom, $dateTo])
                    ->where('voucher_type', 'Receipt')
                    ->where('student_id', $student->id)
                    ->whereNotNull('particular_id')
                    ->select('particular_id', DB::raw('SUM(debit) as collected'))
                    ->groupBy('particular_id')
                    ->get()
                    ->keyBy('particular_id')
                    ->map(fn ($r) => (float) ($r->collected ?? 0));
            } else {
                $perParticularCollected = DB::table('particular_student as ps')
                    ->where('ps.student_id', $student->id)
                    ->groupBy('ps.particular_id')
                    ->select('ps.particular_id')
                    ->selectRaw('COALESCE(SUM(ps.credit), 0) as collected')
                    ->get()
                    ->keyBy('particular_id')
                    ->map(fn ($r) => (float) ($r->collected ?? 0));
            }

            $completedAssignments = $perParticularExpected->keys()->filter(function ($pid) use ($perParticularExpected, $perParticularCollected) {
                $expected = (float) ($perParticularExpected[$pid] ?? 0);
                $collected = (float) ($perParticularCollected[$pid] ?? 0);

                return $expected > 0 && $collected >= $expected;
            })->count();
        }

        return response()->json([
            'name' => $student->name,
            'student_reg_no' => $student->student_reg_no,
            'class' => $student->schoolClass->name ?? $student->class,
            'total_expected' => $totalExpectedNet,
            'total_collected' => $totalCollected,
            'collection_rate' => $totalExpectedNet > 0 ? round(($totalCollected / $totalExpectedNet) * 100, 1) : 0,
            'total_assignments' => $totalAssignments,
            'completed_assignments' => $completedAssignments,
        ]);
    }

    // Custom date range analytics
    public function getCustomAnalytics(Request $request)
    {
        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $dateFrom = Carbon::parse($validated['from_date'])->startOfDay();
        $dateTo = Carbon::parse($validated['to_date'])->endOfDay();

        $assignmentTotals = DB::table('particular_student as ps')
            ->leftJoin('scholarships as sch', function ($join) {
                $join->on('sch.student_id', '=', 'ps.student_id')
                    ->on('sch.particular_id', '=', 'ps.particular_id')
                    ->where('sch.is_active', '=', 1)
                    ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
            })
            ->selectRaw('COALESCE(SUM(ps.sales), 0) as expected_gross')
            ->selectRaw('COALESCE(SUM(ps.credit), 0) as collected_total')
            ->selectRaw('COALESCE(SUM(COALESCE(sch.forgiven_amount, 0)), 0) as scholarships_total')
            ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0)), 0) as expected_net')
            ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0) - ps.credit, 0)), 0) as outstanding_net')
            ->first();

        $expectedGross = (float) ($assignmentTotals->expected_gross ?? 0);
        $expectedNet = (float) ($assignmentTotals->expected_net ?? 0);
        $totalCollectedPivot = (float) ($assignmentTotals->collected_total ?? 0);
        $totalScholarships = (float) ($assignmentTotals->scholarships_total ?? 0);

        // Total Fee Collections (exclude non-student receipts like bank deposits)
        $totalCollections = (float) Voucher::whereBetween('date', [$dateFrom, $dateTo])
            ->where('voucher_type', 'Receipt')
            ->whereNotNull('student_id')
            ->whereNotNull('particular_id')
            ->sum('debit');

        // Total Expenses (Payments are stored as CR in canonical accountant view)
        $totalExpenses = Voucher::whereBetween('date', [$dateFrom, $dateTo])
            ->where('voucher_type', 'Payment')
            ->sum('credit');

        // Active students
        $activeStudents = Student::where('status', 'active')->count();

        $outstandingBalance = (float) ($assignmentTotals->outstanding_net ?? 0);

        // Collection trend for line graph
        $collectionTrend = $this->getCustomCollectionTrend($dateFrom, $dateTo);

        // Books distribution for pie chart (filtered by period)
        $booksDistribution = $this->getBooksDistribution($dateFrom, $dateTo);

        // Particulars data for bar chart
        $particularsData = $this->getParticularsData();

        // Student payment completion status by class (filtered to custom date range)
        $classStats = $this->getClassPaymentStats($dateFrom, $dateTo);

        $booksBalances = $this->getBooksBalances();

        return response()->json([
            'period' => 'custom',
            'date_from' => $dateFrom->toDateString(),
            'date_to' => $dateTo->toDateString(),
            'summary' => [
                'total_expected_gross' => $expectedGross,
                'total_scholarships' => $totalScholarships,
                'total_expected' => $expectedNet,
                'total_collected_pivot' => $totalCollectedPivot,
                'total_collections' => $totalCollections,
                'total_expenses' => $totalExpenses,
                'net_income' => $totalCollections - $totalExpenses,
                'active_students' => $activeStudents,
                'outstanding_balance' => $outstandingBalance,
                'books_total_balance' => (float) ($booksBalances['total_balance'] ?? 0),
            ],
            'collection_trend' => $collectionTrend,
            'books_distribution' => $booksDistribution,
            'books_balances' => $booksBalances,
            'particulars_data' => $particularsData,
            'class_stats' => $classStats,
        ]);
    }

    private function getCustomCollectionTrend($dateFrom, $dateTo)
    {
        $daysDiff = $dateFrom->diffInDays($dateTo);

        // Daily aggregation for ranges up to 60 days
        if ($daysDiff <= 60) {
            $collections = Voucher::whereBetween('date', [$dateFrom, $dateTo])
                ->where('voucher_type', 'Receipt')
                ->whereNotNull('student_id')
                ->whereNotNull('particular_id')
                ->select(DB::raw('DATE(date) as date'), DB::raw('SUM(debit) as amount'))
                ->groupBy(DB::raw('DATE(date)'))
                ->orderBy('date')
                ->get()
                ->keyBy('date');

            $trend = [];
            $current = $dateFrom->copy();
            while ($current <= $dateTo) {
                $dateKey = $current->toDateString();
                $collection = $collections->get($dateKey);

                $trend[] = [
                    'label' => $current->format('M d'),
                    'expected' => 0,
                    'amount' => $collection ? (float) $collection->amount : 0,
                ];
                $current->addDay();
            }

            return $trend;
        }

        // Monthly aggregation for longer ranges
        $collections = Voucher::whereBetween('date', [$dateFrom, $dateTo])
            ->where('voucher_type', 'Receipt')
            ->whereNotNull('student_id')
            ->whereNotNull('particular_id')
            ->selectRaw('YEAR(date) as year, MONTH(date) as month, SUM(debit) as amount')
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->keyBy(function ($item) {
                return $item->year.'-'.str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });

        $trend = [];
        $current = $dateFrom->copy()->startOfMonth();
        while ($current <= $dateTo) {
            $key = $current->format('Y-m');
            $collection = $collections->get($key);

            $trend[] = [
                'label' => $current->format('M Y'),
                'expected' => 0,
                'amount' => $collection ? (float) $collection->amount : 0,
            ];
            $current->addMonth();
        }

        return $trend;
    }
}
