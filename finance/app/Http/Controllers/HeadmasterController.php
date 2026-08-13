<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Book;
use App\Models\Particular;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Voucher;
use App\Services\ActivityLogger;
use App\Traits\HasSchoolContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HeadmasterController extends Controller
{
    use HasSchoolContext;

    protected ActivityLogger $activityLogger;

    public function __construct(ActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    protected function logAction(string $action, string $description): void
    {
        $schoolId = $this->getSchoolId();
        $headmasterId = session('headmaster_id');
        $headmasterName = session('headmaster_name');

        if ($schoolId && $headmasterId) {
            $this->activityLogger->logHeadmasterPortalAction(
                $schoolId,
                $headmasterId,
                $headmasterName ?? 'Unknown',
                $action,
                $description
            );
        }
    }

    protected function headmasterModule(string $view, array $extra = [])
    {
        return view($view, array_merge([
            'settings' => SchoolSetting::getSettings(),
            'portalLayout' => 'layouts.headmaster',
            'readOnly' => true,
        ], $extra));
    }

    public function dashboard(Request $request)
    {
        $settings = SchoolSetting::getSettings();

        $academicYears = AcademicYear::where('is_active', true)
            ->orderBy('start_date', 'desc')
            ->get();
        $currentYear = AcademicYear::current();

        $selectedYearId = $request->get('year_id');
        $selectedYear = $selectedYearId
            ? $academicYears->firstWhere('id', (int) $selectedYearId)
            : $currentYear;

        $yearFilter = $selectedYear?->id;

        $totalStudents = Student::where('status', 'active')->count();
        $totalBooks = Book::where('is_active', true)->count();
        $totalParticulars = Particular::count();

        // Expected and collected for the selected academic year, matching analytics logic.
        $totals = DB::table('particular_student as ps')
            ->leftJoin('scholarships as sch', function ($join) {
                $join->on('sch.student_id', '=', 'ps.student_id')
                    ->on('sch.particular_id', '=', 'ps.particular_id')
                    ->where('sch.is_active', '=', 1)
                    ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
            })
            ->when($yearFilter, fn ($q) => $q->where('ps.academic_year_id', $yearFilter))
            ->selectRaw('COALESCE(SUM(GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0)), 0) as expected_net')
            ->selectRaw('COALESCE(SUM(ps.credit), 0) as collected')
            ->first();

        $totalFeesExpected = (float) ($totals->expected_net ?? 0);
        $totalFeesCollected = (float) ($totals->collected ?? 0);
        $collectionRate = $totalFeesExpected > 0
            ? ($totalFeesCollected / $totalFeesExpected) * 100
            : 0;

        $recentTransactions = Voucher::with(['student', 'particular', 'book'])
            ->when(
                $selectedYear,
                fn ($q) => $q->whereBetween('date', [$selectedYear->start_date, $selectedYear->end_date])
            )
            ->latest()
            ->take(10)
            ->get();

        $school = $this->getCurrentSchool();

        return view('headmaster.dashboard', compact(
            'settings',
            'totalStudents',
            'totalBooks',
            'totalParticulars',
            'totalFeesExpected',
            'totalFeesCollected',
            'collectionRate',
            'recentTransactions',
            'school',
            'academicYears',
            'selectedYear',
        ));
    }

    public function ledgers()
    {
        return $this->headmasterModule('admin.accountant.modules.ledgers');
    }

    public function particularLedger()
    {
        return $this->headmasterModule('admin.accountant.modules.particular-ledger');
    }

    public function overdue()
    {
        return $this->headmasterModule('admin.accountant.modules.overdue');
    }

    public function invoices()
    {
        $classes = \App\Models\SchoolClass::where('is_active', true)->orderBy('display_order')->get();

        return $this->headmasterModule('admin.accountant.modules.invoices', [
            'classes' => $classes,
            'invoicePdfBase' => '/headmaster/invoices',
        ]);
    }
}
