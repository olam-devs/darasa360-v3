<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\SchoolRole;
use App\Models\Stream;
use App\Helpers\RegistrationHelper;
use App\Models\Classroom;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class StudentsImport implements ToCollection, WithHeadingRow
{
  public $errors = [];
  public $successCount = 0;
  public $failureCount = 0;
  private $schoolId;
  private $tenantDb;

  public function __construct($schoolId, $tenantDb)
  {
    $this->schoolId = $schoolId;
    $this->tenantDb = $tenantDb;
  }

public function collection(Collection $rows)
{
    // Track duplicates within the current file (only for students, not phone/email)
    $processedStudents = [];

    foreach ($rows as $index => $row) {
        $rowNumber = $index + 2; // Account for header row

        try {
            // Extract and trim fields
            $firstName  = trim($row['first_name'] ?? '');
            $middleName = trim($row['middle_name'] ?? '');
            $lastName   = trim($row['last_name'] ?? '');
            $gender     = trim($row['gender'] ?? '');
            $dobRaw     = $row['date_of_birth'] ?? null;
            $phone      = trim($row['phone_number'] ?? '');
            $email      = trim($row['email'] ?? null);
            $className  = trim($row['class'] ?? '');
            $streamName = trim($row['stream'] ?? '');
            $address    = trim($row['address'] ?? '');

            // Required field checks
            $missingFields = [];
            if (!$firstName)  $missingFields[] = 'first_name';
            if (!$lastName)   $missingFields[] = 'last_name';
            if (!$gender)     $missingFields[] = 'gender';
            if (!$dobRaw)     $missingFields[] = 'date_of_birth';
            if (!$phone)      $missingFields[] = 'phone_number';
            if (!$className)  $missingFields[] = 'class';

            if (count($missingFields)) {
                throw new \Exception("Missing required fields: " . implode(', ', $missingFields));
            }

            // Validate gender
            if (!in_array($gender, ['Male', 'Female'])) {
                throw new \Exception("Gender must be Male or Female.");
            }

            // Validate phone - accepts +255XXXXXXXXX, 255XXXXXXXXX, and 0XXXXXXXXX formats
            if (!preg_match('/^\+255\d{9}$/', $phone) && !preg_match('/^255\d{9}$/', $phone) && !preg_match('/^0\d{9}$/', $phone)) {
                throw new \Exception("Phone number must be in format +255XXXXXXXXX, 255XXXXXXXXX, or 0XXXXXXXXX.");
            }

            // Convert 0XXXXXXXXX or 255XXXXXXXXX to +255XXXXXXXXX format
            if (preg_match('/^0(\d{9})$/', $phone, $matches)) {
                $phone = '+255' . $matches[1];
            } elseif (preg_match('/^(255\d{9})$/', $phone, $matches)) {
                $phone = '+' . $matches[1];
            }

            // Parse date_of_birth to Y-m-d format
            $dob = null;
            if ($dobRaw) {
                // Handle Excel date format (numeric)
                if (is_numeric($dobRaw)) {
                    $dob = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dobRaw)->format('Y-m-d');
                } else {
                    // Try converting string date
                    $dobParsed = \Carbon\Carbon::createFromFormat('d/m/Y', $dobRaw)
                        ?? \Carbon\Carbon::parse($dobRaw);
                    $dob = $dobParsed->format('Y-m-d');
                }
            }

            // Get class ID
            $classId = Classroom::where('name', $className)->value('id');
            if (!$classId) throw new \Exception("Class '$className' not found.");

            // Get stream ID if exists
            $streamId = null;
            if ($streamName) {
                $streamId = Stream::where('name', $streamName)
                                  ->where('class_id', $classId)
                                  ->value('id');
                if (!$streamId) throw new \Exception("Stream '$streamName' not found for class '$className'.");
            }

            // ========== DUPLICATE CHECKS (STUDENT ONLY) ==========

            // Check for duplicate students within the current file
            $studentKey = $this->generateStudentKey($firstName, $lastName, $classId, $dob);
            if (in_array($studentKey, $processedStudents)) {
                throw new \Exception("Student '$firstName $lastName' appears multiple times in this import file.");
            }

            // Check for existing student with same identity in database
            $existingStudent = Student::where('first_name', $firstName)
                ->where('last_name', $lastName)
                ->where('class_id', $classId)
                ->where('date_of_birth', $dob)
                ->first();

            if ($existingStudent) {
                $class = Classroom::find($classId);
                throw new \Exception("Student '$firstName $lastName' already exists in class '{$class->name}'.");
            }

            // ========== END DUPLICATE CHECKS ==========

            // Generate registration number
            // Was hardcoded to 7 ("Academic" per RoleSeeder's actual order,
            // despite this comment) - every imported student was misfiled as
            // staff. See the same fix in StudentController::store().
            $roleId = SchoolRole::where('name', 'Student')->value('id') ?? 7;
            $registrationNo = RegistrationHelper::generateRegistrationNo($roleId, $this->schoolId, $this->tenantDb);

            // Use database transaction for data integrity
            DB::connection('tenant')->transaction(function () use (
                $registrationNo, $firstName, $middleName, $lastName, $email, 
                $phone, $roleId, $dob, $gender, $classId, $streamId, $address
            ) {
                // Insert into schoolusers
                $userId = DB::connection('tenant')->table('schoolUsers')->insertGetId([
                    'registration_no' => $registrationNo,
                    'username'        => trim("$firstName $middleName $lastName"),
                    'email'           => $email,
                    'phone_number'    => $phone,
                    'role_id'         => $roleId,
                    'password'        => Hash::make('student'),
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);

                // Insert into students
                Student::create([
                    'user_id'        => $userId,
                    'registration_no'=> $registrationNo,
                    'first_name'     => $firstName,
                    'middle_name'    => $middleName,
                    'last_name'      => $lastName,
                    'gender'         => $gender,
                    'date_of_birth'  => $dob,
                    'phone_number'   => $phone,
                    'email'          => $email,
                    'class_id'       => $classId,
                    'stream_id'      => $streamId,
                    'address'        => $address,
                ]);
            });

            // Track successfully processed students
            $processedStudents[] = $studentKey;
            $this->successCount++;

        } catch (\Exception $e) {
            $this->errors[] = "Row $rowNumber: " . $e->getMessage();
            $this->failureCount++;
        }
    }
}

/**
 * Generate a unique key for student identification
 */
private function generateStudentKey($firstName, $lastName, $classId, $dob)
{
    $nameKey = strtolower(trim($firstName) . '|' . trim($lastName));
    $classKey = $classId;
    $dobKey = $dob ? \Carbon\Carbon::parse($dob)->format('Y-m-d') : 'no-dob';
    
    return "{$nameKey}|{$classKey}|{$dobKey}";
}
}
