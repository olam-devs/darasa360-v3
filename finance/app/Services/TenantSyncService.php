<?php

namespace App\Services;

use App\Models\Platform\PlatformStudent;
use App\Models\Platform\PlatformClass;
use App\Models\Platform\PlatformSchool;
use App\Models\Central\School;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TenantSyncService
{
    protected TenantDatabaseManager $tenantManager;

    public function __construct(TenantDatabaseManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    // -------------------------------------------------------------------------
    // CLASS SYNC
    // -------------------------------------------------------------------------

    /**
     * Sync a platform class into the Finance tenant (school_classes table).
     * Returns the Finance class_id on success.
     */
    public function syncClassToFinance(PlatformClass $platformClass, School $financeSchool): ?int
    {
        try {
            return $this->tenantManager->executeForSchool($financeSchool, function () use ($platformClass) {
                $existing = DB::connection('tenant')->table('school_classes')
                    ->where('id', $platformClass->finance_class_id)
                    ->first();

                if ($existing) {
                    DB::connection('tenant')->table('school_classes')
                        ->where('id', $platformClass->finance_class_id)
                        ->update(['name' => $platformClass->name, 'updated_at' => now()]);
                    return $platformClass->finance_class_id;
                }

                $id = DB::connection('tenant')->table('school_classes')->insertGetId([
                    'name'        => $platformClass->name,
                    'description' => $platformClass->stream ?? null,
                    'is_active'   => true,
                    'display_order' => 0,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                return $id;
            });
        } catch (\Throwable $e) {
            Log::error("TenantSyncService: class→Finance failed for platform_class#{$platformClass->id}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync a platform class into the Academics tenant (classes + streams tables).
     * Returns the Academics class_id on success.
     *
     * @param PlatformClass $platformClass
     * @param array{host:string,database:string,username:string,password:string} $academicsConn
     */
    public function syncClassToAcademics(PlatformClass $platformClass, array $academicsConn): ?int
    {
        try {
            $conn = $this->temporaryConnection('academics_sync', $academicsConn);

            // Upsert class
            $existingClass = DB::connection($conn)->table('classes')
                ->where('id', $platformClass->academics_class_id)
                ->first();

            if ($existingClass) {
                DB::connection($conn)->table('classes')
                    ->where('id', $platformClass->academics_class_id)
                    ->update(['name' => $platformClass->name, 'updated_at' => now()]);
                $classId = $platformClass->academics_class_id;
            } else {
                $classId = DB::connection($conn)->table('classes')->insertGetId([
                    'name'       => $platformClass->name,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Ensure a default stream exists for this class
            $stream = DB::connection($conn)->table('streams')
                ->where('class_id', $classId)
                ->first();

            if (!$stream) {
                DB::connection($conn)->table('streams')->insert([
                    'name'       => $platformClass->stream ?: 'A',
                    'class_id'   => $classId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return $classId;
        } catch (\Throwable $e) {
            Log::error("TenantSyncService: class→Academics failed for platform_class#{$platformClass->id}: " . $e->getMessage());
            return null;
        }
    }

    // -------------------------------------------------------------------------
    // STUDENT SYNC
    // -------------------------------------------------------------------------

    /**
     * Sync a platform student into the Finance tenant (students table).
     */
    public function syncStudentToFinance(PlatformStudent $student, School $financeSchool): bool
    {
        try {
            $this->tenantManager->executeForSchool($financeSchool, function () use ($student) {
                $existing = DB::connection('tenant')->table('students')
                    ->where('student_reg_no', $student->student_reg_no)
                    ->first();

                $payload = [
                    'name'           => $student->fullName(),
                    'student_reg_no' => $student->student_reg_no,
                    'class_id'       => $student->platform_class_id
                                            ? $this->resolveFinanceClassId($student->platform_class_id)
                                            : null,
                    'parent_name'    => $student->parent_name,
                    'parent_phone_1' => $student->parent_phone,
                    'parent_email'   => $student->parent_email,
                    'gender'         => $student->gender,
                    'date_of_birth'  => $student->date_of_birth,
                    'status'         => $student->status,
                    'updated_at'     => now(),
                ];

                if ($existing) {
                    DB::connection('tenant')->table('students')
                        ->where('student_reg_no', $student->student_reg_no)
                        ->update($payload);
                } else {
                    DB::connection('tenant')->table('students')
                        ->insert(array_merge($payload, ['created_at' => now()]));
                }
            });

            $student->update(['synced_finance' => true]);
            return true;
        } catch (\Throwable $e) {
            Log::error("TenantSyncService: student→Finance failed for {$student->student_reg_no}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync a platform student into the Academics tenant (schoolUsers + students tables).
     *
     * @param PlatformStudent $student
     * @param array{host:string,database:string,username:string,password:string} $academicsConn
     * @param int $studentRoleId  The `school_roles.id` for the 'student' role in this tenant
     */
    public function syncStudentToAcademics(PlatformStudent $student, array $academicsConn, int $studentRoleId = 1): bool
    {
        try {
            $conn = $this->temporaryConnection('academics_sync', $academicsConn);

            // 1. Upsert schoolUsers row
            $schoolUser = DB::connection($conn)->table('schoolUsers')
                ->where('registration_no', $student->student_reg_no)
                ->first();

            $fullName = $student->fullName();
            $defaultPassword = Hash::make($student->student_reg_no); // student logs in with reg no initially

            if ($schoolUser) {
                DB::connection($conn)->table('schoolUsers')
                    ->where('registration_no', $student->student_reg_no)
                    ->update([
                        'username'    => $fullName,
                        'updated_at'  => now(),
                    ]);
                $userId = $schoolUser->id;
            } else {
                $userId = DB::connection($conn)->table('schoolUsers')->insertGetId([
                    'username'        => $fullName,
                    'registration_no' => $student->student_reg_no,
                    'email'           => $student->parent_email ?: null,
                    'password'        => $defaultPassword,
                    'role_id'         => $studentRoleId,
                    'is_active'       => 1,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }

            // 2. Resolve stream_id for this student's class
            $streamId = null;
            if ($student->platform_class_id) {
                $platformClass = PlatformClass::find($student->platform_class_id);
                if ($platformClass?->academics_class_id) {
                    $stream = DB::connection($conn)->table('streams')
                        ->where('class_id', $platformClass->academics_class_id)
                        ->first();
                    $streamId = $stream?->id;
                }
            }

            // 3. Upsert students row
            $academicsStudent = DB::connection($conn)->table('students')
                ->where('registration_no', $student->student_reg_no)
                ->first();

            $studentPayload = [
                'user_id'         => $userId,
                'registration_no' => $student->student_reg_no,
                'first_name'      => $student->first_name,
                'middle_name'     => $student->middle_name,
                'last_name'       => $student->last_name,
                'gender'          => $student->gender ?? 'Male',
                'date_of_birth'   => $student->date_of_birth,
                'stream_id'       => $streamId,
                'class_id'        => $platformClass->academics_class_id ?? null,
                'updated_at'      => now(),
            ];

            if ($academicsStudent) {
                DB::connection($conn)->table('students')
                    ->where('registration_no', $student->student_reg_no)
                    ->update($studentPayload);
            } else {
                DB::connection($conn)->table('students')
                    ->insert(array_merge($studentPayload, ['created_at' => now()]));
            }

            $student->update(['synced_academics' => true]);
            return true;
        } catch (\Throwable $e) {
            Log::error("TenantSyncService: student→Academics failed for {$student->student_reg_no}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Sync all unsynced students for a school to all enabled systems.
     */
    public function syncAllStudentsForSchool(School $financeSchool): array
    {
        $platformSchool = PlatformSchool::find($financeSchool->platform_school_id);
        if (!$platformSchool) {
            return ['finance' => 0, 'academics' => 0, 'errors' => ['No platform school record found.']];
        }

        $students = PlatformStudent::where('school_id', $platformSchool->id)->get();
        $results  = ['finance' => 0, 'academics' => 0, 'errors' => []];

        $academicsConn = null;
        if ($financeSchool->has_academics && $financeSchool->academics_db_name) {
            $academicsConn = $this->buildAcademicsConnArray($financeSchool);
        }

        foreach ($students as $student) {
            if ($financeSchool->has_finance) {
                if ($this->syncStudentToFinance($student, $financeSchool)) {
                    $results['finance']++;
                }
            }
            if ($academicsConn) {
                if ($this->syncStudentToAcademics($student, $academicsConn)) {
                    $results['academics']++;
                }
            }
        }

        return $results;
    }

    // -------------------------------------------------------------------------
    // HELPERS
    // -------------------------------------------------------------------------

    protected function resolveFinanceClassId(?int $platformClassId): ?int
    {
        if (!$platformClassId) return null;
        $pc = PlatformClass::find($platformClassId);
        return $pc?->finance_class_id;
    }

    protected function buildAcademicsConnArray(School $school): array
    {
        return [
            'host'     => $school->db_host ?? env('DB_HOST', '127.0.0.1'),
            'database' => $school->academics_db_name,
            'username' => $school->db_username ?? env('DB_USERNAME', 'root'),
            'password' => $school->db_password ?? env('DB_PASSWORD', ''),
        ];
    }

    /**
     * Register a temporary DB connection for an Academics tenant and return its key.
     */
    protected function temporaryConnection(string $key, array $params): string
    {
        config([
            "database.connections.{$key}" => [
                'driver'    => 'mysql',
                'host'      => $params['host'] ?? env('DB_HOST', '127.0.0.1'),
                'port'      => $params['port'] ?? env('DB_PORT', '3306'),
                'database'  => $params['database'],
                'username'  => $params['username'] ?? env('DB_USERNAME', 'root'),
                'password'  => $params['password'] ?? env('DB_PASSWORD', ''),
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => '',
                'strict'    => false,
            ],
        ]);

        DB::purge($key);
        return $key;
    }
}
