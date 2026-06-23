<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Announcement;
use App\Models\Classroom;
use App\Models\School;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

class AnnouncementController extends Controller
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

  public function update($id, Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found in session.');
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    $request->validate([
      'title' => 'required|string|max:255',
      'content' => 'required|string',
      'classes' => 'required|array',
      'classes.*' => [
        'required',
        Rule::exists('tenant.classes', 'id'),
      ],
      'announcement_file' => 'nullable|file|mimes:pdf|max:10240',
    ]);

    $createdBy = session('user_id');

    $announcement = Announcement::where('id', $id)
      ->where('created_by', $createdBy)
      ->firstOrFail();

    // Handle optional file upload
    if ($request->hasFile('announcement_file')) {
      $filePath = $request->file('announcement_file')->store('announcements', 'tenant_files');
      $announcement->filepath = $filePath;
    }

    $announcement->title = $request->title;
    $announcement->content = $request->content;
    $announcement->save();

    // Sync classes in tenant pivot table
    DB::connection('tenant')->table('announcement_class')
      ->where('announcement_id', $announcement->id)
      ->delete();

    foreach ($request->classes as $classId) {
      DB::connection('tenant')->table('announcement_class')->insert([
        'announcement_id' => $announcement->id,
        'class_id' => $classId,
      ]);
    }

    return redirect()->route('announcements.create')->with('success', 'Announcement updated successfully.');
  }

  public function studentAnnouncements(Request $request)
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
      $tenantDb = session('school_db');
      $this->configureTenantConnection($tenantDb);
    }

    // Get the student
    $student = Student::on('tenant')->where('user_id', $userId)->first();
    if (!$student) {
      if ($toJson) {
        return response()->json(['status' => 'error', 'message' => 'Student not found'], 404);
      }
      abort(403, 'Student not found or unauthorized');
    }

    $classId = $student->class_id;
    if (!$classId) {
      if ($toJson) {
        return response()->json(['status' => 'error', 'message' => 'Student class not assigned'], 404);
      }
      abort(404, 'Student class not assigned');
    }

    // Get announcement IDs from tenant pivot table
    $announcementIds = DB::connection('tenant')
      ->table('announcement_class')
      ->where('class_id', $classId)
      ->pluck('announcement_id')
      ->unique()
      ->toArray();

    // Apply pagination here
    $announcements = Announcement::whereIn('id', $announcementIds)
      ->with('classes')
      ->orderBy('created_at', 'desc')
      ->paginate(5);

    if ($toJson) {
      return response()->json([
        'status' => 'success',
        'announcements' => $announcements->getCollection()->map(function ($announcement) {
          // Check if file exists and get full URL
          $fileUrl = null;
          $fileName = null;
          $fileSize = null;

          if (!empty($announcement->filepath)) {
            if (Storage::disk('tenant_files')->exists($announcement->filepath)) {
              $fileUrl = url("/announcements/download/{$announcement->id}");
              $fileName = basename($announcement->title);

              // Get file size
              try {
                $fileSize = Storage::disk('tenant_files')->size($announcement->filepath);
                // Convert to human readable format
                $fileSize = $this->formatFileSize($fileSize);
              } catch (\Exception $e) {
                $fileSize = 'Unknown size';
              }
            }
          }

          return [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'content' => $announcement->content ?? $announcement->description ?? '',
            'author' => $announcement->author ?? 'Administration',
            'date' => $announcement->created_at->toIso8601String(),
            'category' => $announcement->category ?? 'General',
            'priority' => $announcement->priority ?? 'medium',
            'pinned' => (bool)($announcement->pinned ?? false),
            'has_attachment' => !empty($announcement->filepath),
            'file_info' => [
              'url' => $fileUrl,
              'name' => $fileName,
              'size' => $fileSize,
              'extension' => $fileName ? pathinfo($fileName, PATHINFO_EXTENSION) : null,
            ]
          ];
        }),
        'pagination' => [
          'total' => $announcements->total(),
          'current_page' => $announcements->currentPage(),
          'last_page' => $announcements->lastPage(),
          'per_page' => $announcements->perPage(),
        ]
      ]);
    }

    // Web view
    return view('content.dashboard.student.announcements.student_announcements', [
      'announcements' => $announcements,
    ]);
  }

  /**
   * Format file size to human readable format
   */
  private function formatFileSize($bytes)
  {
    if ($bytes == 0) return '0 Bytes';

    $units = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    $i = floor(log($bytes, 1024));
    $size = round($bytes / pow(1024, $i), 2);

    return $size . ' ' . $units[$i];
  }








  public function create(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $userId = session('user_id');

    // Fetch only announcements created by the logged-in user
    $announcements = Announcement::with('classes')
      ->where('created_by', $userId)
      ->latest()
      ->get();

    $classes = Classroom::all();

    return view('content.dashboard.student.announcements.create', compact('announcements', 'classes'));
  }




  public function store(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    // Step 1: Get tenant DB from session
    $tenantDb = session('school_db');
    if (!$tenantDb) {
      return back()->with('error', 'Tenant database not found in session.');
    }

    // Step 2: Set tenant DB connection dynamically
    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Step 3: Validate request, including optional file upload
    $request->validate([
      'title' => 'required|string|max:255',
      'content' => 'required|string',
      'classes' => 'required|array',
      'classes.*' => [
        'required',
        Rule::exists('tenant.classes', 'id'),
      ],
      'announcement_file' => 'nullable|file|mimes:pdf|max:10240', // optional PDF file, max 10MB
    ]);

    // Step 4: Handle file upload if present
    $filePath = null;
    if ($request->hasFile('announcement_file')) {
      // Store file in 'announcements' folder using 'tenant_files' disk (make sure disk exists)
      $filePath = $request->file('announcement_file')->store('announcements', 'tenant_files');
    }

    // Step 5: Create the announcement (assuming the announcements table is central)
    $createdBy = session('user_id');
    $announcement = Announcement::create([
      'title' => $request->title,
      'content' => $request->content,
      'filepath' => $filePath,  // Save the file path (nullable)
      'created_by' => $createdBy,
    ]);
    foreach ($request->classes as $classId) {
      DB::connection('tenant')->table('announcement_class')->insert([
        'announcement_id' => $announcement->id,
        'class_id' => $classId,
      ]);
    }
    return redirect()->route('announcements.create')->with('success', 'Announcement created successfully.');
  }



  public function download(Request $request, $id)
  {

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
      $announcement = Announcement::on('tenant')->findOrFail($id);
      $path = $announcement->filepath;

      if (!Storage::disk('tenant_files')->exists($path)) {
        return $request->wantsJson()
          ? response()->json(['error' => 'File not found'], 404)
          : abort(404, 'File not found.');
      }

      if ($request->wantsJson()) {
        return response()->json([
          'title' => $announcement->title,
          'file_path' => $path,
          'download_url' => url("/announcements/download/$id"),
          'message' => 'Announcement file ready for download'
        ]);
      }

      $extension = pathinfo($path, PATHINFO_EXTENSION);
      $downloadName = $announcement->title . '.' . $extension;

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

    $announcement = Announcement::findOrFail($id);
    $announcement->classes()->detach();
    $announcement->delete();

    return redirect()->back()->with('success', 'Announcement deleted successfully.');
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
}
