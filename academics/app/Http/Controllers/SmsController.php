<?php

namespace App\Http\Controllers;

use App\Helpers\SmsHelper;
use App\Models\Classroom;
use App\Models\School;
use App\Models\SmsBalance;
use App\Models\SmsMessage;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

class SmsController extends Controller
{


  public function index(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $schools = School::Where('is_deleted', false)
      ->with('smsBalance')->get();

    $smsBalances = $schools->map(function ($school) {
      $balance = $school->smsBalance;

      return (object)[
        'school_id' => $school->id,
        'school_name' => $school->name,
        'school_code' => $school->school_code,
        'sms_allocated' => $balance->sms_allocated ?? 0,
        'sms_used' => $balance->sms_used ?? 0,
        'sms_remaining' => max(($balance->sms_allocated ?? 0) - ($balance->sms_used ?? 0), 0),
        'has_sms_record' => $balance !== null,
      ];
    });

    // Get current page or default to 1
    $currentPage = Paginator::resolveCurrentPage() ?: 1;

    // Define how many items per page
    $perPage = 5;

    // Slice the collection to get the items to display in current page
    $currentPageItems = $smsBalances->slice(($currentPage - 1) * $perPage, $perPage)->values();

    // Create paginator instance
    $paginatedSmsBalances = new LengthAwarePaginator(
      $currentPageItems,
      $smsBalances->count(),
      $perPage,
      $currentPage,
      [
        'path' => Paginator::resolveCurrentPath(),
        'pageName' => 'page',
      ]
    );

    return view('content.dashboard.super_admin.sms.index', ['smsBalances' => $paginatedSmsBalances]);
  }




  public function update(Request $request, $id)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $request->validate([
      'sms_allocated' => 'required|integer|min:0',
      'operation_type' => 'nullable|in:set,add',
    ]);

    // Try to find SmsBalance by ID (here $id is school_id, or whatever you use)
    $sms = SmsBalance::where('school_id', $id)->first();

    if (!$sms) {
      // If no record found, create a new one with sms_used = 0
      $sms = new SmsBalance();
      $sms->school_id = $id;
      $sms->sms_used = 0; // new record, so no sms used yet
    }

    $operationType = $request->input('operation_type', 'set');
    $amount = $request->input('sms_allocated');

    if ($operationType === 'add') {
      // Add to existing allocation
      $sms->sms_allocated = ($sms->sms_allocated ?? 0) + $amount;
      $message = "Successfully added {$amount} SMS credits. New total: {$sms->sms_allocated}";
    } else {
      // Set total allocation
      $sms->sms_allocated = $amount;
      $message = 'SMS balance updated successfully.';
    }

    $sms->save();

    return redirect()->back()->with('success', $message);
  }

