<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Central\School;
use App\Models\Platform\PlatformClass;
use App\Models\Platform\PlatformSchool;
use App\Models\Platform\PlatformStudent;
use App\Services\PlatformRegistry;
use App\Services\TenantSyncService;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PlatformStudentController extends Controller
{
    public function __construct(
        protected PlatformRegistry $registry,
        protected TenantSyncService $syncService,
        protected ActivityLogger $activityLogger
    ) {}

    // -------------------------------------------------------------------------
    // CLASSES
    // -------------------------------------------------------------------------

    public function classesIndex(School $school)
    {
        $platformSchool = $this->resolvePlatformSchool($school);
        $classes = PlatformClass::where('school_id', $platformSchool->id)->orderBy('name')->get();
        return view('superadmin.platform.classes', compact('school', 'platformSchool', 'classes'));
    }

    public function storeClass(Request $request, School $school)
    {
        $request->validate([
            'name'   => 'required|string|max:100',
            'level'  => 'nullable|string|max:50',
            'stream' => 'nullable|string|max:50',
        ]);

        $platformSchool = $this->resolvePlatformSchool($school);

        $class = PlatformClass::create([
            'school_id' => $platformSchool->id,
            'name'      => $request->name,
            'level'     => $request->level,
            'stream'    => $request->stream,
        ]);

        // Sync to Finance tenant immediately if online
        if ($school->has_finance) {
            $financeClassId = $this->syncService->syncClassToFinance($class, $school);
            if ($financeClassId) {
                $class->update(['finance_class_id' => $financeClassId, 'synced_finance' => true]);
            }
        }

        $sa = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction($sa, 'class_created', "Created class '{$class->name}' for {$school->name}", $school);

        return back()->with('success', "Class '{$class->name}' created and synced.");
    }

    public function destroyClass(School $school, PlatformClass $class)
    {
        $class->delete();
        return back()->with('success', 'Class removed.');
    }

    // -------------------------------------------------------------------------
    // STUDENTS
    // -------------------------------------------------------------------------

    public function studentsIndex(School $school)
    {
        $platformSchool = $this->resolvePlatformSchool($school);
        $classes = PlatformClass::where('school_id', $platformSchool->id)->orderBy('name')->get();
        $students = PlatformStudent::where('school_id', $platformSchool->id)
            ->with('platformClass')
            ->orderBy('last_name')->paginate(50);

        return view('superadmin.platform.students', compact('school', 'platformSchool', 'classes', 'students'));
    }

    public function storeStudent(Request $request, School $school)
    {
        $request->validate([
            'first_name'   => 'required|string|max:100',
            'middle_name'  => 'nullable|string|max:100',
            'last_name'    => 'required|string|max:100',
            'gender'       => 'required|in:Male,Female',
            'date_of_birth'  => 'nullable|date',
            'class_id'     => 'nullable|exists:platform.platform_classes,id',
            'parent_name'  => 'nullable|string|max:200',
            'parent_phone' => 'nullable|string|max:20',
            'parent_email' => 'nullable|email|max:200',
        ]);

        $platformSchool = $this->resolvePlatformSchool($school);

        $regNo = $this->registry->nextRegNo($platformSchool->id, 1, $platformSchool->code);

        $student = PlatformStudent::create([
            'school_id'        => $platformSchool->id,
            'student_reg_no'   => $regNo,
            'first_name'       => $request->first_name,
            'middle_name'      => $request->middle_name,
            'last_name'        => $request->last_name,
            'gender'           => $request->gender,
            'date_of_birth'     => $request->date_of_birth,
            'platform_class_id' => $request->class_id,
            'parent_name'      => $request->parent_name,
            'parent_phone'     => $request->parent_phone,
            'parent_email'     => $request->parent_email,
            'status'           => 'active',
        ]);

        // Push to Finance tenant
        if ($school->has_finance) {
            $this->syncService->syncStudentToFinance($student, $school);
        }

        $sa = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction($sa, 'student_created', "Added student {$regNo} ({$student->fullName()}) for {$school->name}", $school);

        return back()->with('success', "Student {$student->fullName()} added. Reg No: {$regNo}");
    }

    /**
     * Import students via CSV/Excel-like plain CSV upload.
     * Expected columns: first_name, middle_name, last_name, gender, dob, class_name, parent_name, parent_phone, parent_email
     */
    public function importStudents(Request $request, School $school)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
            'class_id' => 'nullable|exists:platform.platform_classes,id',
        ]);

        $platformSchool = $this->resolvePlatformSchool($school);
        $file    = $request->file('csv_file');
        $handle  = fopen($file->getRealPath(), 'r');
        $headers = array_map('trim', fgetcsv($handle));

        $created = 0;
        $errors  = [];
        $row     = 1;

        while (($line = fgetcsv($handle)) !== false) {
            $row++;
            $data = array_combine($headers, array_map('trim', $line));

            $v = Validator::make($data, [
                'first_name' => 'required|string',
                'last_name'  => 'required|string',
                'gender'     => 'required|in:Male,Female',
            ]);

            if ($v->fails()) {
                $errors[] = "Row {$row}: " . implode(', ', $v->errors()->all());
                continue;
            }

            try {
                $regNo = $this->registry->nextRegNo($platformSchool->id, 1, $platformSchool->code);

                // Resolve class by name if class_id not provided
                $classId = $request->class_id;
                if (!$classId && !empty($data['class_name'])) {
                    $cls = PlatformClass::where('school_id', $platformSchool->id)
                        ->where('name', $data['class_name'])
                        ->first();
                    $classId = $cls?->id;
                }

                $student = PlatformStudent::create([
                    'school_id'         => $platformSchool->id,
                    'student_reg_no'    => $regNo,
                    'first_name'        => $data['first_name'],
                    'middle_name'       => $data['middle_name'] ?? null,
                    'last_name'         => $data['last_name'],
                    'gender'            => $data['gender'],
                    'date_of_birth'      => !empty($data['dob']) ? $data['dob'] : null,
                    'platform_class_id' => $classId,
                    'parent_name'       => $data['parent_name'] ?? null,
                    'parent_phone'      => $data['parent_phone'] ?? null,
                    'parent_email'      => $data['parent_email'] ?? null,
                    'status'            => 'active',
                ]);

                if ($school->has_finance) {
                    $this->syncService->syncStudentToFinance($student, $school);
                }

                $created++;
            } catch (\Throwable $e) {
                $errors[] = "Row {$row}: " . $e->getMessage();
            }
        }

        fclose($handle);

        $sa = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction($sa, 'students_imported', "Imported {$created} students for {$school->name}", $school);

        $msg = "{$created} students imported.";
        if ($errors) {
            $msg .= ' Errors: ' . implode(' | ', array_slice($errors, 0, 5));
        }

        return back()->with($errors && !$created ? 'error' : 'success', $msg);
    }

    /**
     * Trigger a full re-sync of all platform students → all enabled tenant systems.
     */
    public function syncAll(School $school)
    {
        $results = $this->syncService->syncAllStudentsForSchool($school);

        $sa = auth('superadmin')->user();
        $this->activityLogger->logSuperAdminAction($sa, 'students_synced', "Synced: Finance={$results['finance']}, Academics={$results['academics']} for {$school->name}", $school);

        return back()->with('success', "Sync complete — Finance: {$results['finance']}, Academics: {$results['academics']} student(s) updated.");
    }

    public function destroyStudent(School $school, PlatformStudent $student)
    {
        $student->delete();
        return back()->with('success', 'Student removed from central registry.');
    }

    // -------------------------------------------------------------------------

    protected function resolvePlatformSchool(School $school): PlatformSchool
    {
        if ($school->platform_school_id) {
            return PlatformSchool::findOrFail($school->platform_school_id);
        }
        abort(404, 'This school has no platform registry record.');
    }
}
