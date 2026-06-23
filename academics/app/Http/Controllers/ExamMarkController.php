<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Student;
use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExamMarkController extends Controller
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

  // public function selectClassExamSubject(Request $request)
  // {
  //   if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
  //   $this->switchTenantDB();

  //   $classes = ClassModel::on('tenant')->get();
  //   //modify to subject_teacher, where teacher_id is the id ,
  //   $exams = Exam::on('tenant')->get();
  //   $subjects = Subject::on('tenant')->get();

  //   $marks = collect();
  //   $students = collect();  // define empty collection here

  //   if ($request->filled(['exam_id', 'class_id', 'subject_id'])) {
  //     // Fetch students in the selected class
  //     $students = Student::on('tenant')->where('class_id', $request->class_id)->get();

  //     // Fetch existing marks for these students in this exam and subject
  //     $marks = ExamMark::on('tenant')
  //       ->where('exam_id', $request->exam_id)
  //       ->where('class_id', $request->class_id)
  //       ->where('subject_id', $request->subject_id)
  //       ->get()
  //       ->keyBy('student_id'); // key by student_id for easy lookup
  //   }

  //   retaurn view('content.dashboard.teacher.exams.select_mark_entry', compact('classes', 'exams', 'subjects', 'marks', 'students'));
  // }



  public function selectClassExamSubject(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    // 1️⃣ Get tenant DB from session
    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found in session.');
    }
    config(['database.connections.tenant.database' => $tenantDb]);

    // 2️⃣ Get logged-in user ID and role
    $userId = session('user_id');
    $userRole = session('role');

    if (!$userId) {
      return back()->with('error', 'User not found in session.');
    }

    if ($userRole === 'Academic') {
      // Academic role - load all classes and all subjects without filtering

      $classes = DB::connection('tenant')->table('classes')->get();
      $subjects = DB::connection('tenant')->table('subjects')->get();

      // Get all exams linked to all classes
      $examIds = DB::connection('tenant')->table('class_exam')
        ->pluck('exam_id')
        ->unique()
        ->values();

      $exams = DB::connection('tenant')->table('exams')
        ->whereIn('id', $examIds)
        ->orderBy('created_at', 'desc')
        ->get();
    } else {
      // Non-academic user - filter by teacher assignments

      // 3️⃣ Get teacher_id for logged-in user
      $teacher = DB::connection('tenant')->table('teachers')
        ->where('user_id', $userId)
        ->first();

      if (!$teacher) {
        return back()->with('error', 'Teacher not found.');
      }
      $teacherId = $teacher->id;

      // 4️⃣ Get classes assigned to this teacher
      $classIds = DB::connection('tenant')->table('subject_teacher')
        ->where('teacher_id', $teacherId)
        ->pluck('class_id')
        ->unique()
        ->values();

      if ($classIds->isEmpty()) {
        return back()->with('error', 'No classes assigned to this teacher.');
      }

      // 5️⃣ Get subjects assigned to this teacher
      $assignedSubjects = DB::connection('tenant')->table('subject_teacher')
        ->where('teacher_id', $teacherId)
        ->pluck('subject_id')
        ->unique()
        ->values();

      $subjects = DB::connection('tenant')->table('subjects')
        ->whereIn('id', $assignedSubjects)
        ->get();

      // 6️⃣ Get exams linked to these classes
      $examIds = DB::connection('tenant')->table('class_exam')
        ->whereIn('class_id', $classIds)
        ->pluck('exam_id')
        ->unique()
        ->values();

      $exams = DB::connection('tenant')->table('exams')
        ->whereIn('id', $examIds)
        ->orderBy('created_at', 'desc')
        ->get();

      // 7️⃣ Get classes info
      $classes = DB::connection('tenant')->table('classes')
        ->whereIn('id', $classIds)
        ->get();
    }

    // 8️⃣ If class_id, exam_id, subject_id are provided, validate and load students and marks
    $students = collect();
    $marks = collect();

    if ($request->filled(['class_id', 'exam_id', 'subject_id'])) {
      $request->validate([
        'class_id' => 'required|integer|exists:tenant.classes,id',
        'exam_id' => 'required|integer|exists:tenant.exams,id',
        'subject_id' => 'required|integer|exists:tenant.subjects,id',
      ]);

      // Load students for the class
      $students = DB::connection('tenant')->table('students')
        ->where('class_id', $request->class_id)
        ->get();

      // Load existing marks for those students for the given exam and subject (optional)
      $marks = ExamMark::on('tenant')
        ->where('exam_id', $request->exam_id)
        ->where('class_id', $request->class_id)
        ->where('subject_id', $request->subject_id)
        ->get()
        ->keyBy('student_id');
    }

    // 9️⃣ Return JSON if requested
    if ($request->input('toJson', false)) {
      return response()->json([
        'teacher' => $teacher ?? null,
        'classes' => $classes,
        'subjects' => $subjects,
        'exams' => $exams,
        'students' => $students,
        'marks' => $marks,
      ]);
    }

    return view('content.dashboard.teacher.exams.select_mark_entry', compact(
      'classes',
      'exams',
      'subjects',
      'marks',
      'students'
    ));
  }




  public function loadStudents(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $request->validate([
      'class_id' => 'required|integer|exists:tenant.classes,id',
      'exam_id' => 'required|integer|exists:tenant.exams,id',
      'subject_id' => 'required|integer|exists:tenant.subjects,id',
    ]);

    $students = Student::on('tenant')->where('class_id', $request->class_id)->get();

    return view('exams.enter_marks', [
      'students' => $students,
      'class_id' => $request->class_id,
      'exam_id' => $request->exam_id,
      'subject_id' => $request->subject_id,
    ]);
  }

  public function submitMarks(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    try {
      $request->validate([
        'exam_id' => 'required|exists:tenant.exams,id',
        'class_id' => 'required|exists:tenant.classes,id',
        'subject_id' => 'required|exists:tenant.subjects,id',
        'marks' => 'required|array',
        'marks.*.student_id' => 'required|exists:tenant.students,id',
        'marks.*.marks' => 'required|numeric|min:0|max:100',
      ]);

      foreach ($request->marks as $entry) {
        ExamMark::on('tenant')->updateOrCreate(
          [
            'exam_id' => $request->exam_id,
            'student_id' => $entry['student_id'],
            'subject_id' => $request->subject_id,
            'class_id' => $request->class_id,
          ],
          [
            'marks' => $entry['marks'],
            'submitted_by_teacher' => true,       // mark as submitted
            'verified_by_academic' => false,      // reset academic verification on changes
          ]
        );
      }

      return redirect()->route('examMarks.select')->with('success', 'Marks saved successfully.');
    } catch (\Exception $e) {
      return redirect()->back()->withInput()->with('error', 'An error occurred: ' . $e->getMessage());
    }
  }


  public function downloadPdf(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $exam_id = $request->input('exam_id');
    $class_id = $request->input('class_id');
    $subject_id = $request->input('subject_id');

    if (!$exam_id || !$class_id) {
      return redirect()->back()->with('error', 'Exam and Class are required.');
    }

    // Fetch exam and class (used in both cases)
    $exam = Exam::on('tenant')->find($exam_id);
    $class = Classroom::on('tenant')->find($class_id);

    if ($subject_id == 'all') {
      // Fetch all results for the class and exam across all subjects
      $results = ExamMark::on('tenant')
        ->where('exam_id', $exam_id)
        ->where('class_id', $class_id)
        ->with('student', 'subject')
        ->get()
        ->groupBy('student_id'); // Grouped by student for easier table generation

      if ($results->isEmpty()) {
        return redirect()->back()->with('error', 'No results found to export.');
      }

      $subjects = Subject::on('tenant')->get(); // Needed for table headers

      $pdf = Pdf::loadView('pdf.result_pdf_all_subjects', [
        'results' => $results,
        'subjects' => $subjects,
        'exam' => $exam,
        'class' => $class,
      ]);
    } else {
      // Specific subject result
      $results = ExamMark::on('tenant')->with('student')
        ->where('exam_id', $exam_id)
        ->where('class_id', $class_id)
        ->where('subject_id', $subject_id)
        ->get();

      if ($results->isEmpty()) {
        return redirect()->back()->with('error', 'No results found to export.');
      }

      $subject = Subject::on('tenant')->find($subject_id);

      $pdf = Pdf::loadView('pdf.result_pdf', compact('results', 'exam', 'class', 'subject'));
    }

    return $pdf->download('ResultSheet_' . now()->format('Ymd_His') . '.pdf');
  }
}
