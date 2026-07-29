<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\Classroom;
use App\Models\Notes;
use App\Models\School;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;


class AssignmentController extends Controller
{
  protected function configureTenantConnection($databaseName)
  {
    $school = \App\Models\School::where('database_url', $databaseName)->first();
    if ($school) {
      $school->useAsTenant();
    }
  }

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

    $userId = session('user_id');
    $role = session('role');

    $isAdmin = true;
    $query = Assignment::on('tenant')->with(['class', 'subject'])->orderBy('uploaded_at', 'desc');

    if (in_array($role, ['ClassTeacher', 'Teacher'])) {
      $isAdmin = false;

      // Get teacher_id from Teachers table using user_id
      $teacher = Teacher::on('tenant')->where('user_id', $userId)->first();

      if ($teacher) {
        $teacherId = $teacher->id;

        // Get assigned classes & subjects from subject_teacher table
        $subjectTeacherRecords = DB::connection('tenant')
          ->table('subject_teacher')
          ->where('teacher_id', $teacherId)
          ->get(['class_id', 'subject_id']);

        $assignedClassIds = $subjectTeacherRecords->pluck('class_id')->unique()->toArray();
        $assignedSubjectIds = $subjectTeacherRecords->pluck('subject_id')->unique()->toArray();

        $query->where(function ($q) use ($assignedClassIds, $assignedSubjectIds) {
          if (!empty($assignedClassIds)) {
            $q->whereIn('class_id', $assignedClassIds);
          }
          if (!empty($assignedSubjectIds)) {
            $q->orWhereIn('subject_id', $assignedSubjectIds);
          }
        });

        // If role is strictly "Teacher", also filter by assigned_by
        if ($role === 'Teacher') {
          $query->where('assigned_by', $userId);
        }
      } else {
        // Teacher record not found
        $query->whereRaw('0 = 1');
      }
    }

    // Search filter
    if ($request->filled('search')) {
      $search = $request->input('search');
      $query->where('title', 'like', "%{$search}%");
    }

    $assignments = $query->paginate(10)->appends($request->query());

    $classes = $isAdmin
      ? Classroom::on('tenant')->get()
      : Classroom::on('tenant')
      ->whereIn('id', $assignedClassIds ?? [])
      ->get();


    // Subjects
    if ($isAdmin) {
      // Admin sees all subjects
      $subjects = Subject::on('tenant')->get();
    } else {
      if ($role === 'ClassTeacher') {
        // Get all subjects of the classes assigned to this ClassTeacher
        $subjectIds = DB::connection('tenant')
          ->table('classroom_subject')
          ->whereIn('classroom_id', $assignedClassIds ?? [])
          ->pluck('subject_id')
          ->unique()
          ->toArray();

        $subjects = Subject::on('tenant')
          ->whereIn('id', $subjectIds)
          ->get();
      } elseif ($role === 'Teacher') {
        // Get subjects assigned to the teacher
        $subjectIds = DB::connection('tenant')
          ->table('subject_teacher')
          ->where('teacher_id', $teacherId ?? 0)
          ->pluck('subject_id')
          ->unique()
          ->toArray();

        $subjects = Subject::on('tenant')
          ->whereIn('id', $subjectIds)
          ->get();
      } else {
        $subjects = collect(); // empty collection for other roles
      }
    }


