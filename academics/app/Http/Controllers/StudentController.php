<?php

namespace App\Http\Controllers;

use App\Helpers\RegistrationHelper;
use App\Imports\StudentsImport;
use App\Models\Assignment;
use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\DailyAttendanceEntry;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Grade;
use App\Models\Notes;
use App\Models\School;
use App\Models\Student;
use App\Models\Stream;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;

class StudentController extends Controller
{


  public function updateComment(Request $request, $studentId)
  {
    $request->validate([
      'comment' => 'nullable|string|max:1000',
    ]);

    Student::where('id', $studentId)->update([
      'comments' => $request->comment
    ]);

    // Respond with JSON for AJAX
    return response()->json([
      'success' => true,
      'student_id' => $studentId,
      'comment' => $request->comment
    ]);
  }



  public function studentHome(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    // Get tenant DB connection from session or however you do it
    $schoolDb = session('school_db');
    $userId = session('user_id');  // Assuming user_id is stored in session

    if (!$schoolDb) {
      return redirect('/login')->withErrors('Invalid school database');
    }

    $this->configureTenantConnection($schoolDb); // Your method to set tenant DB connection

    // Get the student record linked to this user_id
    $student = DB::connection('tenant')->table('students')->where('user_id', $userId)->first();

    if (!$student) {
      return redirect('/login')->withErrors('Student record not found');
    }

    // Ensure student object has all required properties
    $student->username = $student->username ?? $student->name ?? 'Student';
    $student->comments = $student->comments ?? null;
    $student->updated_at = $student->updated_at ?? null;

    // Get student's class info
    $class = DB::connection('tenant')->table('classes')->where('id', $student->class_id)->first();

    // Ensure class has name property
    if ($class && !isset($class->name)) {
      $class->name = 'N/A';
    }

    // Optionally, get subjects assigned to student's class
    $subjects = DB::connection('tenant')
      ->table('subjects')
      ->join('classroom_subject', 'subjects.id', '=', 'classroom_subject.subject_id')
      ->where('classroom_subject.classroom_id', $student->class_id)
      ->select('subjects.*')
      ->get();

    // Optionally, announcements (for all or filtered)
    $announcements = DB::connection('tenant')->table('announcements')->orderBy('created_at', 'desc')->limit(5)->get();

    // Prepare dashboard data
    $dashboardData = [
      'student' => $student,
      'class' => $class ?? (object)['name' => 'N/A'],
      'subjects' => $subjects,
      'announcements' => $announcements,
    ];

    return view('content.dashboard.student.home', compact('dashboardData'));
  }


