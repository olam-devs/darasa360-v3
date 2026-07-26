<?php

namespace App\Http\Controllers;

use App\Models\ExamResult;
use App\Models\School;
use App\Models\SchoolRole;
use App\Models\Student;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AcademicController extends Controller
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

    $schoolDb = session('school_db');
    $userId = session('user_id');

    if (!$schoolDb) return response()->json(['error' => 'Invalid school database'], 400);

    $this->configureTenantConnection($schoolDb);

    $user = DB::connection('tenant')->table('schoolUsers')->where('id', $userId)->first();
    $username = $user ? $user->username : 'User';


    $dashboardData = [
      'username' => $username,
    ];

    return view('content.dashboard.academic.home', compact('dashboardData'));
  }

  public function academicVerificationDashboard(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $this->switchTenantDB();

    $classes = DB::connection('tenant')->table('classes')->get();
    $subjects = DB::connection('tenant')->table('subjects')->get();

    return view('content.dashboard.academic.verification', [
      'classes' => $classes,
      'subjects' => $subjects
    ]);
  }

  public function getExamsByClass($classId, Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $this->switchTenantDB();

    $exams = DB::connection('tenant')
      ->table('class_exam')
      ->join('exams', 'class_exam.exam_id', '=', 'exams.id')
      ->where('class_exam.class_id', $classId)
      ->select('exams.id', 'exams.name', 'exams.created_at')
      ->orderBy('exams.created_at', 'desc')
      ->get();

    return response()->json($exams);
  }

  public function fetchVerificationData(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $this->switchTenantDB();

    $classId = $request->class_id;
    $examId = $request->exam_id;
    $subjectId = $request->subject_id; // new

    $query = DB::connection('tenant')->table('exams_marks')
      ->join('students', 'exams_marks.student_id', '=', 'students.id')
      ->join('subjects', 'exams_marks.subject_id', '=', 'subjects.id')
      ->where('exams_marks.class_id', $classId)
      ->where('exams_marks.exam_id', $examId);

    // filter only if subject_id is provided and not "all"
    if (!empty($subjectId) && strtolower($subjectId) !== 'all') {
      $query->where('exams_marks.subject_id', $subjectId);
    }

    $marks = $query->select(
      'exams_marks.*',
      'students.first_name',
      'students.last_name',
      'subjects.name as subject_name'
    )
      ->orderBy('students.first_name')
      ->orderBy('subjects.name')
      ->get();

    return response()->json($marks);
  }


  public function toggleVerification(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $this->switchTenantDB();

    $verified = (int) $request->input('verified', 0); // force integer

    DB::connection('tenant')->table('exams_marks')
      ->where('class_id', $request->class_id)
      ->where('exam_id', $request->exam_id)
      ->where('subject_id', $request->subject_id)
      ->where('student_id', $request->student_id)
      ->update(['verified_by_academic' => $verified]);

    return response()->json([
      'status' => 'success',
      'newStatus' => $verified
    ]);
  }


  public function batchVerification(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $this->switchTenantDB();

    $classId = $request->class_id;
    $examId = $request->exam_id;
    $verificationList = $request->verification_list ?? [];

    foreach ($verificationList as $item) {
      [$studentId, $subjectId] = explode('_', $item);

      DB::connection('tenant')->table('exams_marks')
        ->where('class_id', $classId)
        ->where('exam_id', $examId)
        ->where('subject_id', $subjectId)
        ->where('student_id', $studentId)
        ->update(['verified_by_academic' => true]);
    }

    return redirect()
      ->route('academic.verification')
      ->with('success', 'Selected entries verified successfully.');
  }




  public function showGenerateResultsForm(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $this->switchTenantDB();

    $userRole = session('role');
    if ($userRole !== 'Academic') abort(403);

    // Get all classes and exams where all subjects marks are submitted & verified
    $classes = DB::connection('tenant')->table('classes')->get();
    $exams = DB::connection('tenant')->table('exams')->orderBy('created_at', 'desc')->get();

    $availableClassExamPairs = [];
    foreach ($classes as $class) {
      foreach ($exams as $exam) {
        // Get only subjects assigned to this class
        $subjects = DB::connection('tenant')->table('subjects')
          ->join('classroom_subject', 'subjects.id', '=', 'classroom_subject.subject_id')
          ->where('classroom_subject.classroom_id', $class->id)
          ->select('subjects.*')
          ->get();

        $allSubjectsVerified = true;

        foreach ($subjects as $subject) {
          $markExists = DB::connection('tenant')->table('exams_marks')
            ->where('class_id', $class->id)
            ->where('exam_id', $exam->id)
            ->where('subject_id', $subject->id)
            ->where('submitted_by_teacher', true)
            ->where('verified_by_academic', true)
            ->exists();

          if (!$markExists) {
            $allSubjectsVerified = false;
            break;
          }
        }

        if ($allSubjectsVerified) {
          $availableClassExamPairs[] = [
            'class' => $class,
            'exam' => $exam,
          ];
        }
      }
    }

    // Get all unique roles from the database (excluding Academic)
    $schoolRoles = SchoolRole::where('name', '!=', 'Academic')
      ->pluck('name')
      ->toArray();


    $gradingTypes = ['individual', 'standard', 'division'];


    return view('content.dashboard.academic.generate_results_form', compact(
      'availableClassExamPairs',
      'gradingTypes',
      'schoolRoles'
    ));
  }


  public function studentResults(Request $request)
  {
    $toJson = $request->input('toJson', false);
    $userId = $request->input('user_id');
    $schoolCode = $request->input('school_code');

    // 1️⃣ Handle JSON API requests (no session)
    if ($toJson && $userId && $schoolCode) {
      $school = School::where('school_code', $schoolCode)->first();
      if (!$school) {
        return response()->json(['status' => 'error', 'message' => 'Invalid school code'], 400);
      }
      $this->configureTenantConnection($school->database_url);

      // For JSON requests, we need to determine user role from database, not session
      $user = DB::connection('tenant')->table('schoolUsers')
        ->where('id', $userId)
        ->first();

      if (!$user) {
        return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
      }

      // Get role name from roles table if needed
      $userRole = DB::connection('tenant')->table('school_roles')
        ->where('id', $user->role_id)
        ->value('name');
    }
    // 2️⃣ Handle web requests (session-based)
    else {
      if (!(new HelperController)->isLoggedIn($request)) {
        return redirect('/login');
      }

      if (!session()->has('user_id') || !session()->has('school_db')) {
        return redirect()->route('login')->withErrors('Session expired. Please login again.');
      }

      $this->switchTenantDB();
      $userId = session('user_id');
      $userRole = session('role');
    }

    // Initialize class & exam list
    $availableClassExamPairs = [];
    $examsList = []; // This will store the formatted exams for JSON response

    if (in_array($userRole, ['teacher', 'class_teacher'])) {
      // Get teacher id
      $teacher = DB::connection('tenant')->table('teachers')
        ->where('user_id', $userId)
        ->first();

      if (!$teacher) {
        return $toJson
          ? response()->json(['status' => 'error', 'message' => 'Teacher record not found'], 404)
          : abort(403, 'Teacher record not found');
      }

      // Get classes assigned to this teacher via subject_teacher
      $classIds = DB::connection('tenant')->table('subject_teacher')
        ->where('teacher_id', $teacher->id)
        ->pluck('class_id')
        ->unique()
        ->toArray();

      $classes = DB::connection('tenant')->table('classes')
        ->whereIn('id', $classIds)
        ->get();
    } elseif ($userRole === 'student') {
      // Get student record
      $student = DB::connection('tenant')->table('students')
        ->where('user_id', $userId)
        ->first();

      if (!$student) {
        return $toJson
          ? response()->json(['status' => 'error', 'message' => 'Student record not found'], 404)
          : abort(403, 'Student record not found');
      }

      // Fetch only the class of this student
      $classes = DB::connection('tenant')->table('classes')
        ->where('id', $student->class_id)
        ->get();
    } else {
      // For other roles (optional)
      $classes = DB::connection('tenant')->table('classes')->get();
    }

    // Get all exams
    $exams = DB::connection('tenant')->table('exams')->orderBy('created_at', 'desc')->get();

    // Filter class-exam pairs where all subjects marks are submitted & verified
    $verifiedResults = [];
    foreach ($classes as $class) {
      foreach ($exams as $exam) {
        // Get only subjects assigned to this class
        $subjects = DB::connection('tenant')->table('subjects')
          ->join('classroom_subject', 'subjects.id', '=', 'classroom_subject.subject_id')
          ->where('classroom_subject.classroom_id', $class->id)
          ->select('subjects.*')
          ->get();

        $allSubjectsVerified = true;

        foreach ($subjects as $subject) {
          $markExists = DB::connection('tenant')->table('exams_marks')
            ->where('class_id', $class->id)
            ->where('exam_id', $exam->id)
            ->where('subject_id', $subject->id)
            ->where('submitted_by_teacher', true)
            ->where('verified_by_academic', true)
            ->exists();

          if (!$markExists) {
            $allSubjectsVerified = false;
            break;
          }
        }

        if ($allSubjectsVerified) {
          $availableClassExamPairs[] = [
            'class' => $class,
            'exam' => $exam,
          ];

          // For JSON response, format the data as expected by Flutter app
          $examsList[] = [
            'id' => $exam->id,
            'name' => $exam->name,
            'term' => $exam->term ?? 'Term 1', // Use actual term if available, otherwise default
            'exam_date' => $exam->start_date,
          ];
        }
      }
    }

    // Return JSON response if requested
    if ($toJson) {
      return response()->json([
        'status' => 'success',
        'exams' => $examsList, // Changed to match Flutter expectation
        'total_available' => count($examsList)
      ]);
    }

    return view('content.dashboard.academic.student_results', compact('availableClassExamPairs'));
  }



  public function getStudentResults(Request $request)
  {
    $toJson = $request->input('toJson', false);
    $userId = $request->input('user_id');
    $schoolCode = $request->input('school_code');
    $examId = $request->input('exam_id');

    // 1️⃣ Handle JSON API requests (no session)
    if ($toJson && $userId && $schoolCode) {
      try {
        $school = School::where('school_code', $schoolCode)->first();
        if (!$school) {
          return response()->json(['status' => 'error', 'message' => 'Invalid school code'], 400);
        }
        $this->configureTenantConnection($school->database_url);

        // Get user role from database for API requests
        $user = DB::connection('tenant')->table('schoolUsers')
          ->where('id', $userId)
          ->first();

        if (!$user) {
          return response()->json(['status' => 'error', 'message' => 'User not found'], 404);
        }

        $userRole = DB::connection('tenant')->table('school_roles')
          ->where('id', $user->role_id)
          ->value('name');

        $userId = $user->id; // Use the user ID from database

      } catch (\Exception $e) {
        return response()->json(['status' => 'error', 'message' => 'Database connection failed'], 500);
      }
    }
    // 2️⃣ Handle web requests (session-based)
    else {
      if (!(new HelperController)->isLoggedIn($request)) {
        return $toJson ? response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401) : redirect('/login');
      }

      if (!session()->has('user_id') || !session()->has('school_db')) {
        return $toJson ? response()->json(['status' => 'error', 'message' => 'Session expired'], 401) : redirect()->route('login')->withErrors('Session expired. Please login again.');
      }

      $this->switchTenantDB();
      $userId = session('user_id');
      $userRole = session('role');
    }

    // ==================== COMMON LOGIC FOR BOTH WEB AND JSON ====================

    // Fetch classes based on role
    if (in_array($userRole, ['Teacher', 'ClassTeacher'])) {
      // Get teacher_id
      $teacher = DB::connection('tenant')->table('teachers')->where('user_id', $userId)->first();
      $classIds = [];
      if ($teacher) {
        $classIds = DB::connection('tenant')->table('subject_teacher')
          ->where('teacher_id', $teacher->id)
          ->pluck('class_id')
          ->unique()
          ->toArray();
      }
      $classes = DB::connection('tenant')->table('classes')
        ->whereIn('id', $classIds)
        ->get();
    } elseif ($userRole === 'Student') {
      $student = DB::connection('tenant')->table('students')->where('user_id', $userId)->first();

      $classes = collect();
      if ($student) {
        $class = DB::connection('tenant')->table('classes')->find($student->class_id);
        if ($class) $classes->push($class);
      }
    } else {
      // Default: load all classes for other roles
      $classes = DB::connection('tenant')->table('classes')->get();
    }

    $exams = DB::connection('tenant')->table('exams')->get();

    // Build available class-exam pairs
    $availableClassExamPairs = [];
    $availableExams = []; // For JSON response

    foreach ($classes as $cls) {
      foreach ($exams as $ex) {
        $availableClassExamPairs[] = [
          'class' => $cls,
          'exam'  => $ex,
        ];

        // For JSON response
        $availableExams[] = [
          'id' => $ex->id,
          'name' => $ex->name,
          'term' => $ex->term ?? 'Term 1',
          'exam_date' => $ex->start_date ?? $ex->created_at,
        ];
      }
    }


    // ==================== JSON RESPONSE HANDLING ====================
    if ($toJson) {
      // If exam_id is provided, return specific exam results
      if ($examId) {
        return $this->getExamResultsJson($userId, $examId, $userRole);
      }

      // Otherwise return list of available exams
      return response()->json([
        'status' => 'success',
        'exams' => $availableExams,
        'total_available' => count($availableExams)
      ]);
    }

    // ==================== WEB REQUEST HANDLING ====================
    try {
      // If form submitted
      if ($request->isMethod('post')) {
        $validated = $request->validate([
          'class_id' => 'required|exists:tenant.classes,id',
          'exam_id'  => 'required|exists:tenant.exams,id',
        ]);

        $classId = $validated['class_id'];
        $examId  = $validated['exam_id'];

        $resultsQuery = ExamResult::where('class_id', $classId)
          ->where('exam_id', $examId);

        // For Student role: only show their own results AND check sent_to
        if ($userRole === 'Student') {
          $student = DB::connection('tenant')->table('students')->where('user_id', $userId)->first();
          if ($student) {
            $resultsQuery->where('student_id', $student->id);
          }
        }

        $results = $resultsQuery->get()->filter(function ($result) use ($userRole, $userId) {
          $sentTo = json_decode($result->sent_to, true) ?? [];

          // For Student role, also check if the result belongs to them
          if ($userRole === 'Student') {
            $student = DB::connection('tenant')->table('students')->where('user_id', $userId)->first();
            return $student && $result->student_id == $student->id && in_array($userRole, $sentTo);
          }

          return in_array($userRole, $sentTo);
        });

        if ($results->isEmpty()) {
          return redirect()->back()
            ->with('error', 'You are not authorized to view these results or they haven\'t been shared with your role yet.');
        }

        // Ranking logic
        $allResults = ExamResult::where('class_id', $classId)
          ->where('exam_id', $examId)
          ->get();

        $studentTotals = [];
        foreach ($allResults as $res) {
          // Process subjects the same way as in download method
          $subjects = json_decode($res->subjects, true);
          if (is_string($subjects)) {
            $subjects = json_decode($subjects, true);
          }
          if (!is_array($subjects)) {
            $subjects = [];
          }

          $totalMarks = array_sum(array_column($subjects, 'marks'));
          $studentTotals[$res->student_id] = $totalMarks;
        }

        arsort($studentTotals);
        $rankings = [];
        $rank = 1;
        $prevMarks = null;
        $counter = 1;
        foreach ($studentTotals as $studentId => $total) {
          if ($prevMarks !== null && $total < $prevMarks) {
            $rank = $counter;
          }
          $rankings[$studentId] = $rank;
          $prevMarks = $total;
          $counter++;
        }

        $totalStudents = count($studentTotals);

        // Process results for display - consistent with download method
        foreach ($results as $res) {
          // Process subjects the same way as in download method
          $subjects = json_decode($res->subjects, true);
          if (is_string($subjects)) {
            $subjects = json_decode($subjects, true);
          }
          if (!is_array($subjects)) {
            $subjects = [];
          }

          // Clean up subject names and ensure proper structure
          $subjects = array_map(function ($subject) {
            return [
              'subject_name' => $subject['subject_name'] ?? 'N/A',
              'marks' => $subject['marks'] ?? 0,
              'grade' => $subject['grade'] ?? '',
              'teacher' => $subject['teacher'] ?? 'Unknown Teacher',
              'comments' => $subject['comments'] ?? 'No comments'
            ];
          }, $subjects);

          $res->subjects = $subjects;
          $res->total_marks = array_sum(array_column($subjects, 'marks'));
          $res->position = $rankings[$res->student_id] ?? null;
          $res->position_out_of = $totalStudents;
        }

        $class = DB::connection('tenant')->table('classes')->find($classId);
        $exam  = DB::connection('tenant')->table('exams')->find($examId);

        return view('content.dashboard.academic.student_results', compact(
          'availableClassExamPairs',
          'results',
          'class',
          'exam',
          'userRole',
          'totalStudents'
        ));
      }

      // Initial page load
      return view('content.dashboard.academic.student_results', compact(
        'availableClassExamPairs'
      ));
    } catch (\Illuminate\Validation\ValidationException $e) {
      return redirect()->back()
        ->withErrors($e->validator)
        ->withInput();
    } catch (\Exception $e) {
      return redirect()->back()
        ->with('error', 'Error fetching results: ' . $e->getMessage())
        ->withInput();
    }
  }

  // Helper method for JSON exam results
  private function getExamResultsJson($userId, $examId, $userRole)
  {
    try {
      // Get student record
      $student = DB::connection('tenant')->table('students')
        ->where('user_id', $userId)
        ->first();

      if (!$student) {
        return response()->json(['status' => 'error', 'message' => 'Student record not found'], 404);
      }

      // Get exam results for this student
      $results = ExamResult::where('exam_id', $examId)
        ->where('student_id', $student->id)
        ->get();

      if ($results->isEmpty()) {
        return response()->json(['status' => 'error', 'message' => 'No results found for this exam'], 404);
      }

      // Check if results are sent to this user's role
      $authorized = false;
      foreach ($results as $result) {
        $sentTo = json_decode($result->sent_to, true) ?? [];
        if (in_array($userRole, $sentTo)) {
          $authorized = true;
          break;
        }
      }

      if (!$authorized) {
        return response()->json(['status' => 'error', 'message' => 'Not authorized to view these results'], 403);
      }

      // Process results for JSON response
      $subjectResults = [];
      $totalMarks = 0;
      $subjectCount = 0;

      foreach ($results as $result) {
        $subjects = json_decode($result->subjects, true) ?? [];
        foreach ($subjects as $subject) {
          $subjectResults[] = [
            'name' => $subject['subject_name'] ?? 'N/A',
            'grade' => $subject['grade'] ?? 'N/A',
            'score' => (int) ($subject['marks'] ?? 0),
            'teacher' => "Mr./Ms. " . substr($subject['name'] ?? 'Teacher', 0, 3),
            'comments' => "Performance in " . ($subject['name'] ?? 'this subject'),
          ];
          $totalMarks += $subject['marks'] ?? 0;
          $subjectCount++;
        }
      }

      $averageScore = $subjectCount > 0 ? round($totalMarks / $subjectCount, 1) : 0;

      return response()->json([
        'status' => 'success',
        'student' => [
          'name' => $student->first_name . ' ' . $student->last_name,
          'class' => DB::connection('tenant')->table('classes')->where('id', $student->class_id)->value('name') ?? 'N/A',
          'stream' => $student->stream_id ? DB::connection('tenant')->table('streams')->where('id', $student->stream_id)->value('name') : 'N/A',
        ],
        'overallGrade' => $this->calculateOverallGrade($averageScore),
        'averageScore' => $averageScore,
        'subjects' => $subjectResults,
      ]);
    } catch (\Exception $e) {
      return response()->json(['status' => 'error', 'message' => 'Error fetching exam results: ' . $e->getMessage()], 500);
    }
  }

  // Helper method to calculate overall grade
  private function calculateOverallGrade($average)
  {
    if ($average >= 80) return 'A';
    if ($average >= 70) return 'B';
    if ($average >= 60) return 'C';
    if ($average >= 50) return 'D';
    return 'F';
  }




  public function downloadStudentResults(Request $request)
  {
    try {
      // Authentication and authorization checks
      if (!(new HelperController)->isLoggedIn($request)) {
        return redirect('/login');
      }

      $this->switchTenantDB();

      // $userRole = session('role');
      // if ($userRole !== 'Academic') {
      //   abort(403);
      // }

      // Validate request
      $validated = $request->validate([
        'class_id' => 'required|exists:tenant.classes,id',
        'exam_id'  => 'required|exists:tenant.exams,id',
      ]);

      $classId = $validated['class_id'];
      $examId  = $validated['exam_id'];

      // Fetch class & exam details
      $class = DB::connection('tenant')->table('classes')->find($classId);
      $exam  = DB::connection('tenant')->table('exams')->find($examId);

      // Fetch results
      $results = DB::connection('tenant')->table('exam_results')
        ->where('class_id', $classId)
        ->where('exam_id', $examId)
        ->get();

      if ($results->isEmpty()) {
        return redirect()->back()->with('error', 'No results found for this exam.');
      }

      $formattedResults = collect();
      $allSubjects = collect();

      foreach ($results as $result) {
        // Decode subjects safely
        $subjects = json_decode($result->subjects, true);

        // If still a string (double-encoded), decode again
        if (is_string($subjects)) {
          $subjects = json_decode($subjects, true);
        }

        // Ensure we have an array
        if (!is_array($subjects)) {
          $subjects = [];
        }

        // Remove invalid characters from subject names
        $subjects = array_map(function ($sub) {
          if (!isset($sub['subject_name'])) $sub['subject_name'] = 'N/A';
          $sub['subject_name'] = iconv('UTF-8', 'UTF-8//IGNORE', $sub['subject_name']);
          return $sub;
        }, $subjects);

        // Track all unique subjects
        foreach ($subjects as $subject) {
          $allSubjects->put($subject['subject_name'], true);
        }

        $formattedResults->push([
          'student' => [
            'name' => $result->student_name,
          ],
          'raw_subjects' => $subjects,
          'total_marks' => $result->total_marks ?? 0,
          'average_marks' => $result->average_marks ?? 0,
          'final_grade' => $result->final_grade ?? 'N/A',
        ]);
      }

      $allSubjects = $allSubjects->keys()->sort()->values();

      $School = School::where('school_code', session('school_code'))->first();
      // Generate PDF
      $pdf = PDF::loadView('content.dashboard.academic.student_results_pdf', [
        'class' => $class,
        'exam' => $exam,
        'School' => $School->name,
        'gradingType' => $results->first()->grading_type ?? 'N/A',
        'results' => $formattedResults,
        'allSubjects' => $allSubjects,
      ]);

      $pdf->setPaper('A4', 'landscape');
      $pdf->setOption('isHtml5ParserEnabled', true);
      $pdf->setOption('isRemoteEnabled', true);
      $pdf->setOption('defaultFont', 'dejavu sans');

      $filename = "Results_{$class->name}_{$exam->name}.pdf";

      return $pdf->download($filename);
    } catch (\Exception $e) {
      return $e;
      return redirect()->back()
        ->with('error', 'Error generating PDF: ' . $e->getMessage())
        ->withInput();
    }
  }



  public function generateResults(Request $request)
  {
    try {
      // Authentication and authorization checks
      if (!(new HelperController)->isLoggedIn($request)) {
        return redirect('/login');
      }

      $this->switchTenantDB();

      $userRole = session('role');
      if ($userRole !== 'Academic') {
        abort(403);
      }

      // Decode send_to_roles if sent as JSON string
      if ($request->has('send_to_roles') && is_string($request->send_to_roles)) {
        $decodedRoles = json_decode($request->send_to_roles, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
          throw new \Exception('Invalid roles format');
        }
        $request->merge(['send_to_roles' => $decodedRoles]);
      }

      // Validate request data
      $validated = $request->validate([
        'class_id' => 'required|exists:tenant.classes,id',
        'exam_id' => 'required|exists:tenant.exams,id',
        'grading_type' => 'required|in:standard,division',
        'send_to_roles' => 'nullable|array',
      ]);

      $classId = $validated['class_id'];
      $examId = $validated['exam_id'];
      $gradingType = $validated['grading_type'];
      $sendToRoles = (array) ($validated['send_to_roles'] ?? []);

      // Ensure Academic role is always included
      if (!in_array('Academic', $sendToRoles)) {
        $sendToRoles[] = 'Academic';
      }

      // Begin transaction
      DB::connection('tenant')->beginTransaction();

      try {
        // Fetch students and grades
        $students = DB::connection('tenant')->table('students')
          ->where('class_id', $classId)
          ->get();

        $standardGrades = DB::connection('tenant')->table('grades')->where('type', 'standard')->get();
        $divisionGrades = DB::connection('tenant')->table('grades')->where('type', 'division')->get();
        $individualGrades = DB::connection('tenant')->table('grades')->where('type', 'individual')->get();

        $results = [];

        foreach ($students as $student) {
          // Get student marks
          $studentMarks = DB::connection('tenant')->table('exams_marks')
            ->join('subjects', 'exams_marks.subject_id', '=', 'subjects.id')
            ->where('exams_marks.student_id', $student->id)
            ->where('exams_marks.exam_id', $examId)
            ->where('exams_marks.class_id', $classId)
            ->select('exams_marks.*', 'subjects.name as subject_name')
            ->get();

          $totalMarks = $studentMarks->sum('marks');
          $subjectCount = $studentMarks->count();
          $subjectResults = [];

          foreach ($studentMarks as $mark) {
            $indGrade = $individualGrades->first(fn($g) => $mark->marks >= $g->min_mark && $mark->marks <= $g->max_mark);
            $subjectResults[] = [
              'subject_name' => $mark->subject_name,
              'marks' => $mark->marks,
              'grade' => $indGrade?->name ?? 'N/A'
            ];
          }

          // Final grade calculation
          $finalGrade = $this->calculateFinalGrade(
            $gradingType,
            $studentMarks,
            $standardGrades,
            $divisionGrades,
            $subjectCount,
            $totalMarks
          );

          $results[] = (object)[
            'student_name' => trim("{$student->first_name} {$student->middle_name} {$student->last_name}"),
            'total_marks' => $totalMarks,
            'average_marks' => $subjectCount > 0 ? round($totalMarks / $subjectCount, 2) : null,
            'final_grade' => $finalGrade,
            'subjects' => $subjectResults,
          ];

          // Merge previous sent_to with new roles
          $existingResult = ExamResult::where('class_id', $classId)
            ->where('exam_id', $examId)
            ->where('student_id', $student->id)
            ->first();

          $mergedRoles = $sendToRoles;
          if ($existingResult && !empty($existingResult->sent_to)) {
            $previousRoles = json_decode($existingResult->sent_to, true) ?? [];
            $mergedRoles = array_unique(array_merge($previousRoles, $sendToRoles));
          }

          $isVerified = !empty($mergedRoles);

          ExamResult::updateOrCreate(
            [
              'class_id' => $classId,
              'exam_id' => $examId,
              'student_id' => $student->id,
            ],
            [
              'student_name' => trim("{$student->first_name} {$student->middle_name} {$student->last_name}"),
              'total_marks' => $totalMarks,
              'average_marks' => $subjectCount > 0 ? round($totalMarks / $subjectCount, 2) : null,
              'final_grade' => $finalGrade,
              'is_verified' => $isVerified,
              'sent_to' => json_encode($mergedRoles),
              'grading_type' => $gradingType,
              'subjects' => json_encode($subjectResults),
              'sent_to_headteacher' => in_array('Headteacher', $mergedRoles),
              'sent_to_class_teacher' => in_array('ClassTeacher', $mergedRoles),
              'sent_to_student' => in_array('Student', $mergedRoles),
              'sent_to_academic' => in_array('Academic', $mergedRoles),
            ]
          );
        }

        DB::connection('tenant')->commit();

        // Prepare view data
        $class = DB::connection('tenant')->table('classes')->find($classId);
        $exam = DB::connection('tenant')->table('exams')->find($examId);
        $availableClassExamPairs = $this->getAvailableClassExamPairs();
        $schoolRoles = SchoolRole::whereNotIn('name', ['Academic', 'Admin', 'Owner'])->pluck('name')->toArray();
        $gradingTypes = ['individual', 'standard', 'division'];

        $message = !empty($sendToRoles)
          ? 'Results generated and sent successfully!'
          : 'Results generated successfully!';

        $previouslySentRoles = ExamResult::where('class_id', $classId)
          ->where('exam_id', $examId)
          ->pluck('sent_to')
          ->first();

        $selectedRoles = $previouslySentRoles ? json_decode($previouslySentRoles, true) : [];

        return view('content.dashboard.academic.generate_results_form', compact(
          'results',
          'class',
          'exam',
          'gradingType',
          'availableClassExamPairs',
          'gradingTypes',
          'schoolRoles',
          'selectedRoles'
        ))->with('successMessage', $message);
      } catch (\Exception $e) {
        DB::connection('tenant')->rollBack();
        throw $e;
      }
    } catch (\Illuminate\Validation\ValidationException $e) {
      return redirect()->back()
        ->withErrors($e->validator)
        ->withInput();
    } catch (\Exception $e) {
      return redirect()->back()
        ->with('error', 'Error generating results: ' . $e->getMessage())
        ->withInput();
    }
  }


  protected function calculateFinalGrade($gradingType, $studentMarks, $standardGrades, $divisionGrades, $subjectCount, $totalMarks)
  {
    if ($subjectCount === 0) {
      return null;
    }

    if ($gradingType === 'standard') {
      $average = $totalMarks / $subjectCount;
      $grade = $standardGrades->first(fn($g) => $average >= $g->min_mark && $average <= $g->max_mark);
      return $grade?->name ?? 'N/A';
    }

if ($gradingType === 'division') {
    $bestSeven = $studentMarks->sortByDesc('marks')->take(7);
    $totalPoints = 0;
    $hasF = false;

    foreach ($bestSeven as $mark) {
        $divGrade = $divisionGrades->first(fn($g) => $mark->marks >= $g->min_mark && $mark->marks <= $g->max_mark);
        $points = match ($divGrade?->name) {
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'F' => 5,
            default => 5
        };
        
        if ($divGrade?->name === 'F') {
            $hasF = true;
        }
        
        $totalPoints += $points;
    }

    // Return string with points included
    return match (true) {
        $hasF => "Fail ({$totalPoints} points - Has F grade)",
        $totalPoints <= 17 => "Division I.{$totalPoints}",
        $totalPoints <= 21 => "Division II.{$totalPoints}",
        $totalPoints <= 25 => "Division III.{$totalPoints}",
        $totalPoints <= 33 => "Division IV.{$totalPoints}",
        default => "Fail ({$totalPoints} points)"
    };
}
    return null;
  }

  protected function getAvailableClassExamPairs()
  {
    $classes = DB::connection('tenant')->table('classes')->get();
    $exams = DB::connection('tenant')->table('exams')->orderBy('created_at', 'desc')->get();
    $availablePairs = [];

    foreach ($classes as $classItem) {
      foreach ($exams as $examItem) {
        // Only check subjects assigned to this class
        $allSubjectsVerified = DB::connection('tenant')->table('subjects')
          ->join('classroom_subject', 'subjects.id', '=', 'classroom_subject.subject_id')
          ->where('classroom_subject.classroom_id', $classItem->id)
          ->whereNotExists(function ($query) use ($classItem, $examItem) {
            $query->select(DB::raw(1))
              ->from('exams_marks')
              ->where('class_id', $classItem->id)
              ->where('exam_id', $examItem->id)
              ->whereColumn('exams_marks.subject_id', 'subjects.id')
              ->where('submitted_by_teacher', true)
              ->where('verified_by_academic', true);
          })
          ->doesntExist();

        if ($allSubjectsVerified) {
          $availablePairs[] = [
            'class' => $classItem,
            'exam' => $examItem,
          ];
        }
      }
    }

    return $availablePairs;
  }






  public function downloadPdf($class_id, $exam_id, $grading_type)
  {

    return 'ok';
    $this->switchTenantDB();

    $class = DB::connection('tenant')->table('classes')->find($class_id);
    $exam = DB::connection('tenant')->table('exams')->find($exam_id);

    $students = DB::connection('tenant')->table('students')
      ->where('class_id', $class_id)
      ->get();

    $marksQuery = DB::connection('tenant')->table('exams_marks')
      ->select('student_id', DB::raw('AVG(marks) as avg_marks'))
      ->where('class_id', $class_id)
      ->where('exam_id', $exam_id)
      ->groupBy('student_id');

    $marks = $marksQuery->get()->keyBy('student_id');

    $grades = DB::connection('tenant')->table('grades')
      ->where('type', $grading_type)
      ->get();

    $results = [];

    foreach ($students as $student) {
      $avgMarks = $marks->has($student->id) ? round($marks[$student->id]->avg_marks, 2) : null;
      $gradeName = null;

      if ($avgMarks !== null) {
        foreach ($grades as $grade) {
          if ($avgMarks >= $grade->min_mark && $avgMarks <= $grade->max_mark) {
            $gradeName = $grade->name;
            break;
          }
        }
      }

      $results[] = (object)[
        'student_name' => trim("{$student->first_name} {$student->middle_name} {$student->last_name}"),
        'marks' => $avgMarks,
        'grade' => $gradeName,
      ];
    }

    // $pdf = PDF::loadView('content.dashboard.academic.generated_results_pdf', compact('results', 'class', 'exam', 'grading_type'));

    return $pdf->download("Results_{$class->name}_{$exam->name}.pdf");
  }


  public function downloadExamResults(Request $request)
  {
    try {
      // Authentication and authorization checks
      if (!(new HelperController)->isLoggedIn($request)) {
        return redirect('/login');
      }

      $this->switchTenantDB();

      $userRole = session('role');
      if ($userRole !== 'Academic') {
        abort(403);
      }

      // Validate request
      $validated = $request->validate([
        'class_id' => 'required|exists:tenant.classes,id',
        'exam_id' => 'required|exists:tenant.exams,id',
      ]);

      $classId = $validated['class_id'];
      $examId = $validated['exam_id'];

      // Fetch class & exam details
      $class = DB::connection('tenant')->table('classes')->find($classId);
      $exam = DB::connection('tenant')->table('exams')->find($examId);

      // Fetch already generated results from exam_results
      $results = DB::connection('tenant')->table('exam_results')
        ->where('class_id', $classId)
        ->where('exam_id', $examId)
        ->get();

      if ($results->isEmpty()) {
        return redirect()->back()->with('error', 'No results found for this exam.');
      }

      // Collect all subjects across students
      $allSubjects = collect();
      $formattedResults = collect();

      foreach ($results as $result) {
        // Decode subjects safely
        $subjects = json_decode($result->subjects, true);

        // If still a string (double-encoded), decode again
        if (is_string($subjects)) {
          $subjects = json_decode($subjects, true);
        }

        // Ensure valid array
        if (!is_array($subjects)) {
          $subjects = [];
        }

        // Track all unique subjects
        foreach ($subjects as $subject) {
          if (isset($subject['subject_name'])) {
            $allSubjects->put($subject['subject_name'], true);
          }
        }


        // Build formatted results
        $formattedResults->push([
          'student' => [
            'name' => $result->student_name,
          ],
          'raw_subjects' => $subjects,
          'total_marks' => $result->total_marks ?? 0,
          'average_marks' => $result->average_marks ?? 0,
          'final_grade' => $result->final_grade ?? 'N/A',
        ]);
      }

      // Sort subjects alphabetically
      $allSubjects = $allSubjects->keys()->sort()->values();

      $School = School::where('school_code', session('school_code'))->first();
      // Generate PDF
      $pdf = PDF::loadView('content.dashboard.academic.exam_results_pdf', [
        'class' => $class,
        'exam' => $exam,
        'school' => $School->name,
        'gradingType' => $results->first()->grading_type ?? 'N/A',
        'results' => $formattedResults,
        'allSubjects' => $allSubjects,
      ]);

      $pdf->setPaper('A4', 'landscape');
      $pdf->setOption('isHtml5ParserEnabled', true);
      $pdf->setOption('isRemoteEnabled', true);
      $pdf->setOption('defaultFont', 'dejavu sans');

      // Download PDF
      $filename = "Results_{$class->name}_{$exam->name}.pdf";
      return $pdf->download($filename);
    } catch (\Exception $e) {
      return redirect()->back()
        ->with('error', 'Error generating PDF: ' . $e->getMessage())
        ->withInput();
    }
  }







  /**
   * Example grading function
   */



  /**
   * Determine grade based on grading type
   */
  private function getGrade($average, $gradingType)
  {
    if ($gradingType === 'standard') {
      if ($average >= 80) return 'A';
      if ($average >= 70) return 'B';
      if ($average >= 60) return 'C';
      if ($average >= 50) return 'D';
      return 'F';
    }

    if ($gradingType === 'division') {
      if ($average >= 75) return 'Division I';
      if ($average >= 60) return 'Division II';
      if ($average >= 45) return 'Division III';
      if ($average >= 30) return 'Division IV';
      return 'Fail';
    }

    // Individual grading (per subject basis, not average)
    // handled in PDF loop (so just return "-")
    return '-';
  }




  public function sendToHeadteacher(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    // TODO: Implement logic to send generated results to headteacher dashboard
    // This could involve saving status in DB and/or sending notification

    return back()->with('success', 'Results sent to Headteacher successfully.');
  }

  public function sendToClassTeacher(Request $request)
  {
    // TODO: Implement logic to send generated results to class teacher and students

    return back()->with('success', 'Results sent to Class Teacher and Students successfully.');
  }




  public function viewGeneratedResults(Request $request, $classId, $examId)
  {
    $this->switchTenantDB();

    $result = DB::connection('tenant')->table('results')
      ->where('class_id', $classId)
      ->where('exam_id', $examId)
      ->orderBy('generated_at', 'desc')
      ->first();

    if (!$result) {
      return redirect()->back()->with('error', 'No generated results found.');
    }

    $resultsData = json_decode($result->data, true);

    return view('content.dashboard.academic.view_results', compact('result', 'resultsData'));
  }


  protected function configureTenantConnection(string $databaseName)
  {
    $school = \App\Models\School::where('database_url', $databaseName)->first();
    if ($school) {
      $school->useAsTenant();
    }
  }
}
