<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StaffPromotionController extends Controller
{
  // Show the staff promotion form
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

    // Get all roles
    $roles = DB::connection('tenant')->table('school_roles')->get();

    $staff = [];
    $selectedRole = null;

    if ($request->filled('from_role_id')) {
      $selectedRole = DB::connection('tenant')->table('school_roles')->where('id', $request->from_role_id)->first();

      if ($selectedRole) {
        $staff = DB::connection('tenant')->table('schoolUsers')
          ->leftJoin('school_roles', 'schoolUsers.role_id', '=', 'school_roles.id')
          ->where('schoolUsers.role_id', $selectedRole->id)
          ->select('schoolUsers.*', 'school_roles.name as role_name')
          ->get();
      }
    }

    return view('content.dashboard.owner.staff.promote_staff', compact('roles', 'staff', 'selectedRole'));
  }

  // Fetch staff from a specific role
  public function getStaff(Request $request, $roleId)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $tenantDb = session('school_db');
    if (!$tenantDb) return response()->json(['error', 'Tenant DB not found'], 400);

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $staff = DB::connection('tenant')
      ->table('schoolUsers')
      ->where('role_id', $roleId)
      ->get();
    return response()->json($staff);
  }

  // Promote staff to another role
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
        'staff_ids' => 'required|array|min:1',
        'to_role_id' => 'required|exists:tenant.school_roles,id',
      ]);

      DB::connection('tenant')->table('schoolUsers')
        ->whereIn('id', $request->staff_ids)
        ->update(['role_id' => $request->to_role_id]);

      return redirect()->route('owner.staff-promotion.index')->with('success', 'Staff promoted successfully.');
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
