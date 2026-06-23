<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
  // Show the promotion form
  public function index(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $tenantDb = session('school_db');
    if (!$tenantDb) return back()->with('error', 'Tenant database not found.');

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $classes = DB::connection('tenant')->table('classes')->get();

    $students = [];
    $selectedClass = null;

    if ($request->filled('from_class_id')) {
      $selectedClass = DB::connection('tenant')->table('classes')->where('id', $request->from_class_id)->first();

      if ($selectedClass) {
        $students = DB::connection('tenant')->table('students')
          ->leftJoin('streams', 'students.stream_id', '=', 'streams.id')
          ->where('students.class_id', $selectedClass->id)   // specify table here!
          ->select('students.*', 'streams.name as stream_name')
          ->get();
      }
    }

    return view('content.dashboard.owner.classes.promote_students', compact('classes', 'students', 'selectedClass'));
  }


  // Fetch students from a specific class
  public function getStudents(Request $request, $classId)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $tenantDb = session('school_db');
    if (!$tenantDb) return response()->json(['error' => 'Tenant DB not found'], 400);

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $students = DB::connection('tenant')
      ->table('students')
      ->where('class_id', $classId)
      ->get();
    return response()->json($students);
  }

  // Promote students to another class
  public function promote(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found.');
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    try {
      $request->validate([
        'student_ids' => 'required|array|min:1',
        'to_class_id' => 'required|exists:tenant.classes,id',
      ]);

      DB::connection('tenant')->table('students')
        ->whereIn('id', $request->student_ids)
        ->update(['class_id' => $request->to_class_id]);

      return redirect()->route('owner.promotions.index')->with('success', 'Students promoted successfully.');
    } catch (\Illuminate\Validation\ValidationException $e) {
      return redirect()->back()
        ->withErrors($e->validator)
        ->withInput();
    } catch (\Exception $e) {
      return redirect()->back()
        ->with('error', 'An error occurred during promotion: ' . $e->getMessage())
        ->withInput();
    }
  }
}
