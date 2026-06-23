<?php

namespace App\Http\Controllers;

use App\Models\ClassTimetable;
use App\Models\ExamTimetable;
use App\Models\Classroom;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TimetableController extends Controller
{

  public function create(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $type = $request->query('type', 'class');

    // Pull common data
    $classes = Classroom::all();
    $subjects = Subject::all();
    $teachers = Teacher::all(); // Only for class timetable

    return view('timetables.create', compact('type', 'classes', 'subjects', 'teachers'));
  }

  public function store(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $request->validate([
      'type' => 'required|in:class,exam',
      'class_id' => 'required|exists:classes,id',
      'subject_id' => 'required|exists:subjects,id',
      'day_of_week' => 'required_if:type,class',
      'start_time' => 'required|date_format:H:i',
      'end_time' => 'required|date_format:H:i|after:start_time',
      'teacher_id' => 'nullable|exists:teachers,id',
      'exam_date' => 'required_if:type,exam|date',
    ]);

    if ($request->type === 'class') {
      ClassTimetable::create([
        'class_id' => $request->class_id,
        'subject_id' => $request->subject_id,
        'day_of_week' => $request->day_of_week,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
        'teacher_id' => $request->teacher_id,
      ]);
    } else {
      ExamTimetable::create([
        'class_id' => $request->class_id,
        'subject_id' => $request->subject_id,
        'exam_date' => $request->exam_date,
        'start_time' => $request->start_time,
        'end_time' => $request->end_time,
      ]);
    }

    return redirect()->back()->with('success', 'Timetable added successfully.');
  }

  public function index(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $type = $request->get('type', 'class'); // default to class timetable

    // Set connection (if needed based on tenant setup)
    $connection = 'tenant';

    if ($type === 'exam') {
      $timetables = DB::connection($connection)
        ->table('exam_timetables')
        ->join('classes', 'exam_timetables.class_id', '=', 'classes.id')
        ->join('subjects', 'exam_timetables.subject_id', '=', 'subjects.id')
        ->select('exam_timetables.*', 'classes.name as class_name', 'subjects.name as subject_name')
        ->orderBy('exam_timetables.day_of_week')
        ->paginate(10);
    } else {
      $timetables = DB::connection($connection)
        ->table('class_timetables')
        ->join('classes', 'class_timetables.class_id', '=', 'classes.id')
        ->join('subjects', 'class_timetables.subject_id', '=', 'subjects.id')
        ->leftJoin('teachers', 'class_timetables.teacher_id', '=', 'teachers.id')
        ->select('class_timetables.*', 'classes.name as class_name', 'subjects.name as subject_name', 'teachers.first_name', 'teachers.last_name')
        ->orderBy('class_timetables.day_of_week')
        ->paginate(10);
    }

    // Load supporting dropdown data (from tenant DB)
    $classes = DB::connection($connection)->table('classes')->get();
    $subjects = DB::connection($connection)->table('subjects')->get();
    $teachers = DB::connection($connection)->table('teachers')->get();

    return view('content.dashboard.student.timetables.index', compact(
      'timetables',
      'type',
      'classes',
      'subjects',
      'teachers'
    ));
  }



  public function classTimetable($classId)
  {
    if (!(new HelperController)->isLoggedIn(request())) return redirect('/login');
    $timetables = ClassTimetable::where('class_id', $classId)
      ->orderByRaw("FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')")
      ->orderBy('start_time')
      ->with(['subject', 'teacher'])
      ->get();

    return view('timetables.class', compact('timetables', 'classId'));
  }

  public function examTimetable($classId)
  {
    if (!(new HelperController)->isLoggedIn(request())) return redirect('/login');
    $exams = ExamTimetable::where('class_id', $classId)
      ->orderBy('exam_date')
      ->orderBy('start_time')
      ->with('subject')
      ->get();

    return view('timetables.exam', compact('exams', 'classId'));
  }

  // Add methods for create/store/edit if needed
}
