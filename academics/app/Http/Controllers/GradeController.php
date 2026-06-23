<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GradeController extends Controller
{
  // Display all grades and optionally staff/roles (optional for now)
  public function index(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found in session.');
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Define all possible types manually
    $allTypes = ['division', 'standard', 'individual']; // add more if you have

    // Fetch counts for existing types
    $counts = DB::connection('tenant')
      ->table('grades')
      ->select('type', DB::raw('count(*) as count'))
      ->groupBy('type')
      ->pluck('count', 'type')
      ->toArray();

    // Build result with zero counts if none found
    $gradeTypes = collect($allTypes)->map(function ($type) use ($counts) {
      return (object)[
        'type' => $type,
        'count' => $counts[$type] ?? 0,
      ];
    });

    return view('content.dashboard.owner.classes.manage_grades', compact('gradeTypes'));
  }



  public function showGradesByType(Request $request, $type)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }
    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found in session.');
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $grades = DB::connection('tenant')
      ->table('grades')
      ->where('type', $type)
      ->orderBy('min_mark')
      ->get();


    return response()->json(['grades' => $grades]);
  }



  // Store new grade
  public function store(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $request->validate([
      'type' => 'required|in:division,standard,individual',
      'name' => 'required|string|max:50',
      'min_mark' => 'required|numeric|min:0|max:100',
      'max_mark' => 'required|numeric|min:0|max:100',
    ]);

    if ($request->min_mark > $request->max_mark) {
      return back()->with('error', 'Min mark cannot be greater than max mark.');
    }

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found in session.');
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Check for overlapping grades
    $overlappingGrade = DB::connection('tenant')->table('grades')
      ->where('type', $request->type)
      ->where(function ($query) use ($request) {
        $query->whereRaw('? BETWEEN min_mark AND max_mark', [$request->min_mark])
          ->orWhereRaw('? BETWEEN min_mark AND max_mark', [$request->max_mark])
          ->orWhere(function ($query) use ($request) {
            $query->where('min_mark', '>=', $request->min_mark)
              ->where('max_mark', '<=', $request->max_mark);
          });
      })
      ->first();

    if ($overlappingGrade) {
      return back()->with('error', 'The grade range overlaps with an existing grade of the same type.');
    }

    DB::connection('tenant')->table('grades')->insert([
      'type' => $request->type,
      'name' => $request->name,
      'min_mark' => $request->min_mark,
      'max_mark' => $request->max_mark,
      'created_at' => now(),
      'updated_at' => now(),
    ]);

    return redirect()->route('owner.grades.index')->with('success', 'Grade added successfully.');
  }

  // Delete grade
  public function destroy(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found in session.');
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    DB::connection('tenant')->table('grades')->where('id', $id)->delete();

    return back()->with('success', 'Grade deleted successfully.');
  }
}
