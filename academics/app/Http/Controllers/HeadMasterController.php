<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StudentsFinanceExport;

class HeadMasterController extends Controller
{
  public function index(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }
    return view('content.dashboard.headmaster.home');
  }

  /**
   * Export students data for finance system
   */
  public function exportStudentsForFinance(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    // Set up tenant database connection
    $tenantDb = session('school_db');
    if (!$tenantDb) {
      abort(500, 'Tenant database not set in session.');
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $classId = $request->input('class_id');
    $filters = [
      'gender' => $request->input('gender'),
      'search' => $request->input('search'),
    ];

    return Excel::download(
      new StudentsFinanceExport($classId, $filters),
      'students_finance_' . now()->format('Y-m-d_His') . '.xlsx'
    );
  }
}
