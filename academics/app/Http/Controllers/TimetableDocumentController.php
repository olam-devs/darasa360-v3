<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\School;
use App\Models\TimetableDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TimetableDocumentController extends Controller
{

  public function index(Request $request)
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

    $userRole = $toJson ? $request->input('role') : session('role');
    $query = TimetableDocument::on('tenant')->with('class');
    $classes = collect();
    $isAdmin = $userRole !== 'Student';

    if ($userRole === 'ClassTeacher') {
      $teacher = Teacher::on('tenant')->where('user_id', $userId)->first();
      if ($teacher) {
        $classes = Classroom::on('tenant')->where('class_teacher_id', $userId)->get();
        $query->whereIn('class_id', $classes->pluck('id')->toArray());
      }
    } elseif ($userRole === 'Student') {
      $student = Student::on('tenant')->where('user_id', $userId)->first();
      if ($student) {
        $query->where('class_id', $student->class_id);
        $classes = Classroom::on('tenant')->where('id', $student->class_id)->get();
      }
    } else {
      $classes = Classroom::on('tenant')->get();
    }

    // Apply filters
    if ($request->filled('class_id')) {
      $query->where('class_id', $request->class_id);
    }
    if ($request->filled('type')) {
      $query->where('type', $request->type);
    }

    if ($toJson) {
      $documents = $query->orderByDesc('created_at')->get();

      // Group by class name
      $grouped = $documents->groupBy(function ($doc) {
        return $doc->class->name ?? 'Unknown Class';
      });

      // Map to desired structure
      $responseData = $grouped->map(function ($classDocs, $className) {
        return [
          'class' => $className,
          'documents' => $classDocs->map(function ($doc) {
            // Example of how to get size and priority (customize as needed)
            // You may want to store size and priority in DB or infer them here

            // Dummy logic for size (in KB or MB)
            $size = 'Unknown';
            if (isset($doc->file_size_bytes)) {
              if ($doc->file_size_bytes > 1e6) {
                $size = number_format($doc->file_size_bytes / 1e6, 1) . ' MB';
              } else {
                $size = number_format($doc->file_size_bytes / 1e3) . ' KB';
              }
            }

            // Dummy logic for priority (you may replace with actual DB field)
            $priority = $doc->priority ?? 'medium';

            // Dummy logic for period (you may want to compute this or store it)
            $period = $doc->period ?? '';

            return [
              'id' => $doc->id,
              'title' => $doc->original_name ?? 'Untitled',
              'description' => $doc->description ?? '',
              'period' => $period,
              'type' => $doc->type,
              'size' => $size,
              'lastUpdated' => $doc->updated_at ? $doc->updated_at->format('Y-m-d') : null,
              'priority' => $priority,
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
      ]);
    }

    // Web response
    $documents = $query->paginate(10);
    return view('content.dashboard.student.timetables.index', compact('documents', 'classes', 'isAdmin'));
  }




  public function store(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');
    $this->switchTenantDB();

    $request->validate([
      'class_id' => 'required|exists:tenant.classes,id',
      'type' => 'required|in:class,exam',
      'file' => 'required|mimes:pdf|max:2048',
      'description' => 'nullable|string|max:255',
    ]);

    $uploadedFile = $request->file('file');
    $path = $uploadedFile->store('timetables', 'public');

    TimetableDocument::create([
      'class_id' => $request->class_id,
      'type' => $request->type,
      'document_path' => $path,
      'original_name' => $uploadedFile->getClientOriginalName(),
      'description' => $request->description,
    ]);

    return redirect()->back()->with('success', 'Timetable uploaded successfully.');
  }



  // public function download($id)
  // {
  //   $this->switchTenantDB();

  //   $document = TimetableDocument::findOrFail($id);

  //   return Storage::disk('public')->download(
  //     $document->document_path,
  //     $document->original_name ?? 'Timetable.pdf'
  //   );
  // }

  //test
  public function download(Request $request, $id)
  {
    $toJson = $request->input('toJson', false);
    $userId = $request->input('user_id');
    $schoolCode = $request->input('school_code');

    // Check if this is a mobile API request
    $isMobileApi = $userId && $schoolCode;

    if ($isMobileApi) {
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
      $document = TimetableDocument::findOrFail($id);
      $path = $document->document_path;

      if (!Storage::disk('public')->exists($path)) {
        return response()->json(['error' => 'File not found'], 404);
      }

      // Return JSON only for explicit toJson requests
      if ($toJson) {
        return response()->json([
          'title' => $document->original_name,
          'file_path' => $path,
          'file_size' => Storage::disk('public')->size($path),
          'mime_type' => Storage::disk('public')->mimeType($path),
          'download_url' => url("/timetables/timetables/download/$id?user_id=$userId&school_code=$schoolCode"),
          'message' => 'Timetable ready for download'
        ]);
      }

      $extension = pathinfo($path, PATHINFO_EXTENSION);
      $downloadName = $document->original_name ?? ($document->title . '.' . $extension);

      // Get the file and return it with proper headers
      $file = Storage::disk('public')->get($path);
      $mimeType = Storage::disk('public')->mimeType($path);

      $headers = [
        'Content-Type' => $mimeType,
        'Content-Disposition' => 'attachment; filename="' . $downloadName . '"',
        'Content-Length' => Storage::disk('public')->size($path),
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Expires' => '0'
      ];

      return response($file, 200, $headers);
    } catch (\Exception $e) {
      Log::error('Timetable download error: ' . $e->getMessage());
      return response()->json(['error' => 'Server error: ' . $e->getMessage()], 500);
    }
  }
  //
  public function destroy($id)
  {
    $document = TimetableDocument::findOrFail($id);

    // Delete the file from storage
    Storage::disk('public')->delete($document->document_path);

    // Delete the database record
    $document->delete();

    return redirect()->back()->with('success', 'Timetable deleted successfully.');
  }

  protected function configureTenantConnection($databaseName)
  {
    // Copy the default database config
    $config = config('database.connections.mysql');

    // Override the database name dynamically
    $config['database'] = $databaseName;

    // Set the tenant connection config dynamically
    config(['database.connections.tenant' => $config]);

    // Reconnect to apply changes
    DB::purge('tenant');
    DB::reconnect('tenant');
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
