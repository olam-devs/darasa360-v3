<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubjectController extends Controller
{


  public function listSubjects(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $query = Subject::on('tenant')->orderBy('name');

    if ($request->filled('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('name', 'like', "%{$search}%")
          ->orWhere('code', 'like', "%{$search}%");
      });
    }

    $subjects = $query->paginate(10)->appends($request->query());

    return view('content.dashboard.owner.subjects.manage_subjects', compact('subjects'));
  }


  public function showAddSubjectForm(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    return view('content.dashboard.owner.subjects.create');
  }

  public function saveNewSubject(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $request->validate([
      'name' => 'required|string|unique:tenant.subjects,name',
      'code' => 'nullable|string|unique:tenant.subjects,code',
      // Remove description if no longer in DB
    ]);

    Subject::on('tenant')->create($request->only('name', 'code'));

    return redirect()->route('subjects.list')->with('success', 'Subject added successfully.');
  }

  public function showEditSubjectForm(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $subject = Subject::on('tenant')->findOrFail($id);

    return view('content.dashboard.owner.subjects.edit', compact('subject'));
  }

  public function updateSubject(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $subject = Subject::on('tenant')->findOrFail($id);

    $request->validate([
      'name' => 'required|string|unique:tenant.subjects,name,' . $subject->id,
      'code' => 'nullable|string|unique:tenant.subjects,code,' . $subject->id,
      // Remove description
    ]);

    $subject->update($request->only('name', 'code'));

    return redirect()->route('subjects.list')->with('success', 'Subject updated successfully.');
  }

  public function deleteSubject(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $subject = Subject::on('tenant')->findOrFail($id);

    $subject->delete();

    return redirect()->route('subjects.list')->with('success', 'Subject deleted successfully.');
  }

  private function switchTenantDB()
  {
    $tenantDb = session('school_db');
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');
  }
}
