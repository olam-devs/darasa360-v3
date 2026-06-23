<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Particular;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\Voucher;
use App\Services\ActivityLogger;
use App\Traits\HasSchoolContext;
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

    public function dashboard()
    {
        $settings = SchoolSetting::getSettings();

        $totalStudents = Student::count();
        $totalBooks = Book::where('is_active', true)->count();
        $totalParticulars = Particular::count();

        $totalFeesExpected = (float) DB::table('particular_student')
            ->selectRaw('COALESCE(SUM(COALESCE(sales, 0) + COALESCE(debit, 0)), 0) as total')
            ->value('total');
        $totalFeesCollected = (float) DB::table('particular_student')
            ->selectRaw('COALESCE(SUM(COALESCE(credit, 0)), 0) as total')
            ->value('total');
        $collectionRate = $totalFeesExpected > 0
            ? ($totalFeesCollected / $totalFeesExpected) * 100
            : 0;

        $recentTransactions = Voucher::with(['student', 'particular', 'book'])
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
            'school'
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
