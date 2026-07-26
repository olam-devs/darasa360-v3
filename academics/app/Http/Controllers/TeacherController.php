<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Grade;
use App\Models\School;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\TeacherNote;
use App\Models\TimetableDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class TeacherController extends Controller
{
  private function switchTenantDB()
  {
    $tenantDb = session('school_db');
    if (!$tenantDb) {
      abort(403, 'Tenant database not found in session.');
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');
  }

  // public function viewTeachers(Request $request)
  // {
  //   if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
  //   $this->switchTenantDB();

  //   // Use Eloquent on tenant connection with eager loading
  //   $teachers = Teacher::on('tenant')
  //     ->with(['classes', 'subjects'])
  //     ->paginate(5);

  //   return view('content.dashboard.teacher.home', compact('teachers'));
  // }


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

    $userRole = $toJson ? $request->input('role') : session('role');

    // Base query: all notes uploaded by this user
    $query = TeacherNote::on('tenant')->with(['class', 'subject'])
      ->where('uploaded_by', $userId);

    $classes = collect();
    $subjects = collect();
    $isAdmin = $userRole !== 'Student';

    if ($userRole === 'Student') {
      $student = Student::on('tenant')->where('user_id', $userId)->first();
      if ($student) {
        $query->where('class_id', $student->class_id);
        $classes = Classroom::on('tenant')->where('id', $student->class_id)->get();
      }
    } else {
      // Teacher or ClassTeacher: no filtering by class/subject
      $classes = Classroom::on('tenant')->get();
      $subjects = Subject::on('tenant')->get();
    }

    // Apply optional filters from request (class/subject/type)
    if ($request->filled('class_id')) {
      $query->where('class_id', $request->class_id);
    }
    if ($request->filled('subject_id')) {
      $query->where('subject_id', $request->subject_id);
    }
    if ($request->filled('type')) {
      $query->where('type', $request->type);
    }

    if ($toJson) {
      $documents = $query->orderByDesc('created_at')->get();

      // Group by class name (may be null)
      $grouped = $documents->groupBy(function ($doc) {
        return $doc->class->name ?? 'No Class';
      });

      $responseData = $grouped->map(function ($classDocs, $className) {
        return [
          'class' => $className,
          'documents' => $classDocs->map(function ($doc) {
            $size = 'Unknown';
            if (isset($doc->file_size_bytes)) {
              $size = $doc->file_size_bytes > 1e6
                ? number_format($doc->file_size_bytes / 1e6, 1) . ' MB'
                : number_format($doc->file_size_bytes / 1e3) . ' KB';
            }

            return [
              'id' => $doc->id,
              'title' => $doc->original_name ?? 'Untitled',
              'description' => $doc->description ?? '',
              'subject' => $doc->subject->name ?? '',
              'period' => $doc->period ?? '',
              'type' => $doc->type,
              'size' => $size,
              'lastUpdated' => $doc->updated_at?->format('Y-m-d'),
              'priority' => $doc->priority ?? 'medium',
              'file_path' => $doc->document_path,
              'uploaded_at' => $doc->uploaded_at ?? null,
              'created_at' => $doc->created_at ?? null,
              'updated_at' => $doc->updated_at ?? null,
            ];
          })->values(),
        ];
      })->values();

      return response()->json([
        'status' => 'success',
        'documents' => $responseData,
        'subjects' => $subjects,
      ]);
    }

    // Web view
    $documents = $query->paginate(10);

    // Determine view based on user role
    $viewPath = match($userRole) {
      'Teacher' => 'content.dashboard.teacher.notes',
      'ClassTeacher' => 'content.dashboard.classTeacher.notes',
      'Headteacher', 'HeadTeacher' => 'content.dashboard.headmaster.notes',
      'Academic' => 'content.dashboard.academic.notes',
      default => 'content.dashboard.classTeacher.notes'
    };

    return view($viewPath, compact('documents', 'classes', 'subjects', 'isAdmin'));
  }




  public function addNotes(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $request->validate([
      'file' => 'required|mimes:pdf,doc,docx,ppt,pptx|max:2048',
      'description' => 'nullable|string|max:255',
    ]);


    $uploadedFile = $request->file('file');
    $path = $uploadedFile->store('timetables', 'public');

    TeacherNote::create([
      'document_path'  => $path,
      'original_name'  => $uploadedFile->getClientOriginalName(),
      'description'    => $request->description,
      'uploaded_by'    => session('user_id'),
    ]);

    return redirect()->back()->with('success', 'Document uploaded successfully.');
  }


  public function deleteNotes($id, Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $document = TeacherNote::findOrFail($id);
    Storage::disk('public')->delete($document->document_path);
    $document->delete();

    return redirect()->back()->with('success', 'Document deleted successfully.');
  }


  public function downloadNotes($id, Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $this->switchTenantDB();

    $document = TeacherNote::findOrFail($id);

    return Storage::disk('public')->download(
      $document->document_path,
      $document->original_name ?? 'Notes.pdf'
    );
  }


  public function viewTeachers(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $schoolDb = session('school_db');
    $userId = session('user_id');
    $userRole = session('role');

    if (!$schoolDb) return response()->json(['error' => 'Invalid school database'], 400);

    $this->configureTenantConnection($schoolDb);

    $user = DB::connection('tenant')->table('schoolUsers')->where('id', $userId)->first();
    $username = $user ? $user->username : 'User';

    // Initialize variables
    $teachers = collect();
    $classStats = collect();
    $assignments = collect();

    if ($userRole === 'ClassTeacher') {
      // Get the class where this user is the class teacher
      $classTeacherClass = DB::connection('tenant')->table('classes')
        ->where('class_teacher_id', $userId)
        ->first();

      if ($classTeacherClass) {
        // Get teachers who teach in this specific class
        $teacherIds = DB::connection('tenant')->table('subject_teacher')
          ->where('class_id', $classTeacherClass->id)
          ->pluck('teacher_id')
          ->unique()
          ->toArray();

        if (!empty($teacherIds)) {
          $teachers = Teacher::on('tenant')
            ->whereIn('id', $teacherIds)
            ->with(['classes', 'subjects'])
            ->paginate(5);
        }

        // Load stats for this class
        $classStats = Classroom::withCount([
          'students',
          'subjects',
          'assignments',
          'teachers',
        ])
          ->where('id', $classTeacherClass->id)
          ->get()
          ->map(function ($classroom) {
            return [
              'class_id' => $classroom->id,
              'class_name' => $classroom->name,
              'studentsCount' => $classroom->students_count,
              'subjectsCount' => $classroom->subjects_count,
              'teachersCount' => $classroom->teachers_count,
              'assignmentsCount' => $classroom->assignments_count,
            ];
          });

        // Group assignments by class
        $assignments = Assignment::on('tenant')
          ->where('class_id', $classTeacherClass->id)
          // ->with(['teacher', 'subject'])
          ->get()
          ->groupBy('class_id')
          ->map(function ($items, $classId) {
            $class = Classroom::on('tenant')->find($classId);
            return [
              'class_id' => $classId,
              'class_name' => $class ? $class->name : 'Unknown Class',
              'assignments' => $items,
            ];
          });
      }
    } elseif ($userRole === 'Teacher') {
      // Get classes and subjects this teacher teaches
      $classIds = DB::connection('tenant')->table('subject_teacher')
        ->where('teacher_id', function ($query) use ($userId) {
          $query->select('id')
            ->from('teachers')
            ->where('user_id', $userId)
            ->limit(1);
        })
        ->pluck('class_id')
        ->unique()
        ->toArray();

      $subjectIds = DB::connection('tenant')->table('subject_teacher')
        ->where('teacher_id', function ($query) use ($userId) {
          $query->select('id')
            ->from('teachers')
            ->where('user_id', $userId)
            ->limit(1);
        })
        ->pluck('subject_id')
        ->unique()
        ->toArray();

      // Get all teachers (for the view)
      $teachers = Teacher::on('tenant')
        ->with(['classes', 'subjects'])
        ->paginate(5);

      // Load stats for classes this teacher teaches
      if (!empty($classIds)) {
        $classStats = Classroom::withCount([
          'students',
          'subjects',
          'assignments',
          'teachers',
        ])
          ->whereIn('id', $classIds)
          ->get()
          ->map(function ($classroom) {
            return [
              'class_id' => $classroom->id,
              'class_name' => $classroom->name,
              'studentsCount' => $classroom->students_count,
              'subjectsCount' => $classroom->subjects_count,
              'teachersCount' => $classroom->teachers_count,
              'assignmentsCount' => $classroom->assignments_count,
            ];
          });
      }

      // Add teacher-specific stats
      $teacherStats = [
        'classes_taught' => count($classIds),
        'subjects_taught' => count($subjectIds),
        'total_students' => DB::connection('tenant')->table('students')
          ->whereIn('class_id', $classIds)
          ->count(),
      ];

      $classStats = $classStats->merge([$teacherStats]);

      // Group this teacher's assignments by class_id (filter by user_id directly)
      $assignments = Assignment::on('tenant')
        ->where('assigned_by', $userId)
        ->with(['class', 'subject'])
        ->get()
        ->groupBy('class_id')
        ->map(function ($items, $classId) {
          $class = Classroom::on('tenant')->find($classId);
          return [
            'class_id' => $classId,
            'class_name' => $class ? $class->name : 'Unknown Class',
            'assignments' => $items,
          ];
        });
    } else {
      // For other roles (Headmaster, Academic, etc.)
      $teachers = Teacher::on('tenant')
        ->with(['classes', 'subjects'])
        ->paginate(5);

      // Load all classrooms with related stats
      $classStats = Classroom::withCount([
        'students',
        'subjects',
        'assignments',
        'teachers',
      ])->get()->map(function ($classroom) {
        return [
          'class_id' => $classroom->id,
          'class_name' => $classroom->name,
          'studentsCount' => $classroom->students_count,
          'subjectsCount' => $classroom->subjects_count,
          'teachersCount' => $classroom->teachers_count,
          'assignmentsCount' => $classroom->assignments_count,
        ];
      });

      // Group all assignments by class
      $assignments = Assignment::on('tenant')
        ->with(['teacher', 'classroom', 'subject'])
        ->get()
        ->groupBy('class_id')
        ->map(function ($items, $classId) {
          $class = Classroom::on('tenant')->find($classId);
          return [
            'class_id' => $classId,
            'class_name' => $class ? $class->name : 'Unknown Class',
            'assignments' => $items,
          ];
        });
    }

    $dashboardData = [
      'username' => $username,
      'userRole' => $userRole,
      'teachers' => $teachers,
      'classStats' => $classStats,
      'assignments' => $assignments,
    ];


    return view('content.dashboard.teacher.home', compact('dashboardData'));
  }





  public function storeTeacherAssignment(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $request->validate([
      'teacher_id' => 'required|exists:tenant.teachers,id',
      'class_ids' => 'required|array',
      'class_ids.*' => 'required|exists:tenant.classes,id',
      'subject_ids' => 'required|array',
      'subject_ids.*' => 'array',
      'subject_ids.*.*' => 'exists:tenant.subjects,id',
    ]);

    $teacherId = $request->teacher_id;

    // Get teacher's user_id
    $teacher = Teacher::on('tenant')->find($teacherId);
    if (!$teacher) {
      return redirect()->back()->withErrors('Teacher not found.');
    }

    $userId = $teacher->user_id;

    // Get role_id from schoolusers
    $roleId = DB::connection('tenant')->table('schoolUsers')
      ->where('id', $userId)
      ->value('role_id');

    // Check if ClassTeacher (role_id 5)
    if ($roleId == 5) {
      // Get the class assigned to this ClassTeacher
      $assignedClassId = DB::connection('tenant')->table('classes')
        ->where('class_teacher_id', $userId)
        ->value('id');

      if ($assignedClassId) {
        // Ensure submitted classes match the assigned class
        foreach ($request->class_ids as $classId) {
          if ($classId != $assignedClassId) {
            return redirect()->back()->withErrors(
              'ClassTeacher can only be assigned to their own class.'
            );
          }
        }
      } else {
        return redirect()->back()->withErrors(
          'This ClassTeacher does not have a class assigned yet.'
        );
      }
    }

    // Delete all previous assignments for this teacher
    DB::connection('tenant')->table('subject_teacher')->where('teacher_id', $teacherId)->delete();

    $insertData = [];

    foreach ($request->class_ids as $classId) {
      $subjectList = $request->subject_ids[$classId] ?? [];
      foreach ($subjectList as $subjectId) {
        $insertData[] = [
          'teacher_id' => $teacherId,
          'class_id' => $classId,
          'subject_id' => $subjectId,
          'created_at' => now(),
          'updated_at' => now(),
        ];
      }
    }

    if (!empty($insertData)) {
      DB::connection('tenant')->table('subject_teacher')->insert($insertData);
    }

    return redirect()->back()->with('success', 'Assignments updated successfully.');
  }


  public function getTeacherAssignments($teacherId)
  {
    $this->switchTenantDB();

    // Get teacher
    $teacher = Teacher::on('tenant')->find($teacherId);
    if (!$teacher) {
      return response()->json(['error' => 'Teacher not found'], 404);
    }

    // Fetch assignments
    $assignments = DB::connection('tenant')->table('subject_teacher')
      ->where('teacher_id', $teacherId)
      ->get(); // returns collection of rows with class_id and subject_id

    // Optionally, group by class for easier frontend handling
    $grouped = $assignments->groupBy('class_id')->map(function ($subjects) {
      return $subjects->pluck('subject_id')->toArray();
    });

    return response()->json([
      'teacher_id' => $teacherId,
      'assignments' => $grouped // { class_id: [subject_ids...] }
    ]);
  }





  public function getSubjects(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $classroom = Classroom::on('tenant')->with('subjects')->findOrFail($id);
    return response()->json($classroom->subjects);
  }


  public function getSubjectsByClass($classId)
  {
    $subjects = Subject::where('class_id', $classId)->get();
    return response()->json($subjects);
  }


  // TeacherController.php
  public function getTeacherAssignmentsJson($id)
  {
    $this->switchTenantDB();

    $teacher = Teacher::on('tenant')->with('subjects')->findOrFail($id);

    $assignments = [];

    foreach ($teacher->subjects as $subject) {
      $assignments[$subject->pivot->class_id][] = $subject->id;
    }

    return response()->json($assignments);
  }



  public function showAssignForm(Request $request, $teacherId)
  {
    if (!(new HelperController)->isLoggedIn(request())) return redirect('/login');
    $this->switchTenantDB();

    // Find teacher on tenant connection
    $teacher = Teacher::on('tenant')->findOrFail($teacherId);

    $subjects = Subject::on('tenant')->get();
    $classes = Classroom::on('tenant')->get();

    $assignedSubjectIds = $teacher->subjects()->pluck('subjects.id')->toArray();
    $assignedClassIds = $teacher->classes()->pluck('classrooms.id')->toArray();

    return view('content.dashboard.teacher.assign_classes', compact(
      'teacher',
      'subjects',
      'classes',
      'assignedSubjectIds',
      'assignedClassIds'
    ));
  }

  public function assignSubjectsAndClasses(Request $request, $teacherId)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $teacher = Teacher::on('tenant')->findOrFail($teacherId);

    $request->validate([
      'subject_ids' => 'nullable|array',
      'subject_ids.*' => 'exists:subjects,id',
      'class_ids' => 'nullable|array',
      'class_ids.*' => 'exists:classrooms,id',
    ]);

    // Sync subjects and classes on tenant connection
    $teacher->subjects()->sync($request->input('subject_ids', []));
    $teacher->classes()->sync($request->input('class_ids', []));

    return redirect()->route('teachers.view')
      ->with('success', 'Subjects and classes assigned successfully.');
  }

  public function viewTeachersPerClass(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    // Get paginated teachers with their classes and subjects for the table
    $teachers = Teacher::on('tenant')
      ->with(['subjects', 'classes'])
      ->paginate(10);

    // Get all teachers for the assign form dropdown
    $allTeachers = Teacher::on('tenant')->get();

    // Get all classes
    $classes = Classroom::on('tenant')->get();

    // Get all subjects
    $subjects = Subject::on('tenant')->get();

    // Get all grades
    $grades = Grade::on('tenant')->get();

    return view('content.dashboard.teacher.view_teachers_per_class', compact(
      'teachers',
      'allTeachers',
      'classes',
      'subjects',
      'grades'        // Pass grades to the view
    ));
  }


  public function viewResultSheet(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $classes = Classroom::on('tenant')->get();
    $exams = Exam::on('tenant')->get();
    $subjects = Subject::on('tenant')->get();
    $results = collect();

    if ($request->filled(['exam_id', 'class_id', 'subject_id'])) {
      $query = ExamMark::on('tenant')
        ->where('exam_id', $request->exam_id)
        ->where('class_id', $request->class_id)
        ->with(['student', 'subject']);

      if ($request->subject_id !== 'all') {
        $query->where('subject_id', $request->subject_id);
      }

      $results = $query->get();
    }

    return view('content.dashboard.teacher.exams.result_sheet', [
      'classes' => $classes,
      'exams' => $exams,
      'subjects' => $subjects,
      'results' => $results,
      'selectedExamId' => $request->exam_id,
      'selectedClassId' => $request->class_id,
      'selectedSubjectId' => $request->subject_id
    ]);
  }

  protected function configureTenantConnection(string $databaseName)
  {
    $school = \App\Models\School::where('database_url', $databaseName)->first();
    if ($school) {
      $school->useAsTenant();
    }
  }
}
