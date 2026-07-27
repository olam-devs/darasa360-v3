<?php

namespace App\Http\Controllers;

use App\Models\Scholarship;
use App\Models\SchoolClass;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\SuspenseAccount;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with('schoolClass')->get();

        return view('students.index', compact('students'));
    }

    public function apiIndex(Request $request)
    {
        $query = Student::with('schoolClass');

        if ($request->has('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $students = $query->orderBy('name')->get();

        return response()->json(['students' => $students]);
    }

    public function create()
    {
        $classes = SchoolClass::where('is_active', true)->orderBy('display_order')->get();

        return view('students.create', compact('classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_reg_no' => 'required|string|unique:students',
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'class_id' => 'required|exists:school_classes,id',
            'parent_phone_1' => 'nullable|string|max:255',
            'parent_phone_2' => 'nullable|string|max:255',
            'admission_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,graduated',
        ]);

        // Get class name
        $class = SchoolClass::find($validated['class_id']);
        $validated['class'] = $class->name;

        $student = Student::create($validated);

        return response()->json($student, 201);
    }

    public function show($id)
    {
        $student = Student::with(['schoolClass', 'particulars'])->findOrFail($id);

        return view('students.show', compact('student'));
    }

    public function edit($id)
    {
        $student = Student::findOrFail($id);
        $classes = SchoolClass::where('is_active', true)->orderBy('display_order')->get();

        return view('students.edit', compact('student', 'classes'));
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);

        $validated = $request->validate([
            'student_reg_no' => 'required|string|unique:students,student_reg_no,'.$id,
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'class_id' => 'required|exists:school_classes,id',
            'parent_phone_1' => 'nullable|string|max:255',
            'parent_phone_2' => 'nullable|string|max:255',
            'admission_date' => 'nullable|date',
            'status' => 'required|in:active,inactive,graduated',
        ]);

        // Get class name
        $class = SchoolClass::find($validated['class_id']);
        $validated['class'] = $class->name;

        $student->update($validated);

        return response()->json($student);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);

        // Don't delete vouchers, just detach particulars
        $student->particulars()->detach();
        $student->delete();

        return response()->json(['message' => 'Student deleted successfully']);
    }

    public function searchStudents(Request $request)
    {
        $search = $request->get('q', '');

        $students = Student::where('name', 'LIKE', "%{$search}%")
            ->orWhere('student_reg_no', 'LIKE', "%{$search}%")
            ->with('schoolClass')
            ->limit(20)
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'student_reg_no' => $student->student_reg_no,
                    'class' => $student->schoolClass->name ?? $student->class,
                ];
            });

        return response()->json($students);
    }

    // Alias for searchStudents
    public function search(Request $request)
    {
        return $this->searchStudents($request);
    }

    public function apiClasses()
    {
        $classes = SchoolClass::where('is_active', true)
            ->orderBy('display_order')
            ->get();

        return response()->json($classes);
    }

    public function getStudentPaymentSummary($studentId)
    {
        $student = Student::with('particulars')->findOrFail($studentId);

        $summary = $student->particulars->map(function ($particular) {
            return [
                'particular_id' => $particular->id,
                'particular_name' => $particular->name,
                'sales' => $particular->pivot->sales,
                'credit' => $particular->pivot->credit,
                'balance' => $particular->pivot->sales - $particular->pivot->credit,
                'deadline' => $particular->pivot->deadline,
            ];
        });

        return response()->json([
            'student' => $student,
            'particulars' => $summary,
            'total_sales' => $summary->sum('sales'),
            'total_paid' => $summary->sum('credit'),
            'total_balance' => $summary->sum('balance'),
        ]);
    }

    public function downloadStudentTemplate()
    {
        $filename = 'student-import-template.csv';
        $handle = fopen('php://output', 'w');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'"');

        fputcsv($handle, ['student_reg_no', 'name', 'gender', 'class_name', 'parent_phone_1', 'parent_phone_2', 'admission_date']);
        fputcsv($handle, ['STU001', 'John Doe Mwangi', 'male', 'Grade 1', '255712345678', '255787654321', '2024-01-15']);
        fputcsv($handle, ['STU002', 'Jane Smith Kamau', 'female', 'Form 1', '255723456789', '', '2024-01-15']);

        fclose($handle);
        exit;
    }

    public function uploadStudentCsv(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        $data = array_map('str_getcsv', file($file->getRealPath()));
        $headers = array_shift($data);

        $imported = 0;
        $parentAccountsCreated = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($data as $row) {
                $studentData = array_combine($headers, $row);

                // Find or create class
                $class = SchoolClass::where('name', $studentData['class_name'])->first();
                if (! $class) {
                    $errors[] = "Class not found: {$studentData['class_name']} for student {$studentData['name']}";

                    continue;
                }

                $student = Student::updateOrCreate(
                    ['student_reg_no' => $studentData['student_reg_no']],
                    [
                        'name' => $studentData['name'],
                        'gender' => $studentData['gender'],
                        'class_id' => $class->id,
                        'class' => $class->name,
                        'parent_phone_1' => $studentData['parent_phone_1'] ?? null,
                        'parent_phone_2' => $studentData['parent_phone_2'] ?? null,
                        'admission_date' => $studentData['admission_date'] ?? null,
                        'status' => 'active',
                    ]
                );

                // Auto-create parent portal access
                // Parent can login using student registration number only
                // No separate account needed - session-based authentication
                $parentAccountsCreated++;

                $imported++;
            }

            DB::commit();

            return response()->json([
                'message' => "Successfully imported {$imported} students. Parent portal access enabled for all students.",
                'imported' => $imported,
                'parent_accounts_created' => $parentAccountsCreated,
                'errors' => $errors,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Full student dossier: all years, assignments, vouchers, scholarships, suspense, SMS.
     */
    public function studentProfilePage()
    {
        $settings = SchoolSetting::getSettings();

        return view('admin.accountant.modules.student-profile', compact('settings'));
    }

    public function getStudentFullProfile($studentId)
    {
        $student = Student::with('schoolClass')->findOrFail($studentId);

        $assignments = DB::table('particular_student as ps')
            ->join('particulars as p', 'p.id', '=', 'ps.particular_id')
            ->leftJoin('academic_years as ay', 'ay.id', '=', 'ps.academic_year_id')
            ->leftJoin('scholarships as sch', function ($join) {
                $join->on('sch.student_id', '=', 'ps.student_id')
                    ->on('sch.particular_id', '=', 'ps.particular_id')
                    ->where('sch.is_active', '=', 1)
                    ->whereRaw('sch.academic_year_id <=> ps.academic_year_id');
            })
            ->where('ps.student_id', $studentId)
            ->orderByDesc('ps.academic_year_id')
            ->orderBy('p.name')
            ->select([
                'ps.particular_id',
                'p.name as particular_name',
                'ps.academic_year_id',
                'ay.name as academic_year_name',
                'ps.sales',
                'ps.debit',
                'ps.credit',
                'ps.overpayment',
                'ps.deadline',
                DB::raw('COALESCE(sch.forgiven_amount, 0) as scholarship_forgiven'),
                DB::raw('GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0), 0) as expected_net'),
                DB::raw('GREATEST(ps.sales - COALESCE(sch.forgiven_amount, 0) - ps.credit, 0) as outstanding'),
            ])
            ->get();

        $vouchers = Voucher::with(['particular', 'book'])
            ->where('student_id', $studentId)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(1000)
            ->get();

        $scholarships = Scholarship::with(['particular', 'academicYear'])
            ->where('student_id', $studentId)
            ->orderByDesc('applied_date')
            ->get();

        $suspenseAccounts = SuspenseAccount::where('resolved_student_id', $studentId)
            ->orderByDesc('date')
            ->limit(200)
            ->get();

        $smsLogs = $student->smsLogs()
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->limit(40)
            ->get();

        $totals = [
            'expected_gross' => (float) $assignments->sum('sales'),
            'expected_net' => (float) $assignments->sum(fn ($r) => (float) $r->expected_net),
            'collected' => (float) $assignments->sum('credit'),
            'scholarships' => (float) $assignments->sum('scholarship_forgiven'),
            'outstanding' => (float) $assignments->sum(fn ($r) => (float) $r->outstanding),
            'advance_balance' => (float) ($student->advance_balance ?? 0),
        ];

        return response()->json([
            'student' => $student,
            'assignments' => $assignments,
            'vouchers' => $vouchers,
            'scholarships' => $scholarships,
            'suspense_accounts' => $suspenseAccounts,
            'sms_logs' => $smsLogs,
            'totals' => $totals,
            'links' => [
                'ledger_pdf' => route('api.ledgers.student.pdf', ['studentId' => $studentId]),
                'ledger_csv' => route('api.ledgers.student.csv', ['studentId' => $studentId]),
                'invoice_pdf' => route('accountant.invoices.student.pdf', ['studentId' => $studentId]),
                'student_statement' => route('reports.student-statement', ['studentId' => $studentId]),
            ],
        ]);
    }

    // Student Promotion
    public function promotionPage()
    {
        $classes = SchoolClass::where('is_active', true)->orderBy('display_order')->get();

        return view('admin.accountant.modules.student-promotion', compact('classes'));
    }

    public function getStudentsForPromotion(Request $request)
    {
        $classId = $request->get('source_class_id') ?? $request->get('class_id');

        $students = Student::where('class_id', $classId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'student_reg_no', 'class_id', 'class']);

        return response()->json(['students' => $students]);
    }

    public function promoteStudents(Request $request)
    {
        $validated = $request->validate([
            'student_ids' => 'required|array',
            'student_ids.*' => 'exists:students,id',
            'source_class_id' => 'required|exists:school_classes,id',
            'destination_class_id' => 'required|exists:school_classes,id',
        ]);

        // Prevent promoting to the same class
        if ($validated['source_class_id'] == $validated['destination_class_id']) {
            return response()->json([
                'error' => 'Cannot promote students to the same class. Please select a different destination class.',
            ], 400);
        }

        $destinationClass = SchoolClass::find($validated['destination_class_id']);

        DB::beginTransaction();
        try {
            // Only promote students that are actually in the source class
            $updated = Student::whereIn('id', $validated['student_ids'])
                ->where('class_id', $validated['source_class_id'])
                ->update([
                    'class_id' => $destinationClass->id,
                    'class' => $destinationClass->name,
                ]);

            DB::commit();

            return response()->json([
                'message' => $updated.' students promoted successfully to '.$destinationClass->name,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getStudentParticulars($studentId)
    {
        $student = Student::with('particulars')->findOrFail($studentId);

        $particulars = $student->particulars->map(function ($particular) {
            return [
                'id' => $particular->id,
                'name' => $particular->name,
                'sales' => $particular->pivot->sales,
                'debit' => $particular->pivot->debit,
                'credit' => $particular->pivot->credit,
                'balance' => $particular->pivot->sales + $particular->pivot->debit - $particular->pivot->credit,
                'deadline' => $particular->pivot->deadline,
            ];
        });

        return response()->json($particulars);
    }

    public function getStudentParticularDetails($studentId, $particularId)
    {
        $student = Student::findOrFail($studentId);
        $particular = $student->particulars()->where('particular_id', $particularId)->first();

        if (! $particular) {
            return response()->json(['error' => 'Particular not assigned to student'], 404);
        }

        return response()->json([
            'particular_id' => $particular->id,
            'particular_name' => $particular->name,
            'sales' => $particular->pivot->sales,
            'debit' => $particular->pivot->debit,
            'credit' => $particular->pivot->credit,
            'balance' => $particular->pivot->sales + $particular->pivot->debit - $particular->pivot->credit,
            'deadline' => $particular->pivot->deadline,
        ]);
    }

    // ── Portal Password Management ──────────────────────────────────────────

    public function portalPasswordsPage()
    {
        $classes  = SchoolClass::orderBy('name')->get();
        $settings = SchoolSetting::getSettings();
        return view('admin.accountant.modules.portal-passwords', compact('classes', 'settings'));
    }

    public function searchStudentsForPassword(Request $request)
    {
        $q = trim($request->input('q', ''));
        $classId = $request->input('class_id');

        $query = Student::select('id', 'name', 'student_reg_no', 'class_id', 'portal_password', 'portal_password_set_at', 'portal_password_set_by')
            ->with('schoolClass:id,name');

        if ($q !== '') {
            $query->where(function ($sq) use ($q) {
                $sq->where('name', 'like', "%{$q}%")
                   ->orWhere('student_reg_no', 'like', "%{$q}%");
            });
        }

        if ($classId) {
            $query->where('class_id', $classId);
        }

        return response()->json($query->orderBy('name')->limit(100)->get()->map(fn($s) => [
            'id'           => $s->id,
            'name'         => $s->name,
            'reg_no'       => $s->student_reg_no,
            'class'        => $s->schoolClass?->name ?? '—',
            'has_password' => !empty($s->portal_password),
            'set_at'       => $s->portal_password_set_at?->diffForHumans(),
            'set_by'       => $s->portal_password_set_by,
        ]));
    }

    public function setPortalPassword(Request $request, $studentId)
    {
        $request->validate([
            'password' => 'required|string|min:4',
        ]);

        $student = Student::findOrFail($studentId);
        $setBy   = Auth::user()?->name ?? 'Admin';

        $student->update([
            'portal_password'        => Hash::make($request->password),
            'portal_password_set_at' => now(),
            'portal_password_set_by' => $setBy,
        ]);

        return response()->json(['success' => true, 'message' => "Password set for {$student->name}"]);
    }

    public function bulkSetPortalPassword(Request $request)
    {
        $request->validate([
            'password'   => 'required|string|min:4',
            'class_id'   => 'nullable|integer',
            'student_ids'=> 'nullable|array',
            'student_ids.*' => 'integer',
        ]);

        $hash  = Hash::make($request->password);
        $setBy = Auth::user()?->name ?? 'Admin';
        $now   = now();

        $query = Student::query();

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        } elseif ($request->filled('student_ids')) {
            $query->whereIn('id', $request->student_ids);
        } else {
            return response()->json(['success' => false, 'message' => 'Specify a class or student list.'], 422);
        }

        $count = $query->count();
        $query->update([
            'portal_password'        => $hash,
            'portal_password_set_at' => $now,
            'portal_password_set_by' => $setBy,
        ]);

        return response()->json(['success' => true, 'message' => "Password set for {$count} student(s)."]);
    }
}
