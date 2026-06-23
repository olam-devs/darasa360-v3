<?php

namespace App\Http\Controllers;

use App\Models\{Exam, ClassModel, Classroom, Subject, Student, ExamMark};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamController extends Controller
{
  private function switchTenantDB()
  {
    $tenantDb = session('school_db');
    if (!$tenantDb) {
      abort(404, 'Tenant DB not found in session.');
    }
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');
  }

  public function index(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $exams = Exam::on('tenant')->latest()->get();
    $classes = Classroom::on('tenant')->orderBy('name')->get(); // Adjust model name if needed

    return view('content.dashboard.teacher.exams.index', compact('exams', 'classes'));
  }


  public function create(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $classes = ClassModel::on('tenant')->get();
    return view('exams.create', compact('classes'));
  }

  public function store(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $request->validate([
      'name' => 'required|string',
      'term' => 'required|string',
      'start_date' => 'required|date',
      'end_date' => 'required|date|after_or_equal:start_date',
      'classes' => 'required|array',
      'classes.*' => 'exists:tenant.classes,id',
    ]);

    $exam = Exam::on('tenant')->create($request->only(['name', 'term', 'year', 'start_date', 'end_date']));

    $exam->classes()->attach($request->input('classes'));

    return redirect()->route('exams.index')->with('success', 'Exam created successfully.');
  }

  public function destroy(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $exam = Exam::on('tenant')->with('classes')->findOrFail($id);

    // Detach all related classes
    $exam->classes()->detach();

    // Delete related exam marks (if model is ExamMark or similar)
    ExamMark::on('tenant')->where('exam_id', $id)->delete();

    // Delete the exam itself
    $exam->delete();

    return redirect()->route('exams.index')->with('success', 'Exam and related data deleted successfully.');
  }
}
