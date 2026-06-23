<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SmsLog;
use App\Models\SmsTemplate;
use App\Models\SchoolClass;
use App\Models\Central\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsController extends Controller
{
    protected $apiToken;
    protected $senderName;
    protected $apiUrl = 'https://messaging-service.co.tz';

    public function __construct()
    {
        $this->apiToken = env('SMS_API_TOKEN');
        $this->senderName = env('SMS_SENDER_NAME', 'DARASA 360');
    }

    /**
     * Get current school from the tenant manager.
     */
    protected function getCurrentSchool(): ?School
    {
        return app('current_school', null);
    }

    /**
     * Calculate number of SMS parts for a message.
     * Standard SMS: 160 chars, Unicode SMS: 70 chars
     */
    public function calculateSmsCount(string $message): int
    {
        // Check if message contains Unicode characters
        $isUnicode = preg_match('/[^\x00-\x7F]/', $message);
        $maxCharsPerSms = $isUnicode ? 70 : 160;

        // For concatenated messages, headers reduce capacity
        $length = mb_strlen($message);
        if ($length <= $maxCharsPerSms) {
            return 1;
        }

        // Concatenated SMS: 153 chars for standard, 67 for Unicode
        $concatChars = $isUnicode ? 67 : 153;
        return (int) ceil($length / $concatChars);
    }

    /**
     * Get SMS credit balance for the current school.
     */
    public function getSmsCredits()
    {
        try {
            $school = null;
            $debugInfo = ['method' => 'none'];

            // Method 1: Try app container
            try {
                $school = app('current_school');
                if ($school) {
                    $debugInfo['method'] = 'app_container';
                }
            } catch (\Exception $e) {
                // App container failed, continue to other methods
                $debugInfo['app_container_error'] = $e->getMessage();
            }

            // Method 2: Try from database name in environment
            if (!$school) {
                $dbName = env('TENANT_DB_DATABASE') ?: env('DB_DATABASE');
                $debugInfo['db_name'] = $dbName;

                if ($dbName) {
                    $school = School::on('central')->where('database_name', $dbName)->first();
                    if ($school) {
                        $debugInfo['method'] = 'env_db_name';
                    }
                }
            }

            // Method 3: Try from session (impersonation)
            if (!$school && session()->has('impersonating_school_id')) {
                $schoolId = session('impersonating_school_id');
                $school = School::on('central')->find($schoolId);
                if ($school) {
                    $debugInfo['method'] = 'impersonation';
                }
            }

            // Method 4: If only one school exists, use it as fallback
            if (!$school) {
                $schoolCount = School::on('central')->count();
                $debugInfo['schools_in_central'] = $schoolCount;

                if ($schoolCount === 0) {
                    return response()->json([
                        'assigned' => 0,
                        'used' => 0,
                        'remaining' => 0,
                        'school_name' => 'Not configured',
                        'message' => 'Run: php artisan central:setup --sync-school'
                    ]);
                }

                // If only one school, use it
                if ($schoolCount === 1) {
                    $school = School::on('central')->first();
                    $debugInfo['method'] = 'single_school_fallback';
                }
            }

            if (!$school) {
                Log::warning('SMS Credits: School not found', $debugInfo);
                return response()->json([
                    'assigned' => 0,
                    'used' => 0,
                    'remaining' => 0,
                    'school_name' => 'Not linked',
                    'message' => 'School not linked. Contact administrator.'
                ]);
            }

            return response()->json([
                'assigned' => $school->sms_credits_assigned ?? 0,
                'used' => $school->sms_credits_used ?? 0,
                'remaining' => $school->sms_credits_remaining ?? 0,
                'school_name' => $school->name,
                'school_id' => $school->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting SMS credits: ' . $e->getMessage());
            return response()->json([
                'assigned' => 0,
                'used' => 0,
                'remaining' => 0,
                'school_name' => 'Error',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Check if school has enough SMS credits.
     */
    protected function checkSmsCredits(int $requiredCount): array
    {
        $school = $this->getCurrentSchool();

        // If no school from app container, try session
        if (!$school) {
            $schoolSlug = session('current_school_slug');
            if ($schoolSlug) {
                $school = School::on('central')->where('slug', $schoolSlug)->first();
            }
        }

        // If still no school, try to find from database name in env
        if (!$school) {
            $dbName = env('TENANT_DB_DATABASE') ?: env('DB_DATABASE');
            if ($dbName) {
                $school = School::on('central')->where('database_name', $dbName)->first();
            }
        }

        // Fallback: try config values
        if (!$school) {
            $dbName = config('database.connections.tenant.database');
            if (!$dbName) {
                $dbName = config('database.connections.mysql.database');
            }
            if ($dbName) {
                $school = School::on('central')->where('database_name', $dbName)->first();
            }
        }

        // If impersonating, get school from session
        if (!$school && session()->has('impersonating_school_id')) {
            $schoolId = session('impersonating_school_id');
            $school = School::on('central')->find($schoolId);
        }

        if (!$school) {
            return ['allowed' => true, 'school' => null, 'message' => 'School not identified - SMS credits not tracked'];
        }

        // Refresh school data from central database
        $school = School::on('central')->find($school->id);

        if (!$school->hasSmsCredits($requiredCount)) {
            return [
                'allowed' => false,
                'message' => "Insufficient SMS credits. Required: {$requiredCount}, Available: {$school->sms_credits_remaining}. Please contact administrator."
            ];
        }

        return ['allowed' => true, 'school' => $school];
    }

    /**
     * Calculate SMS count for a message (API endpoint).
     */
    public function calculateMessageSms(Request $request)
    {
        $message = $request->get('message', '');
        $isUnicode = preg_match('/[^\x00-\x7F]/', $message);

        return response()->json([
            'length' => mb_strlen($message),
            'sms_count' => $this->calculateSmsCount($message),
            'is_unicode' => $isUnicode,
            'max_chars_per_sms' => $isUnicode ? 70 : 160,
        ]);
    }

    public function index()
    {
        return view('sms.index');
    }

    public function indexAccountant()
    {
        $templates = SmsTemplate::where('is_active', true)->get();
        $classes = SchoolClass::where('is_active', true)->orderBy('display_order')->get();

        return view('admin.accountant.modules.sms', compact('templates', 'classes'));
    }

    /**
     * Debug endpoint to troubleshoot school identification.
     */
    public function debugSchool()
    {
        $debug = [
            'env_tenant_db' => env('TENANT_DB_DATABASE'),
            'env_db_database' => env('DB_DATABASE'),
            'config_tenant_db' => config('database.connections.tenant.database'),
            'config_mysql_db' => config('database.connections.mysql.database'),
            'app_container_school' => null,
            'schools_in_central' => [],
        ];

        // Check app container
        $school = app('current_school');
        if ($school) {
            $debug['app_container_school'] = [
                'id' => $school->id,
                'name' => $school->name,
                'database_name' => $school->database_name,
                'sms_credits_assigned' => $school->sms_credits_assigned,
                'sms_credits_used' => $school->sms_credits_used,
            ];
        }

        // List all schools in central database
        try {
            $schools = School::on('central')->get(['id', 'name', 'database_name', 'sms_credits_assigned', 'sms_credits_used']);
            $debug['schools_in_central'] = $schools->toArray();
        } catch (\Exception $e) {
            $debug['schools_error'] = $e->getMessage();
        }

        return response()->json($debug);
    }

    public function logs()
    {
        $logs = SmsLog::with(['student', 'sender'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('sms.logs', compact('logs'));
    }

    public function logsAccountant()
    {
        $logs = SmsLog::with(['student', 'sentBy'])
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return view('admin.accountant.modules.sms-logs', compact('logs'));
    }

    public function sendSms(Request $request)
    {
        // Check if this is a bulk send (from the SMS module) or single send
        if ($request->has('student_ids')) {
            return $this->sendBulkSms($request);
        }

        $validated = $request->validate([
            'recipient_phone' => 'nullable|string',
            'message' => 'required|string',
            'student_id' => 'nullable|exists:students,id',
        ]);

        // Ensure we have a recipient phone number
        if (empty($validated['recipient_phone'])) {
            return response()->json([
                'success' => false,
                'message' => 'Recipient phone number is required',
            ], 400);
        }

        // Calculate SMS count and check credits
        $smsCount = $this->calculateSmsCount($validated['message']);
        $creditCheck = $this->checkSmsCredits($smsCount);

        if (!$creditCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $creditCheck['message'],
            ], 403);
        }

        $school = $creditCheck['school'];

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($this->apiUrl . '/api/sms/v2/text/single', [
                'from' => $this->senderName,
                'to' => $validated['recipient_phone'],
                'text' => $validated['message'],
            ]);

            $responseData = $response->json();

            // Log the SMS
            $smsLog = SmsLog::create([
                'student_id' => $validated['student_id'] ?? null,
                'sent_by' => auth()->id(),
                'recipient_phone' => $validated['recipient_phone'],
                'message' => $validated['message'],
                'message_id' => $responseData['messages'][0]['messageId'] ?? null,
                'status' => $response->successful() ? 'sent' : 'failed',
                'status_code' => $response->status(),
                'status_description' => $responseData['messages'][0]['status']['description'] ?? 'Unknown',
                'sent_at' => now(),
                'sms_count' => $smsCount,
            ]);

            if ($response->successful()) {
                if ($school) {
                    $school->deductSmsCredits($smsCount);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'SMS sent successfully',
                    'data' => $responseData,
                    'sms_credits' => $school ? [
                        'used' => $smsCount,
                        'remaining' => $school->fresh()->sms_credits_remaining,
                    ] : null,
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to send SMS',
                    'error' => $responseData,
                ], 400);
            }
        } catch (\Exception $e) {
            Log::error('SMS sending failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'SMS sending failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function sendBulkSms(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'message' => 'required|string',
            'thank_you_message' => 'nullable|string', // Optional thank you message for fully paid students
            'phone_number' => 'required|in:phone_1,phone_2,both',
        ]);

        $students = Student::whereIn('id', $validated['student_ids'])
            ->with(['particulars', 'schoolClass'])
            ->get();

        // Pre-calculate estimated SMS count
        $estimatedTotalSms = 0;
        $baseMessageSmsCount = $this->calculateSmsCount($validated['message']);
        $thankYouSmsCount = isset($validated['thank_you_message']) ? $this->calculateSmsCount($validated['thank_you_message']) : 0;

        foreach ($students as $student) {
            $phoneCount = 0;
            if ($validated['phone_number'] === 'phone_1' && $student->parent_phone_1) $phoneCount++;
            if ($validated['phone_number'] === 'phone_2' && $student->parent_phone_2) $phoneCount++;
            if ($validated['phone_number'] === 'both') {
                if ($student->parent_phone_1) $phoneCount++;
                if ($student->parent_phone_2) $phoneCount++;
            }
            if ($phoneCount === 0 && $student->phone) $phoneCount = 1;

            // Use base message SMS count as estimate (personalization might vary slightly)
            $estimatedTotalSms += $phoneCount * $baseMessageSmsCount;
        }

        // Check credits before sending
        $creditCheck = $this->checkSmsCredits($estimatedTotalSms);
        if (!$creditCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $creditCheck['message'],
            ], 403);
        }

        $school = $creditCheck['school'];
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $errors = [];
        $totalSmsUsed = 0;

        foreach ($students as $student) {
            // Calculate student's total balance across all academic years
            $totalBalance = $student->particulars->sum(function($p) {
                return ($p->pivot->sales ?? 0) - ($p->pivot->credit ?? 0);
            });

            // Determine which message to use based on payment status
            $isFullyPaid = $totalBalance <= 0;

            // If student is fully paid and no thank you message provided, skip
            if ($isFullyPaid && empty($validated['thank_you_message'])) {
                $skipped++;
                continue;
            }

            // Use appropriate message: thank you for fully paid, reminder for others
            $messageToSend = $isFullyPaid ? $validated['thank_you_message'] : $validated['message'];

            // Determine which phone numbers to send to
            $phoneNumbers = [];
            if ($validated['phone_number'] === 'phone_1' || $validated['phone_number'] === 'both') {
                if ($student->parent_phone_1) {
                    $phoneNumbers[] = $student->parent_phone_1;
                }
            }
            if ($validated['phone_number'] === 'phone_2' || $validated['phone_number'] === 'both') {
                if ($student->parent_phone_2) {
                    $phoneNumbers[] = $student->parent_phone_2;
                }
            }

            // Fall back to student's own phone if no parent phones
            if (empty($phoneNumbers) && $student->phone) {
                $phoneNumbers[] = $student->phone;
            }

            if (empty($phoneNumbers)) {
                $skipped++;
                $errors[] = "{$student->name}: No phone number";
                continue;
            }

            // Replace placeholders in message
            $personalizedMessage = $this->replacePlaceholders($messageToSend, $student);
            $smsCount = $this->calculateSmsCount($personalizedMessage);

            foreach ($phoneNumbers as $phone) {
                // Check remaining credits before each send (only when school is tracked)
                if ($school && !$school->fresh()->hasSmsCredits($smsCount)) {
                    $failed++;
                    $errors[] = "{$student->name}: Insufficient SMS credits";
                    continue;
                }

                try {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiToken,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->post($this->apiUrl . '/api/sms/v2/text/single', [
                        'from' => $this->senderName,
                        'to' => $phone,
                        'text' => $personalizedMessage,
                    ]);

                    $responseData = $response->json();

                    // Log the SMS
                    SmsLog::create([
                        'student_id' => $student->id,
                        'sent_by' => auth()->id(),
                        'recipient_phone' => $phone,
                        'message' => $personalizedMessage,
                        'message_id' => $responseData['messages'][0]['messageId'] ?? null,
                        'status' => $response->successful() ? 'sent' : 'failed',
                        'status_code' => $response->status(),
                        'status_description' => $responseData['messages'][0]['status']['description'] ?? 'Unknown',
                        'sent_at' => now(),
                        'sms_count' => $smsCount,
                    ]);

                    if ($response->successful()) {
                        if ($school) {
                            $school->deductSmsCredits($smsCount);
                        }
                        $totalSmsUsed += $smsCount;
                        $sent++;
                    } else {
                        $failed++;
                        $errors[] = "{$student->name}: API error";
                    }
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "{$student->name}: {$e->getMessage()}";
                    Log::error("SMS to {$student->name} failed: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Sent: {$sent}, Failed: {$failed}, Skipped: {$skipped}",
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'errors' => $errors,
            'sms_credits' => $school ? [
                'used' => $totalSmsUsed,
                'remaining' => $school->fresh()->sms_credits_remaining,
            ] : ['used' => $totalSmsUsed, 'remaining' => null],
        ]);
    }

    private function replacePlaceholders($message, $student)
    {
        // Calculate financial data
        $particulars = $student->particulars;
        $totalSales = $particulars->sum('pivot.sales');
        $totalCredit = $particulars->sum('pivot.credit');
        $balance = $totalSales - $totalCredit;

        // Group particulars by academic year and calculate balances
        $academicYears = \App\Models\AcademicYear::orderBy('start_date', 'asc')->get()->keyBy('id');
        $yearlyBreakdown = [];
        $yearlyBalances = [];
        
        foreach ($particulars as $particular) {
            $sales = $particular->pivot->sales ?? 0;
            $credit = $particular->pivot->credit ?? 0;
            $particularBalance = $sales - $credit;
            $academicYearId = $particular->pivot->academic_year_id;
            
            // Only include if there's an outstanding balance
            if ($particularBalance > 0) {
                $yearName = $academicYearId && isset($academicYears[$academicYearId]) 
                    ? $academicYears[$academicYearId]->name 
                    : 'Unassigned';
                
                if (!isset($yearlyBreakdown[$yearName])) {
                    $yearlyBreakdown[$yearName] = [];
                    $yearlyBalances[$yearName] = 0;
                }
                
                $yearlyBreakdown[$yearName][] = "{$particular->name}: TSh " . number_format($particularBalance, 0);
                $yearlyBalances[$yearName] += $particularBalance;
            }
        }

        // Build academic year breakdown text
        $academicYearBreakdown = '';
        foreach ($yearlyBreakdown as $yearName => $items) {
            $yearTotal = $yearlyBalances[$yearName];
            $academicYearBreakdown .= "\n{$yearName} (TSh " . number_format($yearTotal, 0) . "):\n";
            $academicYearBreakdown .= implode("\n", $items) . "\n";
        }

        // Overdue calculations
        $overdueParticulars = $particulars->filter(function($p) {
            return $p->pivot->deadline && $p->pivot->deadline < now()->toDateString()
                && ($p->pivot->sales - $p->pivot->credit) > 0;
        });
        $totalOverdue = $overdueParticulars->sum(fn($p) => $p->pivot->sales - $p->pivot->credit);
        $overdueCount = $overdueParticulars->count();

        // Build particulars breakdown (all unpaid items)
        $breakdown = $particulars
            ->filter(fn($p) => ($p->pivot->sales - $p->pivot->credit) > 0)
            ->map(function($p) {
                return "{$p->name}: TSh " . number_format($p->pivot->sales - $p->pivot->credit, 0);
            })->join("\n");

        // Build overdue details
        $overdueDetails = $overdueParticulars->map(function($p) {
            $days = now()->diffInDays($p->pivot->deadline);
            return "{$p->name}: TSh " . number_format($p->pivot->sales - $p->pivot->credit, 0) . " ({$days} days)";
        })->join("\n");

        $replacements = [
            '{student_name}' => $student->name,
            '{student_reg}' => $student->student_reg_no,
            '{class}' => $student->schoolClass->name ?? $student->class ?? 'N/A',
            '{total_sales}' => number_format($totalSales, 0),
            '{total_paid}' => number_format($totalCredit, 0),
            '{balance}' => number_format($balance, 0),
            '{total_overdue}' => number_format($totalOverdue, 0),
            '{overdue_count}' => $overdueCount,
            '{particulars_breakdown}' => $breakdown ?: 'No fees assigned',
            '{academic_year_breakdown}' => $academicYearBreakdown ?: 'No outstanding fees',
            '{overdue_details}' => $overdueDetails ?: 'No overdue fees',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    public function checkBalance()
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiToken,
                'Accept' => 'application/json',
            ])->get($this->apiUrl . '/api/v2/balance');

            if ($response->successful()) {
                return response()->json($response->json());
            } else {
                return response()->json([
                    'error' => 'Failed to check balance'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function managePhoneNumbers()
    {
        $students = Student::where('status', 'active')
            ->with('schoolClass')
            ->orderBy('name')
            ->get();

        return view('sms.manage-phones', compact('students'));
    }

    public function managePhoneNumbersAccountant()
    {
        $students = Student::where('status', 'active')
            ->with('schoolClass')
            ->orderBy('name')
            ->get();

        return view('admin.accountant.modules.phone-numbers', compact('students'));
    }

    public function downloadPhoneTemplate()
    {
        $filename = "phone-numbers-template.csv";
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        fputcsv($handle, ['student_reg_no', 'parent_phone_1', 'parent_phone_2']);
        fputcsv($handle, ['STU001', '255712345678', '255787654321']);
        fputcsv($handle, ['STU002', '255723456789', '']);

        fclose($handle);
        exit;
    }

    public function uploadPhoneNumbers(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $data = array_map('str_getcsv', file($file->getRealPath()));
        $headers = array_shift($data);

        $updated = 0;
        $errors = [];

        foreach ($data as $row) {
            $phoneData = array_combine($headers, $row);

            $student = Student::where('student_reg_no', $phoneData['student_reg_no'])->first();

            if ($student) {
                $student->update([
                    'parent_phone_1' => $phoneData['parent_phone_1'] ?? null,
                    'parent_phone_2' => $phoneData['parent_phone_2'] ?? null,
                ]);
                $updated++;
            } else {
                $errors[] = "Student not found: {$phoneData['student_reg_no']}";
            }
        }

        return response()->json([
            'message' => "Updated {$updated} student phone numbers",
            'errors' => $errors,
        ]);
    }

    public function updatePhoneNumber(Request $request, $studentId)
    {
        $student = Student::findOrFail($studentId);

        $validated = $request->validate([
            'parent_phone_1' => 'nullable|string|max:255',
            'parent_phone_2' => 'nullable|string|max:255',
        ]);

        $student->update($validated);

        return response()->json([
            'message' => 'Phone numbers updated successfully',
            'student' => $student,
        ]);
    }

    // SMS Templates
    public function getTemplates()
    {
        $templates = SmsTemplate::where('is_active', true)->get();
        return response()->json($templates);
    }

    public function saveTemplate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'message_en' => 'required|string',
            'message_sw' => 'nullable|string',
            'type' => 'required|in:system,custom',
        ]);

        $validated['created_by'] = auth()->id();

        $template = SmsTemplate::create($validated);

        return response()->json($template, 201);
    }

    public function deleteTemplate($templateId)
    {
        $template = SmsTemplate::findOrFail($templateId);

        if ($template->type === 'system') {
            return response()->json([
                'error' => 'Cannot delete system templates'
            ], 400);
        }

        $template->delete();

        return response()->json([
            'message' => 'Template deleted successfully'
        ]);
    }

    // Get students by class for SMS (accepts class ID or class name)
    public function getStudentsByClass($classIdentifier)
    {
        // Try to find by ID first, then by name
        $class = SchoolClass::where('id', $classIdentifier)
            ->orWhere('name', $classIdentifier)
            ->first();

        if (!$class) {
            return response()->json(['error' => 'Class not found'], 404);
        }

        $students = Student::where('class_id', $class->id)
            ->where('status', 'active')
            ->where(function($query) {
                $query->whereNotNull('parent_phone_1')
                    ->orWhereNotNull('parent_phone_2')
                    ->orWhereNotNull('phone');
            })
            ->get(['id', 'name', 'student_reg_no', 'parent_phone_1', 'parent_phone_2', 'phone']);

        return response()->json($students);
    }

    public function searchStudents(Request $request)
    {
        $search = $request->get('q', '');

        $students = Student::where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('student_reg_no', 'LIKE', "%{$search}%");
            })
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNotNull('parent_phone_1')
                    ->orWhereNotNull('parent_phone_2');
            })
            ->with('schoolClass')
            ->limit(20)
            ->get(['id', 'name', 'student_reg_no', 'class', 'parent_phone_1', 'parent_phone_2']);

        return response()->json($students);
    }

    public function sendOverdueReminders(Request $request)
    {
        $validated = $request->validate([
            'message_template' => 'required|string',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'exists:students,id',
        ]);

        // Get students with overdue balances
        $query = Student::whereHas('particulars', function($q) {
            $q->whereRaw('(sales - credit) > 0')
                ->where('deadline', '<', now());
        })
        ->where('status', 'active')
        ->whereNotNull('parent_phone_1')
        ->with('particulars');

        // Filter by selected student IDs if provided
        if (!empty($validated['student_ids'])) {
            $query->whereIn('id', $validated['student_ids']);
        }

        $overdueStudents = $query->get();

        // Estimate SMS count
        $baseSmsCount = $this->calculateSmsCount($validated['message_template']);
        $estimatedTotal = $overdueStudents->count() * $baseSmsCount;

        // Check credits before sending
        $creditCheck = $this->checkSmsCredits($estimatedTotal);
        if (!$creditCheck['allowed']) {
            return response()->json([
                'success' => false,
                'message' => $creditCheck['message'],
            ], 403);
        }

        $school = $creditCheck['school'];
        $sent = 0;
        $failed = 0;
        $totalSmsUsed = 0;

        foreach ($overdueStudents as $student) {
            $totalOverdue = $student->particulars->sum(function($p) {
                return max(0, $p->pivot->sales - $p->pivot->credit);
            });

            if ($totalOverdue > 0 && $student->parent_phone_1) {
                $message = str_replace(
                    ['{student_name}', '{amount}'],
                    [$student->name, number_format($totalOverdue, 2)],
                    $validated['message_template']
                );

                $smsCount = $this->calculateSmsCount($message);

                // Check credits before each send (only when school is tracked)
                if ($school && !$school->fresh()->hasSmsCredits($smsCount)) {
                    $failed++;
                    continue;
                }

                try {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . $this->apiToken,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ])->post($this->apiUrl . '/api/sms/v2/text/single', [
                        'from' => $this->senderName,
                        'to' => $student->parent_phone_1,
                        'text' => $message,
                    ]);

                    $responseData = $response->json();

                    // Log the SMS
                    SmsLog::create([
                        'student_id' => $student->id,
                        'sent_by' => auth()->id(),
                        'recipient_phone' => $student->parent_phone_1,
                        'message' => $message,
                        'message_id' => $responseData['messages'][0]['messageId'] ?? null,
                        'status' => $response->successful() ? 'sent' : 'failed',
                        'status_code' => $response->status(),
                        'status_description' => $responseData['messages'][0]['status']['description'] ?? 'Unknown',
                        'sent_at' => now(),
                        'sms_count' => $smsCount,
                    ]);

                    if ($response->successful()) {
                        if ($school) {
                            $school->deductSmsCredits($smsCount);
                        }
                        $totalSmsUsed += $smsCount;
                        $sent++;
                    } else {
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $failed++;
                    Log::error("Failed to send overdue reminder to {$student->name}: " . $e->getMessage());
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Sent {$sent} reminders, {$failed} failed",
            'sent' => $sent,
            'failed' => $failed,
            'sms_credits' => $school ? [
                'used' => $totalSmsUsed,
                'remaining' => $school->fresh()->sms_credits_remaining,
            ] : ['used' => $totalSmsUsed, 'remaining' => null],
        ]);
    }
}