  public function downloadStudentTemplate(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    // Path to the template file in your public folder
    $templatePath = 'templates/student_import_template.xlsx';

    // Check if file exists in public folder
    if (!file_exists(public_path($templatePath))) {
      abort(404, 'Template file not found.');
    }

    // Set appropriate headers for download
    $headers = [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    // Download the file with a friendly name
    return response()->download(public_path($templatePath), 'student_import_template.xlsx', $headers);
  }

  public function manageStudents(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $userId = session('user_id');
    $role = session('role');
    $isAdmin = true;
    $isClassTeacher = false; // default
    $isCommentEditable = false;
    $selectedClassId = null;

    // Get school package from session (or fetch it)
    $package = session('package') ?? 'premium'; // Default to 'premium' if not found

    // Restrict class_teacher/teacher to only their assigned class
    if (in_array($role, ['ClassTeacher', 'Teacher'])) {
      $isAdmin = false;

      if ($role === 'ClassTeacher') {
        $isClassTeacher = true; // set flag
        $isCommentEditable = true;
      }


      $classroom = Classroom::on('tenant')
        ->where('class_teacher_id', $userId)
        ->first();

      if ($classroom) {
        $selectedClassId = $classroom->id;

        $query = Student::on('tenant')
          ->where('class_id', $selectedClassId)
          ->with(['stream.classroom']);
      } else {
        $query = Student::on('tenant')->whereRaw('0 = 1');
      }
    } else {
      $query = Student::on('tenant')->with(['stream.classroom']);
    }

    // Apply class filter for admins
    if ($request->filled('class_id')) {
      $query->where('class_id', $request->class_id);
    }

    // Apply gender filter
    if ($request->filled('gender')) {
      $query->where('gender', $request->gender);
    }

    if ($request->filled('search')) {
      $search = $request->search;
      $query->where(function ($q) use ($search) {
        $q->orWhereRaw("CONCAT_WS(' ', students.first_name, students.middle_name, students.last_name) LIKE ?", ["%$search%"])
          ->orWhere('students.registration_no', 'like', "%$search%")
          ->orWhere('students.email', 'like', "%$search%")
          ->orWhere('students.phone_number', 'like', "%$search%");
      });
    }

    $students = $query->orderBy('id', 'desc')->paginate(5)->appends($request->query());

    $streams = Stream::on('tenant')->with('classroom')->get();
    $classes = Classroom::on('tenant')->with('streams')->get();


    if ($role === 'Headmaster' || $role === 'Academic') {
      $isCommentEditable = false; // allow comments to be editable
      $isClassTeacher = true;
    }


    return view('content.dashboard.owner.manage_students', compact(
      'students',
      'selectedClassId',
      'streams',
      'classes',
      'isAdmin',
      'isClassTeacher', // pass to Blade
      'package',
      'isCommentEditable'
    ));
  }



  public function addStudent(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $request->validate([
      'first_name' => 'required|string',
      'middle_name' => 'nullable|string',
      'last_name' => 'required|string',
      'gender' => 'required|string|in:Male,Female',
      'date_of_birth' => 'required|date',
      'phone_number' => ['required', 'string', 'regex:/^0\d{9}$/'],
      'email' => 'nullable|email|unique:schoolUsers,email',
      'class_id' => 'required|exists:classes,id',
      'stream_id' => 'nullable|exists:streams,id',
      'address' => 'nullable|string',
      'password' => 'required|string|min:6|confirmed',
    ], [
      'phone_number.regex' => 'Phone number must be exactly 10 digits and start with 0.',
    ]);


    $roleId = 7;
    $schoolCode = session('school_code') ?? '000';
    $registrationNo = RegistrationHelper::generateRegistrationNo($roleId, $schoolCode);

    $userId = DB::connection('tenant')->table('schoolUsers')->insertGetId([
      'registration_no' => $registrationNo,
      'username' => trim($request->first_name . ' ' . ($request->middle_name ?? '') . ' ' . $request->last_name),
      'email' => $request->email,
      'phone_number' => $request->phone_number,
      'role_id' => $roleId,
      'password' => Hash::make($request->password),
      'created_at' => now(),
      'updated_at' => now(),
      'gender' => $request->gender,
    ]);

    Student::create([
      'user_id' => $userId,
      'registration_no' => $registrationNo,
      'first_name' => $request->first_name,
      'middle_name' => $request->middle_name,
      'last_name' => $request->last_name,
      'gender' => $request->gender,
      'date_of_birth' => $request->date_of_birth,
      'phone_number' => $request->phone_number,
      'email' => $request->email,
      'class_id' => $request->class_id,
      'stream_id' => $request->stream_id ?? null, // explicitly null if not provided
      'address' => $request->address,
    ]);

    return back()->with('success', 'Student added successfully.')->with('form', 'addStudent');
  }

public function importBulk(Request $request)
{
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $request->validate([
        'excel_file' => 'required|file|mimes:xlsx,xls',
    ]);

    $schoolId = session('school_code');
    $tenantDb = session('school_db');

    if (!$schoolId || !$tenantDb) {
        return back()->with('error', 'Session expired. Please login again.');
    }

    $import = new StudentsImport($schoolId, $tenantDb);
    Excel::import($import, $request->file('excel_file'));

    $message = '';
    if ($import->successCount > 0) {
        $message .= "{$import->successCount} student(s) imported successfully. ";
    }
    if ($import->failureCount > 0) {
        $message .= "{$import->failureCount} student(s) failed to import.";
    }

    if (!empty($import->errors)) {
        return back()
            ->with('import_status', $message)
            ->with('import_errors', $import->errors);
    }

    if ($import->successCount > 0) {
        return back()->with('success', $message);
    }

    return back()->with('error', 'No students were imported.');
}







  public function updateStudent(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $student = Student::findOrFail($id);

    $request->validate([
      'first_name'    => 'required|string',
      'middle_name'   => 'nullable|string',
      'last_name'     => 'required|string',
      'gender'        => 'required|string|in:Male,Female',
      'date_of_birth' => 'required|date',
      'phone_number'  => ['required', 'string', 'regex:/^0\d{9}$/'],
      'email'         => 'nullable|email',
      'address'       => 'nullable|string',
    ], [
      'phone_number.regex' => 'Phone number must be exactly 10 digits and start with 0.',
    ]);


    $student->update($request->all());

    // Update the user record
    $user = DB::connection('tenant')->table('schoolUsers')->where('id', $student->user_id)->first();
    if ($user) {
      DB::connection('tenant')->table('schoolUsers')->where('id', $student->user_id)->update([
        'username' => trim($request->first_name . ' ' . ($request->middle_name ?? '') . ' ' . $request->last_name),
        'email' => $request->email,
        'phone_number' => $request->phone_number,
        'gender' => $request->gender,


      ]);
    }

    return back()->with('success', 'Student updated successfully.');
  }

  public function deleteStudent(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    Student::findOrFail($id)->delete();

    return back()->with('success', 'Student deleted.');
  }

  private function switchTenantDB()
  {
    $tenantDb = session('school_db');
    config(['database.default' => 'tenant']);
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');
  }

  public function editStudent(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $student = Student::on('tenant')->with('stream.classroom')->findOrFail($id);
    $streams = Stream::on('tenant')->with('classroom')->get();
    $classes = Classroom::on('tenant')->with('streams')->get();

    return view('content.dashboard.owner.edit_student', compact('student', 'streams', 'classes'));
  }



  public function studentResults(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $toJson = $request->input('toJson', false);
    $userId = $request->input('user_id');
    $schoolCode = $request->input('school_code');

    // 1️⃣ Configure tenant DB connection
    if ($toJson && $userId && $schoolCode) {
      $school = School::where('school_code', $schoolCode)->first();
      if (!$school) {
        return $toJson
          ? response()->json(['status' => 'error', 'message' => 'Invalid school code'], 400)
          : back()->withErrors('Invalid school code.');
      }
      $this->configureTenantConnection($school->database_url);
    } else {
      if (!session()->has('user_id') || !session()->has('school_db')) {
        return redirect()->route('login')->withErrors('Session expired. Please login again.');
      }
      $userId = session('user_id');
      $this->configureTenantConnection(session('school_db'));
    }

    // 2️⃣ Fetch student
    $student = Student::on('tenant')
      ->with(['class:id,name', 'stream:id,name'])
      ->where('user_id', $userId)
      ->first();

    if (!$student) {
      return $toJson
        ? response()->json(['status' => 'error', 'message' => 'Student not found'], 404)
        : back()->withErrors('Student not found.');
    }

    // 3️⃣ Get available exams
    $examIds = DB::connection('tenant')->table('class_exam')
      ->where('class_id', $student->class_id)
      ->pluck('exam_id');

    $exams = Exam::on('tenant')
      ->whereIn('id', $examIds)
      ->orderBy('created_at', 'desc')
      ->get();

    $selectedExamId = $request->input('exam_id');
    $gradeType = $request->input('grading_type');

    // Defaults for Blade
    $resultsForView = [];
    $subjectResults = [];
    $totalMarks = 0;
    $overallGrade = null;

    // 4️⃣ If no exam selected → return early
    if (!$selectedExamId) {
      if ($toJson) {
        return response()->json([
          'status' => 'success',
          'student' => [
            'name' => $student->first_name,
            'class' => $student->class?->name,
            'stream' => $student->stream?->name,
          ],
          'exams' => $exams,
        ]);
      }
      return view('content.dashboard.student.results', [
        'student' => $student,
        'exams' => $exams,
        'selectedExamId' => null,
        'gradeType' => null,
        'results' => [],
        'totalMarks' => 0,
        'overallGrade' => null
      ]);
    }

    // 5️⃣ Fetch marks for selected exam
    $results = ExamMark::on('tenant')
      ->where('exam_id', $selectedExamId)
      ->where('student_id', $student->id)
      ->with('subject:id,name,code')
      ->get();

    foreach ($results as $result) {
      $totalMarks += $result->marks;

      $grade = Grade::on('tenant')
        ->where('type', 'individual')
        ->where('min_mark', '<=', $result->marks)
        ->where('max_mark', '>=', $result->marks)
        ->first();

      $teacherName = "Mr./Ms. " . substr($result->subject->name ?? '', 0, 3);
      $comment = "Well done in " . ($result->subject->name ?? 'this subject') . ".";

      $subjectResults[] = [
        'name' => $result->subject->name ?? 'N/A',
        'grade' => $grade?->name ?? 'N/A',
        'score' => (int) $result->marks,
        'teacher' => $teacherName,
        'comments' => $comment,
      ];

      // For Blade table
      $resultsForView[] = (object) [
        'subject' => (object) ['name' => $result->subject->name ?? 'N/A'],
        'marks' => $result->marks,
        'grade' => $grade?->name ?? 'N/A'
      ];
    }

    // 6️⃣ Calculate overall grade
    if ($results->count()) {
      $average = round($totalMarks / $results->count(), 1);

      if (!$gradeType) {
        $hasStandard = Grade::on('tenant')->where('type', 'standard')->exists();
        $hasDivision = Grade::on('tenant')->where('type', 'division')->exists();
        $gradeType = $hasStandard ? 'standard' : ($hasDivision ? 'division' : null);
      }

      if ($gradeType === 'standard') {
        $grade = Grade::on('tenant')
          ->where('type', 'standard')
          ->where('min_mark', '<=', $average)
          ->where('max_mark', '>=', $average)
          ->first();
        $overallGrade = $grade?->name ?? 'N/A';
      } elseif ($gradeType === 'division') {
        $bestSeven = $results->sortByDesc('marks')->take(7);
        $totalDivisionPoints = 0;

        foreach ($bestSeven as $subjectResult) {
          $divisionGrade = Grade::on('tenant')
            ->where('type', 'division')
            ->where('min_mark', '<=', $subjectResult->marks)
            ->where('max_mark', '>=', $subjectResult->marks)
            ->first();

          $points = match ($divisionGrade?->name) {
            'A' => 1,
            'B' => 2,
            'C' => 3,
            'D' => 4,
            'F' => 5,
            default => 5,
          };
          $totalDivisionPoints += $points;
        }

        if ($totalDivisionPoints <= 17) {
          $overallGrade = "Division 1.$totalDivisionPoints";
        } elseif ($totalDivisionPoints <= 21) {
          $overallGrade = "Division 2.$totalDivisionPoints";
        } elseif ($totalDivisionPoints <= 25) {
          $overallGrade = "Division 3.$totalDivisionPoints";
        } elseif ($totalDivisionPoints <= 33) {
          $overallGrade = "Division 4.$totalDivisionPoints";
        } else {
          $overallGrade = "Fail";
        }
      }
    }

    // 7️⃣ Return final response
    if ($toJson) {
      return response()->json([
        'status' => 'success',
        'student' => [
          'name' => $student->first_name,
          'class' => $student->class?->name,
          'stream' => $student->stream?->name,
        ],
        'overallGrade' => $overallGrade,
        'averageScore' => $results->count() ? round($totalMarks / $results->count(), 1) : 0,
        'subjects' => $subjectResults,
      ]);
    }

    return view('content.dashboard.student.results', [
      'student' => $student,
      'exams' => $exams,
      'selectedExamId' => $selectedExamId,
      'gradeType' => $gradeType,
      'results' => $resultsForView,
      'totalMarks' => $totalMarks,
      'overallGrade' => $overallGrade
    ]);
  }










  public function downloadResults(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $studentId = session('user_id');
    $student = Student::on('tenant')->where('user_id', $studentId)->first();

    if (!$student) {
      return redirect()->back()->with('error', 'Student not found.');
    }

    $examId = $request->input('exam_id');
    $gradingType = $request->input('grading_type');

    if (!$examId) {
      return redirect()->back()->with('error', 'Please select an exam.');
    }

    $results = ExamMark::on('tenant')
      ->where('exam_id', $examId)
      ->where('student_id', $student->id)
      ->with('subject')
      ->get();

    if ($results->isEmpty()) {
      return redirect()->back()->with('error', 'No results found for the selected exam.');
    }

    // Calculate total marks and assign grades like in studentResults()
    $totalMarks = 0;
    foreach ($results as $result) {
      $totalMarks += $result->marks;

      $grade = Grade::on('tenant')
        ->where('type', 'individual')
        ->where('min_mark', '<=', $result->marks)
        ->where('max_mark', '>=', $result->marks)
        ->first();

      $result->grade = $grade ? $grade->name : 'N/A';
    }

    $average = round($totalMarks / $results->count());

    if (!$gradingType) {
      $hasStandard = Grade::on('tenant')->where('type', 'standard')->exists();
      $hasDivision = Grade::on('tenant')->where('type', 'division')->exists();

      if ($hasStandard) {
        $gradingType = 'standard';
      } elseif ($hasDivision) {
        $gradingType = 'division';
      }
    }

    $overallGrade = null;
    if ($gradingType) {
      $grade = Grade::on('tenant')
        ->where('type', $gradingType)
        ->where('min_mark', '<=', $average)
        ->where('max_mark', '>=', $average)
        ->first();

      $overallGrade = $grade ? $grade->name : 'N/A';
    }

    $exam = Exam::on('tenant')->find($examId);

    $School = School::where('school_code', session('school_code'))->first();
    // Generate PDF using a blade view (create 'pdf.student_results' blade)
    $pdf = Pdf::loadView('pdf.student_results', compact('results', 'School', 'exam', 'totalMarks', 'overallGrade', 'gradingType'));

    $filename = 'ExamResults_' . $exam->title . '_' . now()->format('Ymd_His') . '.pdf';

    return $pdf->download($filename);
  }

  public function getClassAttendances($classId)
  {
    $attendances = Attendance::where('class_id', $classId)
      ->get();

    return response()->json($attendances);
  }


  public function attendanceHistory(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $toJson = $request->input('toJson', false);
    $userId = $request->input('user_id');
    $schoolCode = $request->input('school_code');

    if ($toJson && $userId && $schoolCode) {
      // Mobile/API request
      $school = School::where('school_code', $schoolCode)->first();

      if (!$school) {
        return response()->json(['status' => 'error', 'message' => 'Invalid school code'], 400);
      }

      $this->configureTenantConnection($school->database_url);
    } else {
      // Web/session request
      if (!session()->has('user_id') || !session()->has('school_db')) {
        return redirect()->route('login')->withErrors('Session expired. Please login again.');
      }

      $userId = session('user_id');
      $schoolDb = session('school_db');
      $this->configureTenantConnection($schoolDb);
    }

    $student = Student::on('tenant')->where('user_id', $userId)->first();

    if (!$student) {
      if ($toJson) {
        return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
      }

      return redirect()->back()->with('error', 'Student not found.');
    }

    $perPage = $request->input('per_page', 15); // default to 15 entries per page
    $attendanceEntries = DailyAttendanceEntry::on('tenant')
      ->where('student_id', $student->id)
      ->with(['attendance.class:id,name', 'attendance.stream:id,name'])
      ->orderBy('date', 'desc')
      ->paginate($perPage);

    // Compute stats
    $presentCount = $attendanceEntries->where('status', 'present')->count();
    $absentCount = $attendanceEntries->where('status', 'absent')->count();
    $total = $attendanceEntries->count();
    $presentPercentage = $total > 0 ? round(($presentCount / $total) * 100, 2) : 0;

    if ($toJson) {
      $attendanceRecords = $attendanceEntries->getCollection()->map(function ($entry) {
        return [
          'date' => \Carbon\Carbon::parse($entry->date)->toDateString(),
          'status' => strtolower($entry->status),
        ];
      });

      $lateCount = $attendanceEntries->where('status', 'late')->count();

      return response()->json([
        'status' => 'success',
        'summary' => [
          'presentCount' => $presentCount,
          'lateCount' => $lateCount,
          'absentCount' => $absentCount,
          'attendancePercentage' => $total > 0 ? round((($presentCount + $lateCount) / $total) * 100, 2) : 0,
        ],
        'attendance' => $attendanceRecords,
        'pagination' => [
          'current_page' => $attendanceEntries->currentPage(),
          'per_page' => $attendanceEntries->perPage(),
          'total' => $attendanceEntries->total(),
          'last_page' => $attendanceEntries->lastPage(),
        ]
      ]);
    }


    // Web response
    return view('content.dashboard.student.attendance', [
      'attendanceEntries' => $attendanceEntries,
      'presentCount' => $presentCount,
      'absentCount' => $absentCount,
      'presentPercentage' => $presentPercentage,
    ]);
  }


  public function classAttendanceHistory(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $toJson = $request->input('toJson', false);
    $classId = $request->input('class_id');
    $schoolCode = $request->input('school_code');

    // Handle connection setup
    if ($toJson && $schoolCode) {
      $school = School::where('school_code', $schoolCode)->first();
      if (!$school) {
        return response()->json(['status' => 'error', 'message' => 'Invalid school code'], 400);
      }
      $this->configureTenantConnection($school->database_url);
    } else {
      if (!session()->has('school_db')) {
        return redirect()->route('login')->withErrors('Session expired. Please login again.');
      }
      $this->configureTenantConnection(session('school_db'));
    }

    // Get all classes for dropdown
    $classes = Classroom::on('tenant')->select('id', 'name')->get();

    // If no class selected, just show form with no attendance data
    if (!$classId) {
      return view('content.dashboard.student.class_attendance', [
        'classes' => $classes,
        'attendanceData' => []
      ]);
    }

    // Get all students in this class
    $students = Student::on('tenant')->where('class_id', $classId)->get();

    if ($students->isEmpty()) {
      if ($toJson) {
        return response()->json(['status' => 'error', 'message' => 'No students found in this class'], 404);
      }
      return view('content.dashboard.student.class_attendance', [
        'classes' => $classes,
        'attendanceData' => []
      ])->with('error', 'No students found in this class.');
    }

    $attendanceData = [];

    foreach ($students as $student) {
      // Fetch all attendance entries for this student, ordered descending by date
      $attendanceEntries = DailyAttendanceEntry::on('tenant')
        ->where('student_id', $student->id)
        ->orderBy('date', 'desc')
        ->get();

      $presentCount = $attendanceEntries->where('status', 'present')->count();
      $lateCount = $attendanceEntries->where('status', 'late')->count();
      $absentCount = $attendanceEntries->where('status', 'absent')->count();
      $total = $attendanceEntries->count();
      $attendancePercentage = $total > 0 ? round((($presentCount + $lateCount) / $total) * 100, 2) : 0;

      $attendanceData[] = [
        'student_id' => $student->id,
        'student_name' => trim($student->first_name . ' ' . ($student->middle_name ? $student->middle_name . ' ' : '') . $student->last_name),
        'presentCount' => $presentCount,
        'lateCount' => $lateCount,
        'absentCount' => $absentCount,
        'total' => $total,
        'attendancePercentage' => $attendancePercentage,
        'attendanceRecords' => $attendanceEntries->map(function ($entry) {
          return [
            'date' => \Carbon\Carbon::parse($entry->date)->toDateString(),
            'status' => strtolower($entry->status),
          ];
        }),
      ];
    }

    if ($toJson) {
      return response()->json([
        'status' => 'success',
        'classes' => $classes,
        'attendanceData' => $attendanceData,
      ]);
    }

    return view('content.dashboard.student.class_attendance', [
      'classes' => $classes,
      'attendanceData' => $attendanceData,
    ]);
  }





  public function viewAssignments(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $toJson = $request->input('toJson', false);
    $userId = $request->input('user_id');
    $schoolCode = $request->input('school_code');

    if ($toJson && $userId && $schoolCode) {
      // Mobile/API request
      $school = School::where('school_code', $schoolCode)->first();

      if (!$school) {
        return response()->json(['status' => 'error', 'message' => 'Invalid school code'], 400);
      }

      $this->configureTenantConnection($school->database_url);
    } else {
      // Web/session request
      if (!session()->has('user_id') || !session()->has('school_db')) {
        return redirect()->route('login')->withErrors('Session expired. Please login again.');
      }

      $userId = session('user_id');
      $schoolDb = session('school_db');
      $this->configureTenantConnection($schoolDb);
    }

    // Find student
    $student = Student::on('tenant')->where('user_id', $userId)->first();

    if (!$student) {
      if ($toJson) {
        return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
      }

      return redirect()->back()->with('error', 'Student not found.');
    }

    // Fetch assignments
    $assignments = Assignment::on('tenant')
      ->with('subject') // eager load subject data
      ->where('class_id', $student->class_id)
      ->orderBy('created_at', 'desc')
      ->get();


    if ($toJson) {
      $grouped = $assignments->groupBy(function ($assignment) {
        return $assignment->subject->name ?? 'Unknown Subject';
      });

      return response()->json([
        'status' => 'success',
        'assignments' => $grouped->map(function ($subjectAssignments, $subjectName) {
          return [
            'subject' => $subjectName,
            'assignments' => $subjectAssignments->map(function ($assignment) {
              return [
                'id' => $assignment->id,
                'class_id' => $assignment->class_id,
                'title' => $assignment->title,
                'description' => $assignment->description,
                'file_path' => $assignment->file_path,
                'uploaded_at' => $assignment->uploaded_at,
                'created_at' => $assignment->created_at,
                'updated_at' => $assignment->updated_at,
              ];
            })->values(),
          ];
        })->values(),
      ]);
    }


    // Web response
    return view('content.dashboard.student.assignments', compact('assignments'));
  }


  public function viewNotes(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $toJson = $request->input('toJson', false);
    $userId = $request->input('user_id');
    $schoolCode = $request->input('school_code');

    if ($toJson && $userId && $schoolCode) {
      // Mobile/API request
      $school = School::where('school_code', $schoolCode)->first();

      if (!$school) {
        return response()->json(['status' => 'error', 'message' => 'Invalid school code'], 400);
      }

      $this->configureTenantConnection($school->database_url);
    } else {
      // Web/session request
      if (!session()->has('user_id') || !session()->has('school_db')) {
        return redirect()->route('login')->withErrors('Session expired. Please login again.');
      }

      $userId = session('user_id');
      $schoolDb = session('school_db');
      $this->configureTenantConnection($schoolDb);
    }

    // Find student
    $student = Student::on('tenant')->where('user_id', $userId)->first();

    if (!$student) {
      if ($toJson) {
        return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
      }

      return redirect()->back()->with('error', 'Student not found.');
    }

    // Pagination size (can be parameterized)
    $perPage = 10;

    // Fetch paginated notes
    $notes = Notes::on('tenant')
      ->with('subject')
      ->where('class_id', $student->class_id)
      ->orderBy('created_at', 'desc')
      ->paginate($perPage);

    // Fetch paginated assignments
    $assignments = Assignment::on('tenant')
      ->with('subject')
      ->where('class_id', $student->class_id)
      ->paginate($perPage);

    if ($toJson) {
      // Format notes with pagination meta
      $formattedNotes = $notes->getCollection()->map(function ($note) {
        $fileSize = 0;
        if ($note->file_path) {
          $fullPath = public_path($note->file_path);
          if (file_exists($fullPath)) {
            $fileSize = round(filesize($fullPath) / (1024 * 1024), 1);
          }
        }

        return [
          'id' => $note->id,
          'title' => $note->title,
          'subject' => $note->subject->name ?? 'Unknown Subject',
          'uploadDate' => $note->created_at->toDateTimeString(),
          'fileType' => strtoupper(pathinfo($note->file_path, PATHINFO_EXTENSION)) ?? 'PDF',
          'size' => $fileSize . ' MB',
          'file_path' => $note->file_path ? url($note->file_path) : null,
        ];
      })->values()->all();

      // Format assignments with pagination meta
      $formattedAssignments = $assignments->getCollection()->map(function ($assignment) {
        $now = now();
        $dueDate = \Carbon\Carbon::parse($assignment->due_date);
        $status = $assignment->submitted ? 'submitted' : ($dueDate->lt($now) ? 'overdue' : 'pending');

        return [
          'id' => $assignment->id,
          'title' => $assignment->title,
          'subject' => $assignment->subject->name ?? 'Unknown Subject',
          'dueDate' => $assignment->due_date,
          'status' => $status,
          'description' => $assignment->description,
          'attachments' => $assignment->attachments ? json_decode($assignment->attachments) : [],
          'submitted' => (bool) $assignment->submitted,
          'submittedDate' => $assignment->submitted_date,
          'grade' => $assignment->grade,
        ];
      })->values()->all();

      // Return paginated results including meta data for frontend paging
      return response()->json([
        'status' => 'success',
        'notes' => [
          'data' => $formattedNotes,
          'current_page' => $notes->currentPage(),
          'last_page' => $notes->lastPage(),
          'per_page' => $notes->perPage(),
          'total' => $notes->total(),
        ],
        'assignments' => [
          'data' => $formattedAssignments,
          'current_page' => $assignments->currentPage(),
          'last_page' => $assignments->lastPage(),
          'per_page' => $assignments->perPage(),
          'total' => $assignments->total(),
        ],
      ]);
    }

    // Web response (notes only, unpaginated for now)
    return view('content.dashboard.student.notes', compact('notes'));
  }



  protected function configureTenantConnection($databaseName)
  {
    $school = \App\Models\School::where('database_url', $databaseName)->first();
    if ($school) {
      $school->useAsTenant();
    }
  }
}