public function showForm(Request $request)
{
    if (!(new HelperController)->isLoggedIn($request)) {
        return redirect('/login');
    }

    $this->configureTenantConnection(session('school_db'));

    $role = session('role');  // get role from session
    $userId = session('user_id'); // logged-in user ID

    if ($role === 'ClassTeacher') {
        // Fetch class IDs assigned to this ClassTeacher from the tenant DB
        $assignedClassIds = DB::connection('tenant')
            ->table('classes')
            ->where('class_teacher_id', $userId)
            ->pluck('id');

        $classes = Classroom::whereIn('id', $assignedClassIds)->get();
    } else {
        $classes = Classroom::all();
    }

    $schoolCode = session('school_code');
    $school = School::where('school_code', $schoolCode)->first();

    $smsBalance = 0;
    $schoolMessageName = ''; // default

    if ($school) {
        $balanceRecord = SmsBalance::where('school_id', $school->id)->first();
        if ($balanceRecord) {
            $smsBalance = $balanceRecord->sms_allocated - $balanceRecord->sms_used;
        }
        $schoolMessageName = $school->message_name ?? $school->name ?? ''; // fallback
    }

    // Fetch sent messages based on role
    if ($role === 'Accountant') {
        $sentMessages = SmsMessage::with('class')
            ->where('sender_id', $userId)
            ->paginate(5);
    } else {
        $sentMessages = SmsMessage::with('class')->paginate(5);
    }

    return view(
        'content.dashboard.super_admin.sms.send',
        compact('classes', 'smsBalance', 'sentMessages', 'schoolMessageName', 'role')
    );
}





  public function getStudents(Request $request, $classId)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $this->configureTenantConnection(session('school_db'));

    $students = Student::where('class_id', $classId)->select('id', 'first_name', 'registration_no')->get();
    return response()->json($students);
  }

  public function sendSms(Request $request)
  {
    if (!(new HelperController)->isLoggedIn($request)) {
      return redirect('/login');
    }

    $this->configureTenantConnection(session('school_db'));

    $request->validate([
      'message' => 'required|string|max:612', // max 4 segments
      'audience' => 'required|string',
      'students' => 'nullable|string',
    ]);

    $message = trim($request->input('message'));
    $audience = $request->input('audience');
    $senderId = session('user_id');

    $selectedStudentIds = $request->filled('students') ? explode(',', $request->input('students')) : [];

    $schoolCode = session('school_code');
    $school = DB::table('schools')->where('school_code', $schoolCode)->first();
    if (!$school) {
      return redirect()->back()->with('error', 'School not found.');
    }

    $schoolNamePrefix = trim($school->message_name ?? $school->name ?? 'School');
    $prefixWithColon = $schoolNamePrefix !== '' ? $schoolNamePrefix . ': ' : '';

    // Fetch sms balance record
    $smsBalanceRecord = DB::table('sms_balances')->where('school_id', $school->id)->first();

    if (!$smsBalanceRecord) {
      return redirect()->back()->with('error', 'SMS balance record not found.');
    }

    // Calculate current balance = allocated - used
    $smsBalance = $smsBalanceRecord->sms_allocated - $smsBalanceRecord->sms_used;

    if ($smsBalance <= 0) {
      return redirect()->back()->with('error', 'Insufficient SMS balance.');
    }

    $studentsQuery = DB::connection('tenant')->table('students')->whereNotNull('phone_number');
    $classIdForLog = null;

    if ($audience === 'all') {
      $students = $studentsQuery->get();
    } elseif (str_starts_with($audience, 'class_')) {
      $classId = explode('_', $audience)[1];
      $classIdForLog = $classId;
      $studentsQuery->where('class_id', $classId);

      if (!empty($selectedStudentIds)) {
        $studentsQuery->whereIn('id', $selectedStudentIds);
      }
      $students = $studentsQuery->get();
    } else {
      return redirect()->back()->with('error', 'Invalid audience selected.');
    }

    if ($students->isEmpty()) {
      return redirect()->back()->with('error', 'No students found for selected audience.');
    }

    $bulkMessages = [];
    $totalSmsCount = 0;

    foreach ($students as $student) {
      $phoneNumber = preg_replace('/^0/', '255', $student->phone_number);
      $personalizedText = $prefixWithColon . $message;

      $segments = SmsHelper::calculateSmsSegments($personalizedText);
      $totalSmsCount += $segments;

      if ($totalSmsCount > $smsBalance) {
        return redirect()->back()->with('error', 'SMS balance too low to send to all selected students.');
      }

      $bulkMessages[] = [
        'to' => $phoneNumber,
        'text' => $personalizedText,
        'from' => $schoolNamePrefix,
      ];
    }

    $sendResult = SmsHelper::sendNextSmsBulk($bulkMessages);

    if (!$sendResult['success']) {
      return redirect()->back()->with('error', 'Failed to send SMS: ' . ($sendResult['error'] ?? 'Unknown error'));
    }

    $now = now();
    $insertData = [];
    $messagesResponse = $sendResult['response']['messages'] ?? [];
    $responseMap = [];

    foreach ($messagesResponse as $resp) {
      $responseMap[$resp['to']] = $resp;
    }

    foreach ($students as $student) {
      $phoneNumber = preg_replace('/^0/', '255', $student->phone_number);
      $personalizedText = $prefixWithColon . $message;
      $messageInfo = $responseMap[$phoneNumber] ?? null;
      $segments = SmsHelper::calculateSmsSegments($personalizedText);

      $insertData[] = [
        'student_id' => $student->id,
        'class_id' => $student->class_id ?? $classIdForLog,
        'phone_number' => $student->phone_number,
        'message' => $personalizedText,
        'sender_id' => $senderId,
        'status' => $messageInfo['status']['name'] ?? 'SENT',
        'sms_count' => $segments,
        'message_id' => $messageInfo['messageId'] ?? null,
        'price' => $messageInfo['price'] ?? null,
        'created_at' => $now,
        'updated_at' => $now,
      ];
    }

    DB::connection('tenant')->table('sms_messages')->insert($insertData);

    // Update sms_used to reflect sent SMS count
    DB::table('sms_balances')->where('school_id', $school->id)->increment('sms_used', $totalSmsCount);

    // Create notification entry for each recipient
    $notificationData = [];
    foreach ($students as $student) {
      $phoneNumber = $student->phone_number;
      $notificationData[] = [
        'type' => 'sms',
        'recipient_phone' => $phoneNumber,
        'recipient_name' => $student->first_name . ' ' . ($student->last_name ?? ''),
        'subject' => 'SMS Notification',
        'message' => $prefixWithColon . $message,
        'status' => 'sent',
        'notification_category' => 'sms',
        'sent_at' => $now,
        'created_at' => $now,
        'updated_at' => $now,
      ];
    }

    if (!empty($notificationData)) {
      DB::connection('tenant')->table('notification_queue')->insert($notificationData);
    }

    return redirect()->route('sms.send.form')->with('success', "SMS sent to {$students->count()} students. Total SMS units used: {$totalSmsCount}.");
  }







  // SmsController.php
  public function searchStudents(Request $request, $classId)
  {
    if (!(new HelperController)->isLoggedIn($request)) return redirect('/login');

    $this->configureTenantConnection(session('school_db'));
    $q = request('q');
    return Student::where('class_id', $classId)
      ->where(function ($query) use ($q) {
        $query->where('first_name', 'like', "%$q%")
          ->orWhere('registration_no', 'like', "%$q%")
          ->orWhere('last_name', 'like', "%$q%")
          ->orWhere('email', 'like', "%$q%");
      })
      ->select('id', 'first_name', 'middle_name', 'last_name', 'registration_no')
      ->limit(10)
      ->get();
  }


  private function configureTenantConnection($schoolDb)
  {
    $school = \App\Models\School::where('database_url', $schoolDb)->first();
    if ($school) {
      $school->useAsTenant();
    }
  }
}
