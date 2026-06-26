<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

echo "============================================\n";
echo "   DARASA FINANCE - FULL SYSTEM SETUP      \n";
echo "============================================\n\n";

// ============================================
// PART 1: TENANT DATABASE (darasa_finance)
// ============================================
echo "PART 1: SETTING UP TENANT DATABASE (darasa_finance)\n";
echo "====================================================\n\n";

// Clean database
echo "Step 1: Cleaning tenant database...\n";
try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    $tables = ['vouchers', 'particular_student', 'particulars', 'students', 'school_classes',
        'headmasters', 'staff', 'payroll_entries', 'expenses', 'books', 'sms_logs',
        'suspense_accounts', 'bank_transactions', 'academic_years'];
    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            DB::table($table)->truncate();
        }
    }
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    echo "   Done!\n";
} catch (Exception $e) {
    echo '   Warning: '.$e->getMessage()."\n";
}

// School Settings
echo "\nStep 2: Configuring school settings...\n";
try {
    $settings = DB::table('school_settings')->first();
    if ($settings) {
        DB::table('school_settings')->where('id', $settings->id)->update([
            'school_name' => 'Darasa Secondary School',
            'po_box' => 'P.O. Box 12345, Dar es Salaam',
            'region' => 'Dar es Salaam',
            'phone' => '+255 712 345 678',
            'email' => 'info@darasa.ac.tz',
        ]);
    }
    echo "   Done!\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Academic Years
echo "\nStep 3: Creating academic years...\n";
try {
    DB::table('academic_years')->insert([
        ['name' => '2023/2024', 'start_date' => '2023-09-01', 'end_date' => '2024-07-31', 'is_current' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['name' => '2024/2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31', 'is_current' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);
    echo "   Created: 2023/2024, 2024/2025 (current)\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Classes
echo "\nStep 4: Creating 5 classes...\n";
$classes = [
    ['name' => 'Form 1', 'code' => 'F1', 'level' => 1],
    ['name' => 'Form 2', 'code' => 'F2', 'level' => 2],
    ['name' => 'Form 3', 'code' => 'F3', 'level' => 3],
    ['name' => 'Form 4', 'code' => 'F4', 'level' => 4],
    ['name' => 'Form 5', 'code' => 'F5', 'level' => 5],
];
$classIds = [];
$classNames = [];
try {
    foreach ($classes as $idx => $class) {
        $id = DB::table('school_classes')->insertGetId([
            'name' => $class['name'], 'code' => $class['code'], 'level' => $class['level'],
            'capacity' => 50, 'description' => $class['name'].' Class', 'is_active' => true,
            'display_order' => $idx + 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $classIds[$class['name']] = $id;
        $classNames[] = $class['name'];
        echo "   Created: {$class['name']}\n";
    }
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// 50 Students (10 per class)
echo "\nStep 5: Creating 50 students (10 per class)...\n";
$firstNames = ['John', 'Sarah', 'Peter', 'Grace', 'David', 'Mary', 'James', 'Anna', 'Michael', 'Elizabeth',
    'Joseph', 'Rose', 'Daniel', 'Faith', 'Samuel', 'Hope', 'Emmanuel', 'Joy', 'Benjamin', 'Peace',
    'William', 'Mercy', 'Charles', 'Gloria', 'Francis', 'Esther', 'Patrick', 'Ruth', 'George', 'Naomi',
    'Henry', 'Martha', 'Robert', 'Lydia', 'Thomas', 'Deborah', 'Andrew', 'Hannah', 'Stephen', 'Miriam',
    'Paul', 'Priscilla', 'Mark', 'Rebecca', 'Luke', 'Rachel', 'Simon', 'Leah', 'Philip', 'Sarah'];
$lastNames = ['Mwamba', 'Kimaro', 'Mushi', 'Massawe', 'Mrema', 'Shirima', 'Mtui', 'Kessy', 'Mlay', 'Swai',
    'Lyimo', 'Maro', 'Moshi', 'Mwanga', 'Tarimo', 'Minja', 'Urassa', 'Tesha', 'Mollel', 'Kimambo'];

$studentIds = [];
$regNum = 1001;
try {
    foreach ($classes as $class) {
        $classId = $classIds[$class['name']];
        for ($i = 0; $i < 10; $i++) {
            $firstName = $firstNames[array_rand($firstNames)];
            $lastName = $lastNames[array_rand($lastNames)];
            $gender = ($i % 2 == 0) ? 'Male' : 'Female';
            $phone = '+2557'.rand(10, 99).rand(100, 999).rand(100, 999);

            $id = DB::table('students')->insertGetId([
                'name' => "$firstName $lastName",
                'student_reg_no' => 'S'.$regNum,
                'gender' => $gender,
                'class_id' => $classId,
                'class' => $class['name'],
                'phone' => $phone,
                'parent_phone_1' => $phone,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $studentIds[] = $id;
            $regNum++;
        }
        echo "   Created 10 students for {$class['name']}\n";
    }
    echo "   Total: 50 students (S1001-S1050)\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Headmaster
echo "\nStep 6: Creating headmaster...\n";
try {
    DB::table('headmasters')->insert([
        'name' => 'Dr. Joseph Makundi',
        'registration_number' => 'HM001',
        'email' => 'headmaster@darasa.ac.tz',
        'phone' => '+255712000001',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    echo "   Created: Dr. Joseph Makundi (HM001)\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Books
echo "\nStep 7: Creating cash books...\n";
$bookIds = [];
try {
    $bookIds['cash'] = DB::table('books')->insertGetId([
        'name' => 'Main Cash Book', 'opening_balance' => 500000, 'is_cash_book' => true, 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $bookIds['bank'] = DB::table('books')->insertGetId([
        'name' => 'NMB Bank Account', 'bank_account_number' => '40712000001', 'opening_balance' => 2000000,
        'is_cash_book' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    echo "   Created: Main Cash Book, NMB Bank Account\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Fee Particulars
echo "\nStep 8: Creating fee particulars...\n";
$particulars = [
    ['name' => 'Tuition Fee', 'amount' => 500000],
    ['name' => 'Examination Fee', 'amount' => 50000],
    ['name' => 'Computer Lab Fee', 'amount' => 30000],
    ['name' => 'Library Fee', 'amount' => 20000],
    ['name' => 'Sports Fee', 'amount' => 15000],
];
$particularIds = [];
$particularAmounts = [];
try {
    $bookId = $bookIds['cash'];
    foreach ($particulars as $p) {
        $id = DB::table('particulars')->insertGetId([
            'name' => $p['name'],
            'book_ids' => json_encode([$bookId]),
            'class_names' => json_encode($classNames),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $particularIds[$p['name']] = $id;
        $particularAmounts[$id] = $p['amount'];
        echo "   Created: {$p['name']} - TZS ".number_format($p['amount'])."\n";
    }
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Assign fees to ALL students
echo "\nStep 9: Assigning fees to 50 students...\n";
try {
    $academicYear = DB::table('academic_years')->where('is_current', true)->first();
    $deadline = date('Y-m-d', strtotime('+30 days'));
    $count = 0;

    foreach ($studentIds as $studentId) {
        foreach ($particularIds as $name => $particularId) {
            $amount = $particularAmounts[$particularId];
            DB::table('particular_student')->insert([
                'particular_id' => $particularId,
                'student_id' => $studentId,
                'academic_year_id' => $academicYear->id,
                'sales' => $amount,
                'credit' => 0,
                'deadline' => $deadline,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $count++;
    }
    $totalPerStudent = array_sum($particularAmounts);
    echo "   Assigned 5 fee types to $count students\n";
    echo '   Total fees per student: TZS '.number_format($totalPerStudent)."\n";
    echo '   Grand total fees: TZS '.number_format($totalPerStudent * $count)."\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Payments
echo "\nStep 10: Creating sample payments...\n";
try {
    $bookId = $bookIds['cash'];
    $user = DB::table('users')->where('email', 'accountant@darasa360.com')->first();
    $userId = $user->id ?? 1;
    $paymentCount = 0;
    $totalPayments = 0;

    // Create payments for about 30 students (varied amounts)
    $studentsWithPayments = array_slice($studentIds, 0, 30);
    foreach ($studentsWithPayments as $idx => $studentId) {
        $student = DB::table('students')->find($studentId);
        $paymentAmount = [200000, 300000, 400000, 500000, 615000][rand(0, 4)];
        $paymentDate = date('Y-m-d', strtotime('-'.rand(1, 30).' days'));

        $feeAssignment = DB::table('particular_student')->where('student_id', $studentId)->first();
        $voucherRef = 'VCH'.str_pad($idx + 1, 6, '0', STR_PAD_LEFT);

        DB::table('vouchers')->insert([
            'date' => $paymentDate,
            'student_id' => $studentId,
            'particular_id' => $feeAssignment->particular_id,
            'book_id' => $bookId,
            'voucher_type' => 'receipt',
            'voucher_no' => $voucherRef,
            'voucher_number' => $voucherRef,
            'debit' => 0,
            'credit' => $paymentAmount,
            'payment_by_receipt_to' => $student->name,
            'notes' => 'Fee payment',
            'created_by' => $userId,
            'created_at' => $paymentDate,
            'updated_at' => $paymentDate,
        ]);

        // Apply to fee assignments
        $remaining = $paymentAmount;
        $fees = DB::table('particular_student')->where('student_id', $studentId)->whereRaw('sales > credit')->get();
        foreach ($fees as $fee) {
            if ($remaining <= 0) {
                break;
            }
            $outstanding = $fee->sales - $fee->credit;
            $apply = min($remaining, $outstanding);
            DB::table('particular_student')->where('id', $fee->id)->increment('credit', $apply);
            $remaining -= $apply;
        }

        $paymentCount++;
        $totalPayments += $paymentAmount;
    }
    echo "   Created $paymentCount payments\n";
    echo '   Total payments: TZS '.number_format($totalPayments)."\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Expenses
echo "\nStep 11: Creating expenses...\n";
try {
    $bookId = $bookIds['cash'];
    $expenses = [
        ['name' => 'Electricity Bill - January', 'amount' => 150000],
        ['name' => 'Water Bill - January', 'amount' => 45000],
        ['name' => 'Office Supplies', 'amount' => 75000],
        ['name' => 'Staff Transport Allowance', 'amount' => 200000],
        ['name' => 'Classroom Maintenance', 'amount' => 350000],
        ['name' => 'Internet Subscription', 'amount' => 100000],
        ['name' => 'Security Services', 'amount' => 180000],
        ['name' => 'Cleaning Supplies', 'amount' => 60000],
    ];
    $totalExpenses = 0;
    foreach ($expenses as $expense) {
        $expenseNumber = 'EXP'.str_pad((string) rand(1, 999999), 6, '0', STR_PAD_LEFT);
        DB::table('expenses')->insert([
            'expense_number' => $expenseNumber,
            'expense_name' => $expense['name'],
            'category' => 'General',
            'transaction_date' => date('Y-m-d', strtotime('-'.rand(1, 25).' days')),
            'book_id' => $bookId,
            'amount' => $expense['amount'],
            'description' => $expense['name'],
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $totalExpenses += $expense['amount'];
    }
    echo '   Created '.count($expenses)." expenses\n";
    echo '   Total expenses: TZS '.number_format($totalExpenses)."\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Staff
echo "\nStep 12: Creating staff members...\n";
try {
    $staffMembers = [
        ['name' => 'Mr. Robert Kiwelu', 'position' => 'Senior Teacher', 'salary' => 800000, 'dept' => 'Teaching'],
        ['name' => 'Mrs. Agnes Mwangi', 'position' => 'Teacher', 'salary' => 600000, 'dept' => 'Teaching'],
        ['name' => 'Mr. Paul Nyamwezi', 'position' => 'Teacher', 'salary' => 600000, 'dept' => 'Teaching'],
        ['name' => 'Ms. Fatuma Hassan', 'position' => 'Teacher', 'salary' => 600000, 'dept' => 'Teaching'],
        ['name' => 'Mr. John Kimaro', 'position' => 'Lab Technician', 'salary' => 450000, 'dept' => 'Science'],
        ['name' => 'Mrs. Grace Tarimo', 'position' => 'Librarian', 'salary' => 400000, 'dept' => 'Library'],
        ['name' => 'Ms. Neema Moshi', 'position' => 'Secretary', 'salary' => 400000, 'dept' => 'Admin'],
        ['name' => 'Mr. Juma Bakari', 'position' => 'Security Guard', 'salary' => 300000, 'dept' => 'Support'],
        ['name' => 'Mrs. Mariam Ali', 'position' => 'Cleaner', 'salary' => 250000, 'dept' => 'Support'],
        ['name' => 'Mr. Hassan Omari', 'position' => 'Driver', 'salary' => 350000, 'dept' => 'Support'],
    ];
    foreach ($staffMembers as $idx => $staff) {
        DB::table('staff')->insert([
            'name' => $staff['name'],
            'employee_id' => 'STF'.str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
            'position' => $staff['position'],
            'department' => $staff['dept'],
            'phone' => '+2557'.rand(10, 99).rand(100, 999).rand(100, 999),
            'email' => strtolower(str_replace([' ', '.', 'Mr', 'Mrs', 'Ms'], '', $staff['name'])).'@darasa.ac.tz',
            'basic_salary' => $staff['salary'],
            'bank_name' => 'NMB Bank',
            'bank_account' => '4071200'.str_pad($idx + 1, 4, '0', STR_PAD_LEFT),
            'hire_date' => date('Y-m-d', strtotime('-'.rand(100, 1000).' days')),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    echo '   Created '.count($staffMembers)." staff members\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// SMS Logs
echo "\nStep 13: Creating SMS logs...\n";
try {
    $user = DB::table('users')->where('email', 'accountant@darasa360.com')->first();
    $userId = $user->id ?? 1;

    for ($i = 0; $i < 15; $i++) {
        $student = DB::table('students')->inRandomOrder()->first();
        DB::table('sms_logs')->insert([
            'student_id' => $student->id,
            'sent_by' => $userId,
            'recipient_phone' => $student->phone,
            'message' => 'Dear Parent, your child '.$student->name.' has an outstanding fee balance. Please make payment at your earliest convenience.',
            'status' => ['sent', 'delivered', 'failed'][rand(0, 2)],
            'sms_count' => 1,
            'sent_at' => now()->subDays(rand(1, 20)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    echo "   Created 15 SMS logs\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Accountant User
echo "\nStep 14: Setting up accountant user...\n";
try {
    DB::table('users')->where('email', 'accountant@darasa360.com')->update([
        'password' => Hash::make('password'),
        'role' => 'accountant',
        'name' => 'School Accountant',
    ]);
    echo "   Updated: accountant@darasa360.com / password\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// ============================================
// PART 2: CENTRAL DATABASE (darasa_central)
// ============================================
echo "\n\nPART 2: SETTING UP CENTRAL DATABASE (darasa_central)\n";
echo "=====================================================\n\n";

echo "Step 15: Setting up SuperAdmin in central database...\n";
try {
    // Check if admin exists
    $admin = DB::connection('central')->table('super_admins')
        ->where('email', 'admin@darasa360.com')
        ->first();

    if ($admin) {
        DB::connection('central')->table('super_admins')
            ->where('email', 'admin@darasa360.com')
            ->update([
                'password' => Hash::make('password'),
                'is_active' => true,
                'updated_at' => now(),
            ]);
        echo "   Updated existing SuperAdmin\n";
    } else {
        DB::connection('central')->table('super_admins')->insert([
            'name' => 'Super Admin',
            'email' => 'admin@darasa360.com',
            'password' => Hash::make('password'),
            'master_password' => Hash::make('master123'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   Created new SuperAdmin\n";
    }

    // Update all other superadmins too
    DB::connection('central')->table('super_admins')
        ->update(['password' => Hash::make('password'), 'is_active' => true]);

    echo "   All SuperAdmins in central DB:\n";
    $admins = DB::connection('central')->table('super_admins')->get();
    foreach ($admins as $a) {
        echo "     - {$a->email}\n";
    }
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Register this school in central DB
echo "\nStep 16: Registering school in central database...\n";
try {
    $schoolExists = DB::connection('central')->table('schools')
        ->where('database_name', 'darasa_finance')
        ->first();

    if (! $schoolExists) {
        DB::connection('central')->table('schools')->insert([
            'name' => 'Darasa Secondary School',
            'code' => 'DSS001',
            'database_name' => 'darasa_finance',
            'domain' => 'darasa.localhost',
            'email' => 'info@darasa.ac.tz',
            'phone' => '+255712345678',
            'address' => 'Dar es Salaam, Tanzania',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   Registered: Darasa Secondary School (database: darasa_finance)\n";
    } else {
        echo "   School already registered in central DB\n";
    }
} catch (Exception $e) {
    echo '   Note: '.$e->getMessage()."\n";
}

// ============================================
// SUMMARY
// ============================================
$totalStudents = count($studentIds);
$totalFeesPerStudent = array_sum($particularAmounts);
$grandTotalFees = $totalStudents * $totalFeesPerStudent;

echo "\n============================================\n";
echo "           SETUP COMPLETE!                  \n";
echo "============================================\n\n";

echo "TENANT DATABASE (darasa_finance):\n";
echo "  - 5 Classes: Form 1-5\n";
echo "  - 50 Students: S1001-S1050 (10 per class)\n";
echo '  - 5 Fee Types totaling TZS '.number_format($totalFeesPerStudent)."/student\n";
echo '  - Grand Total Fees: TZS '.number_format($grandTotalFees)."\n";
echo "  - 30 Payments made\n";
echo "  - 8 Expenses recorded\n";
echo "  - 10 Staff members\n";
echo "  - 1 Headmaster (HM001)\n";
echo "  - 15 SMS logs\n\n";

echo "CENTRAL DATABASE (darasa_central):\n";
echo "  - SuperAdmin accounts configured\n";
echo "  - School registered\n\n";

echo "LOGIN CREDENTIALS:\n";
echo "==================\n";
echo "ACCOUNTANT: http://127.0.0.1:8000/login\n";
echo "  Email: accountant@darasa360.com\n";
echo "  Password: password\n\n";

echo "SUPER ADMIN: http://127.0.0.1:8000/superadmin/login\n";
echo "  Email: admin@darasa360.com\n";
echo "  Password: password\n";
echo "  (Uses central database: darasa_central)\n\n";

echo "HEADMASTER: http://127.0.0.1:8000/headmaster/login\n";
echo "  Registration: HM001\n\n";

echo "PARENT PORTAL: http://127.0.0.1:8000/parent/login\n";
echo "  Students: S1001 to S1050\n\n";

echo "============================================\n";
