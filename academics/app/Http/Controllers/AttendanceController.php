<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Classroom;
use App\Models\DailyAttendanceEntry;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\AttendanceMonthSheetExport;
use App\Exports\AttendanceTemplateExport;
use App\Imports\AttendanceUploadImport;
use Carbon\Carbon;
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
    $today = now()->toDateString();

    // Only clear TODAY's entries for this attendance record before
    // re-inserting - this used to delete every date ever recorded for the
    // whole attendance_id, so taking attendance on a new day silently wiped
    // out every previous day's history.
    DailyAttendanceEntry::on('tenant')
      ->where('attendance_id', $attendanceId)
      ->whereDate('date', $today)
      ->delete();

    foreach ($allStudents as $student) {
      $status = in_array($student->id, $presentStudentIds) ? 'present' : 'absent';

      DailyAttendanceEntry::on('tenant')->create([
        'attendance_id' => $attendanceId,
        'student_id' => $student->id,
        'status' => $status,
        'date' => $today,
      ]);
    }

    return redirect()->back()->with('success', 'Attendance submitted successfully.');
  }

  /**
   * Process a filled-in attendance template (see AttendanceMonthSheetExport
   * for the exact row layout this expects: row 1 is the teacher-editable
   * cutoff date, row 4 is the header with one column per date).
   *
   * Upserts per (attendance_id, student_id, date) instead of wipe-and-replace
   * like submitAttendance() used to, so re-uploading a template - or mixing
   * direct "Take Attendance" edits with template uploads - never destroys
   * unrelated dates' history.
   */
  public function uploadAttendanceTemplate(Request $request, $attendanceId)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $request->validate([
      'file' => 'required|file|mimes:xlsx,xls',
    ]);

    $attendance = Attendance::on('tenant')->findOrFail($attendanceId);
    $rangeStart = Carbon::parse($attendance->from_date);
    $rangeEnd = Carbon::parse($attendance->to_date);

    $students = Student::on('tenant')
      ->where('class_id', $attendance->class_id)
      ->when($attendance->stream_id, fn($q) => $q->where('stream_id', $attendance->stream_id))
      ->get()
      ->keyBy(fn($s) => (string) $s->registration_no);

    $sheets = Excel::toArray(new AttendanceUploadImport(), $request->file('file'));

    $saved = 0;
    $headerRowIndex = AttendanceMonthSheetExport::HEADER_ROW - 1; // 0-indexed

    DB::connection('tenant')->beginTransaction();
    try {
      foreach ($sheets as $sheetRows) {
        if (count($sheetRows) <= $headerRowIndex) {
          continue; // not a sheet this importer recognizes
        }

        $cutoffRaw = trim((string) ($sheetRows[0][1] ?? ''));
        $cutoff = $cutoffRaw !== '' ? Carbon::parse($cutoffRaw) : Carbon::today();
        // A teacher could type a future/out-of-range date by mistake - never
        // let the sheet apply attendance beyond today or beyond this record's
        // own range regardless of what's written in the cell.
        if ($cutoff->gt(Carbon::today())) $cutoff = Carbon::today();
        if ($cutoff->gt($rangeEnd)) $cutoff = $rangeEnd;
        if ($cutoff->lt($rangeStart)) continue; // nothing in range to apply

        $headerRow = $sheetRows[$headerRowIndex];
        $dateColumns = [];
        foreach ($headerRow as $colIndex => $value) {
          if ($colIndex < 3 || $value === null || $value === '') continue;
          try {
            // createFromFormat() inherits the current time-of-day for any
            // part not in the format string, so without startOfDay() a date
            // equal to today's calendar date compares as "after" the
            // midnight $cutoff below and gets wrongly excluded.
            $date = Carbon::createFromFormat('d/m/Y', trim((string) $value))->startOfDay();
          } catch (\Throwable $e) {
            continue;
          }
          if ($date->lt($rangeStart) || $date->gt($rangeEnd) || $date->gt($cutoff)) continue;
          $dateColumns[$colIndex] = $date;
        }

        if (empty($dateColumns)) continue;

        for ($r = $headerRowIndex + 1; $r < count($sheetRows); $r++) {
          $row = $sheetRows[$r];
          $regNo = trim((string) ($row[2] ?? ''));
          if ($regNo === '' || !$students->has($regNo)) continue;
          $student = $students->get($regNo);

          foreach ($dateColumns as $colIndex => $date) {
            $cell = trim((string) ($row[$colIndex] ?? ''));
            $status = strtoupper($cell) === 'X' ? 'absent' : 'present';

            DailyAttendanceEntry::on('tenant')->updateOrCreate(
              [
                'attendance_id' => $attendance->id,
                'student_id' => $student->id,
                'date' => $date->toDateString(),
              ],
              ['status' => $status]
            );
            $saved++;
          }
        }
      }

      DB::connection('tenant')->commit();
    } catch (\Throwable $e) {
      DB::connection('tenant')->rollBack();
      return redirect()->back()->with('error', 'Failed to import attendance template: ' . $e->getMessage());
    }

    if ($saved === 0) {
      return redirect()->back()->with('error', 'Nothing was imported - check the file matches the downloaded template and the cutoff date/registration numbers are valid.');
    }

    return redirect()->back()->with('success', "Attendance imported: {$saved} entries saved.");
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