    return view('content.dashboard.owner.assignments.index', compact('assignments', 'classes', 'subjects', 'isAdmin'));
  }


  public function getSubjectsForClass($classId)
  {
    $userId = session('user_id');
    $role = session('role');

    $this->switchTenantDB();

    // Admin roles can see all subjects for the class
    if (in_array($role, ['Owner', 'Admin', 'Headmaster', 'Academic'])) {
      $subjectIds = DB::connection('tenant')
        ->table('classroom_subject')
        ->where('classroom_id', $classId)
        ->pluck('subject_id')
        ->toArray();
    } elseif ($role === 'ClassTeacher') {
      $subjectIds = DB::connection('tenant')
        ->table('classroom_subject')
        ->where('classroom_id', $classId)
        ->pluck('subject_id')
        ->toArray();
    } elseif ($role === 'Teacher') {
      $teacher = Teacher::on('tenant')->where('user_id', $userId)->first();
      if (!$teacher) return response()->json([]);

      $subjectIds = DB::connection('tenant')
        ->table('subject_teacher')
        ->where('teacher_id', $teacher->id)
        ->where('class_id', $classId)
        ->pluck('subject_id')
        ->toArray();
    } else {
      $subjectIds = [];
    }

    $subjects = Subject::on('tenant')->whereIn('id', $subjectIds)->get(['id', 'name']);

    return response()->json($subjects);
  }


  public function notesHome(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $userId = session('user_id');
    $role = session('role');
    $isAdmin = true;

    $query = Notes::on('tenant')->with(['class', 'subject'])->orderBy('uploaded_at', 'desc');

    // Role-based filtering
    if (in_array($role, ['ClassTeacher', 'Teacher'])) {
      $isAdmin = false;

      if ($role === 'ClassTeacher') {
        $assignedClassIds = Classroom::on('tenant')
          ->where('class_teacher_id', $userId)
          ->pluck('id')
          ->toArray();
        if (!empty($assignedClassIds)) {
          $query->whereIn('class_id', $assignedClassIds);
        } else {
          $query->whereRaw('0=1');
        }
      } elseif ($role === 'Teacher') {
        $teacher = Teacher::on('tenant')->where('user_id', $userId)->first();
        if ($teacher) {
          $teacherId = $teacher->id;
          $subjectTeacherRecords = DB::connection('tenant')
            ->table('subject_teacher')
            ->where('teacher_id', $teacherId)
            ->get(['class_id', 'subject_id']);

          $assignedClassIds = $subjectTeacherRecords->pluck('class_id')->unique()->toArray();
          $assignedSubjectIds = $subjectTeacherRecords->pluck('subject_id')->unique()->toArray();

          $query->where(function ($q) use ($assignedClassIds, $assignedSubjectIds) {
            if (!empty($assignedClassIds)) {
              $q->whereIn('class_id', $assignedClassIds);
            }
            if (!empty($assignedSubjectIds)) {
              $q->orWhereIn('subject_id', $assignedSubjectIds);
            }
          });

          $query->where('uploaded_by', $userId);
        } else {
          $query->whereRaw('0=1');
        }
      }
    } else {
      $query->where('uploaded_by', $userId);
    }

    // Search
    if ($request->filled('search')) {
      $search = $request->input('search');
      $query->where('title', 'like', "%{$search}%");
    }

    $notesPaginated = $query->paginate(10)->appends($request->query());

    // Group notes by class and subject
    $groupedNotes = $notesPaginated->getCollection()->groupBy('class_id')->map(function ($notesInClass) {
      $class = $notesInClass->first()->class;
      $subjects = $notesInClass->groupBy('subject_id')->map(function ($notesInSubject) {
        $subject = $notesInSubject->first()->subject;
        return [
          'subject' => $subject,
          'notes' => $notesInSubject
        ];
      });
      return [
        'class' => $class,
        'subjects' => $subjects
      ];
    });

    $notesPaginated->setCollection($groupedNotes);

    // Classes dropdown
    if ($isAdmin) {
      $classes = Classroom::on('tenant')->get();
    } else {
      if ($role === 'ClassTeacher') {
        $classes = Classroom::on('tenant')->where('class_teacher_id', $userId)->get();
      } elseif ($role === 'Teacher') {
        $teacher = Teacher::on('tenant')->where('user_id', $userId)->first();
        if ($teacher) {
          $assignedClassIds = DB::connection('tenant')
            ->table('subject_teacher')
            ->where('teacher_id', $teacher->id)
            ->pluck('class_id')
            ->unique()
            ->toArray();

          $classes = Classroom::on('tenant')->whereIn('id', $assignedClassIds)->get();
        } else {
          $classes = collect();
        }
      } else {
        $classes = Classroom::on('tenant')->get();
      }
    }

    // Subjects dropdown
    if ($isAdmin) {
      $subjects = Subject::on('tenant')->get();
    } else {
      $teacher = Teacher::on('tenant')->where('user_id', $userId)->first();
      if ($teacher) {
        $subjectIds = DB::connection('tenant')
          ->table('subject_teacher')
          ->where('teacher_id', $teacher->id)
          ->pluck('subject_id')
          ->unique()
          ->toArray();

        $subjects = Subject::on('tenant')->whereIn('id', $subjectIds)->get();
      } else {
        $subjects = collect();
      }
    }


    return view('content.dashboard.owner.notes.index', [
      'notes' => $notesPaginated,
      'classes' => $classes,
      'subjects' => $subjects,
      'isAdmin' => $isAdmin
    ]);
  }



  public function create(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $this->switchTenantDB();

    $classes = Classroom::on('tenant')->orderBy('name')->get();
    $subjects = Subject::on('tenant')->orderBy('name')->get();

    return view('content.dashboard.owner.assignments.create', compact('classes', 'subjects'));
  }

  public function store(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $this->switchTenantDB();

    $request->validate([
      'class_id' => 'required|exists:tenant.classes,id',
      'subject_id' => 'required|exists:tenant.subjects,id',
      'title' => 'required|string|max:255',
      'description' => 'nullable|string',
      'assignment_file' => 'required|file|mimes:pdf,doc,docx|max:10240', // max 10MB
    ]);

    $filePath = $request->file('assignment_file')->store('assignments', 'tenant_files');

    Assignment::on('tenant')->create([
      'class_id'    => $request->class_id,
      'subject_id'  => $request->subject_id,
      'title'       => $request->title,
      'description' => $request->description,
      'file_path'   => $filePath,
      'uploaded_at' => now(),
      'assigned_by' => session('user_id'),
    ]);

    return redirect()->route('assignments.index')->with('success', 'Assignment uploaded successfully.');
  }


  public function notesStore(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $request->validate([
      'class_id' => 'required|exists:tenant.classes,id',
      'subject_id' => 'required|exists:tenant.subjects,id',
      'title' => 'required|string|max:255',
      'description' => 'nullable|string',
      'notes_file' => 'required|file|mimes:pdf,doc,docx|max:10240', // max 10MB
    ]);

    $filePath = $request->file('notes_file')->store('notes', 'tenant_files');

    Notes::on('tenant')->create([
      'class_id' => $request->class_id,
      'subject_id' => $request->subject_id,
      'title' => $request->title,
      'description' => $request->description,
      'file_path' => $filePath,
      'uploaded_at' => now(),
      'uploaded_by' => session('user_id'),
    ]);

    return redirect()->route('notes.home')->with('success', 'Notes uploaded successfully.');
  }


  // For assignments
  public function download(Request $request, $id)
  {
    // if (!(new HelperController)->isLoggedIn($request)) {
    //   return $request->wantsJson()
    //     ? response()->json(['error' => 'Unauthorized'], 401)
    //     : redirect('/login');
    // }

    $toJson = $request->input('toJson', false);
    $userId = $request->input('user_id');
    $schoolCode = $request->input('school_code');

    if ($toJson && $userId && $schoolCode) {
      // Mobile/API request
      $school = School::where('school_code', $schoolCode)->first();

      if (!$school) {
        return response()->json(['error' => 'Invalid school code'], 400);
      }

      $this->configureTenantConnection($school->database_url);
    } else {
      // Web/session request
      if (!session()->has('user_id') || !session()->has('school_db')) {
        return $request->wantsJson()
          ? response()->json(['error' => 'Session expired. Please login again.'], 401)
          : redirect('/login');
      }

      $schoolDb = session('school_db');
      $this->configureTenantConnection($schoolDb);
    }

    try {
      $assignment = Assignment::on('tenant')->findOrFail($id);
      $path = $assignment->file_path;

      if (!Storage::disk('tenant_files')->exists($path)) {
        return $request->wantsJson()
          ? response()->json(['error' => 'File not found'], 404)
          : abort(404, 'File not found.');
      }

      if ($request->wantsJson()) {
        return response()->json([
          'title' => $assignment->title,
          'file_path' => $path,
          'download_url' => url("/assignments/download/$id"),
          'message' => 'Assignment ready for download'
        ]);
      }

      $extension = pathinfo($path, PATHINFO_EXTENSION);
      $downloadName = $assignment->title . '.' . $extension;

      return Storage::disk('tenant_files')->download($path, $downloadName);
    } catch (\Exception $e) {
      return $request->wantsJson()
        ? response()->json(['error' => $e->getMessage()], 500)
        : abort(500, $e->getMessage());
    }
  }

  // For notes
  public function notesDownload(Request $request, $id)
  {
    // if (!(new HelperController)->isLoggedIn($request)) {
    //   return $request->wantsJson()
    //     ? response()->json(['error' => 'Unauthorized'], 401)
    //     : redirect('/login');
    // }

    $toJson = $request->input('toJson', false);
    $userId = $request->input('user_id');
    $schoolCode = $request->input('school_code');

    if ($toJson && $userId && $schoolCode) {
      // Mobile/API request
      $school = School::where('school_code', $schoolCode)->first();

      if (!$school) {
        return response()->json(['error' => 'Invalid school code'], 400);
      }

      $this->configureTenantConnection($school->database_url);
    } else {
      // Web/session request
      if (!session()->has('user_id') || !session()->has('school_db')) {
        return $request->wantsJson()
          ? response()->json(['error' => 'Session expired. Please login again.'], 401)
          : redirect('/login');
      }

      $schoolDb = session('school_db');
      $this->configureTenantConnection($schoolDb);
    }

    try {
      $note = Notes::on('tenant')->findOrFail($id);
      $path = $note->file_path;

      if (!Storage::disk('tenant_files')->exists($path)) {
        return $request->wantsJson()
          ? response()->json(['error' => 'File not found'], 404)
          : abort(404, 'File not found.');
      }

      if ($request->wantsJson()) {
        return response()->json([
          'title' => $note->title,
          'file_path' => $path,
          'download_url' => url("/notes/download/$id"),
          'message' => 'Note ready for download'
        ]);
      }

      $extension = pathinfo($path, PATHINFO_EXTENSION);
      $downloadName = $note->title . '.' . $extension;

      return Storage::disk('tenant_files')->download($path, $downloadName);
    } catch (\Exception $e) {
      return $request->wantsJson()
        ? response()->json(['error' => $e->getMessage()], 500)
        : abort(500, $e->getMessage());
    }
  }

  public function destroy(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $assignment = Assignment::on('tenant')->findOrFail($id);

    if (Storage::disk('tenant_files')->exists($assignment->file_path)) {
      Storage::disk('tenant_files')->delete($assignment->file_path);
    }

    $assignment->delete();

    return redirect()->route('assignments.index')->with('success', 'Assignment deleted successfully.');
  }

  public function notesDestroy(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->switchTenantDB();

    $note = Notes::on('tenant')->findOrFail($id);

    if (Storage::disk('tenant_files')->exists($note->file_path)) {
      Storage::disk('tenant_files')->delete($note->file_path);
    }

    $note->delete();

    return redirect()->route('notes.home')->with('success', 'Note deleted successfully.');
  }


  public function edit(Request $request, Assignment $assignment)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $classes = Classroom::on('tenant')->orderBy('name')->get();
    $subjects = Subject::on('tenant')->orderBy('name')->get();

    return view('content.dashboard.owner.assignments.edit', compact('assignment', 'classes', 'subjects'));
  }

  public function update(Request $request, Assignment $assignment)
  {

    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $request->validate([
      'title' => 'required|string|max:255',
      'class_id' => 'required|exists:tenant.classes,id',
      'subject_id' => 'required|exists:tenant.subjects,id',
    ]);

    $assignment->update([
      'title' => $request->title,
      'class_id' => $request->class_id,
      'subject_id' => $request->subject_id,
    ]);

    return redirect()->route('assignments.index')->with('success', 'Assignment updated successfully.');
  }

  // public function destroy(Assignment $assignment)
  // {
  //   $this->switchTenantDB();
  //   $assignment->delete();
  //   return redirect()->route('assignments.index')->with('success', 'Assignment deleted successfully.');
  // }
}
