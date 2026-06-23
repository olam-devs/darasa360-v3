<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\DailyAttendanceEntry;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\AttendanceTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{

  public function listAttendanceTypes(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $userId = session('user_id');

    // Try to find the class assigned to the class teacher
    $class = Classroom::on('tenant')->where('class_teacher_id', $userId)->first();

    // If no class assigned, show all classes (probably for admins)
    if (!$class) {
      $classes = Classroom::on('tenant')->get();

      $query = Attendance::on('tenant')->orderBy('from_date', 'desc');

      if ($request->filled('search')) {
        $search = $request->input('search');
        $query->where(function ($q) use ($search) {
          $q->where('title', 'like', "%{$search}%")
            ->orWhere('description', 'like', "%{$search}%");
        });
      }

      $attendanceTypes = $query->paginate(10)->appends($request->query());

      return view('content.dashboard.owner.classes.attendance_page', compact('attendanceTypes', 'classes'));
    }

    // If class teacher, limit attendance to their class
    $query = Attendance::on('tenant')
      ->where('class_id', $class->id)
      ->orderBy('from_date', 'desc');

    if ($request->filled('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('title', 'like', "%{$search}%")
          ->orWhere('description', 'like', "%{$search}%");
      });
    }

    $attendanceTypes = $query->paginate(10)->appends($request->query());

    // Always pass $classes
    $classes = Classroom::on('tenant')->get();

    return view('content.dashboard.owner.classes.attendance_page', compact('attendanceTypes', 'classes'));
  }



  public function TeacherAttendanceTypes(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $userId = session('user_id');

    //change modality
    $class = Classroom::on('tenant')->where('class_teacher_id', $userId)->first();

    if (!$class) {
      return view('content.dashboard.classTeacher.teacher_attendance_type', [
        'attendanceTypes' => collect(),
        'classes' => collect(),
        'students' => collect(),
      ]);
    }

    // Get students for this class
    $students = Student::on('tenant')
      ->where('class_id', $class->id)
      ->get();

    $query = Attendance::on('tenant')
      ->where('class_id', $class->id)
      ->orderBy('from_date', 'desc');

    if ($request->filled('search')) {
      $search = $request->input('search');
      $query->where(function ($q) use ($search) {
        $q->where('title', 'like', "%{$search}%")
          ->orWhere('description', 'like', "%{$search}%");
      });
    }

    $attendanceTypes = $query->paginate(10)->appends($request->query());


    return view('content.dashboard.classTeacher.teacher_attendance_type', [
      'attendanceTypes' => $attendanceTypes,
      'classes' => collect([$class]),
      'students' => $students
    ]);
  }



  public function store(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $request->validate([
      'title' => 'required|string|max:255',
      'from_date' => 'required|date',
      'due_date' => 'required|date|after_or_equal:from_date',
    ]);

    $createdBy = session('user_id');

    Attendance::on('tenant')->create([
      'title' => $request->title,
      'from_date' => $request->from_date,
      'to_date' => $request->due_date,
      'class_id' => $request->class_id,
      'stream_id' => $request->stream_id,
      'created_by' => $createdBy
    ]);


    return redirect()->back()->with('success', 'Attendance created successfully.');
  }

  public function update(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $request->validate([
      'title' => 'required|string|max:255',
      'from_date' => 'required|date',
      'due_date' => 'required|date|after_or_equal:from_date',
    ]);

    $attendance = Attendance::on('tenant')->findOrFail($id);

    $attendance->update([
      'title' => $request->title,
      'from_date' => $request->from_date,
      'to_date' => $request->due_date,
      'class_id' => $request->class_id,
      'stream_id' => $request->stream_id,
    ]);

    return redirect()->back()->with('success', 'Attendance updated successfully.');
  }


  public function submitAttendance(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $request->validate([
      'attendance_id' => 'required|integer|exists:tenant.attendances,id',
      'present_students' => 'array',
      'present_students.*' => 'integer|exists:tenant.students,id',
    ]);

    $attendanceId = $request->input('attendance_id');
    $presentStudentIds = $request->input('present_students', []);


    $attendance = Attendance::on('tenant')->findOrFail($attendanceId);
    $query = Student::on('tenant')->where('class_id', $attendance->class_id);

    if ($attendance->stream_id) {
      $query->where('stream_id', $attendance->stream_id);
    }

    $allStudents = $query->get();
    DailyAttendanceEntry::on('tenant')->where('attendance_id', $attendanceId)->delete();

    foreach ($allStudents as $student) {
      $status = in_array($student->id, $presentStudentIds) ? 'present' : 'absent';

      DailyAttendanceEntry::on('tenant')->create([
        'attendance_id' => $attendanceId,
        'student_id' => $student->id,
        'status' => $status,
        'date' => now(),
      ]);
    }

    return redirect()->back()->with('success', 'Attendance submitted successfully.');
  }


  public function destroy(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $attendance = Attendance::on('tenant')->findOrFail($id);

    // Delete related daily attendance entries first
    DailyAttendanceEntry::on('tenant')->where('attendance_id', $id)->delete();

    // Then delete the attendance record
    $attendance->delete();

    return redirect()->back()->with('success', 'Attendance deleted successfully.');
  }


  public function downloadAttendanceTemplate(Request $request, $attendanceId)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $attendance = Attendance::on('tenant')->findOrFail($attendanceId);

    // Get class name for filename
    $className = $attendance->class ? $attendance->class->name : 'Class';
    $title = $attendance->title ?? 'Attendance';

    $fileName = str_replace(' ', '_', $title) . '_' . str_replace(' ', '_', $className) . '_' . date('Y-m-d') . '.xlsx';

    return Excel::download(new AttendanceTemplateExport($attendanceId), $fileName);
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
}
