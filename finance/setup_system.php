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
echo "   DARASA FINANCE - COMPLETE SYSTEM SETUP  \n";
echo "============================================\n\n";

// Step 1: Clean database
echo "STEP 1: Cleaning database...\n";
try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');
    $tables = ['vouchers', 'particular_student', 'particulars', 'students', 'school_classes',
        'headmasters', 'staff', 'payroll_entries', 'expenses', 'books', 'sms_logs',
        'suspense_accounts', 'bank_transactions', 'academic_years'];
    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            DB::table($table)->truncate();
            echo "   Cleaned: $table\n";
        }
    }
    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    echo "   Database cleaned!\n";
} catch (Exception $e) {
    echo '   Warning: '.$e->getMessage()."\n";
}

// Step 2: Update School Settings
echo "\nSTEP 2: Updating school settings...\n";
try {
    $settings = DB::table('school_settings')->first();
    if ($settings) {
        DB::table('school_settings')->where('id', $settings->id)->update([
            'school_name' => 'Darasa Secondary School',
            'po_box' => 'P.O. Box 12345',
            'region' => 'Dar es Salaam',
            'phone' => '+255 712 345 678',
            'email' => 'info@darasa.ac.tz',
        ]);
    }
    echo "   School settings configured!\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Step 3: Create Academic Years
echo "\nSTEP 3: Creating academic years...\n";
try {
    DB::table('academic_years')->insert([
        ['name' => '2023/2024', 'start_date' => '2023-09-01', 'end_date' => '2024-07-31', 'is_current' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ['name' => '2024/2025', 'start_date' => '2024-09-01', 'end_date' => '2025-07-31', 'is_current' => true, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
    ]);
    echo "   Academic years created!\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Step 4: Create Classes
echo "\nSTEP 4: Creating 5 classes...\n";
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
            'capacity' => 40, 'description' => $class['name'].' Class', 'is_active' => true,
            'display_order' => $idx + 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $classIds[$class['name']] = $id;
        $classNames[] = $class['name'];
        echo "   Created: {$class['name']}\n";
    }
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Step 5: Create Students
echo "\nSTEP 5: Creating 10 students...\n";
$students = [
    ['name' => 'John Mwamba', 'gender' => 'Male', 'class' => 'Form 1', 'phone' => '+255711111001'],
    ['name' => 'Sarah Kimaro', 'gender' => 'Female', 'class' => 'Form 1', 'phone' => '+255711111002'],
    ['name' => 'Peter Mushi', 'gender' => 'Male', 'class' => 'Form 2', 'phone' => '+255711111003'],
    ['name' => 'Grace Massawe', 'gender' => 'Female', 'class' => 'Form 2', 'phone' => '+255711111004'],
    ['name' => 'David Mrema', 'gender' => 'Male', 'class' => 'Form 3', 'phone' => '+255711111005'],
    ['name' => 'Mary Shirima', 'gender' => 'Female', 'class' => 'Form 3', 'phone' => '+255711111006'],
    ['name' => 'James Mtui', 'gender' => 'Male', 'class' => 'Form 4', 'phone' => '+255711111007'],
    ['name' => 'Anna Kessy', 'gender' => 'Female', 'class' => 'Form 4', 'phone' => '+255711111008'],
    ['name' => 'Michael Mlay', 'gender' => 'Male', 'class' => 'Form 5', 'phone' => '+255711111009'],
    ['name' => 'Elizabeth Swai', 'gender' => 'Female', 'class' => 'Form 5', 'phone' => '+255711111010'],
];
$studentIds = [];
try {
    $regNum = 1001;
    foreach ($students as $student) {
        $classId = $classIds[$student['class']] ?? null;
        $id = DB::table('students')->insertGetId([
            'name' => $student['name'], 'student_reg_no' => 'S'.$regNum, 'gender' => $student['gender'],
            'class_id' => $classId, 'class' => $student['class'], 'phone' => $student['phone'],
            'parent_phone_1' => $student['phone'], 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $studentIds[] = $id;
        echo "   Created: {$student['name']} (S{$regNum}) - {$student['class']}\n";
        $regNum++;
    }
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Step 6: Create Headmaster
echo "\nSTEP 6: Creating headmaster account...\n";
try {
    DB::table('headmasters')->insert([
        'name' => 'Dr. Joseph Makundi', 'registration_number' => 'HM001',
        'email' => 'headmaster@darasa.ac.tz', 'phone' => '+255712000001', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    echo "   Headmaster created: HM001\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Step 7: Create Books
echo "\nSTEP 7: Creating cash books...\n";
$bookIds = [];
try {
    $bookIds['cash'] = DB::table('books')->insertGetId([
        'name' => 'Main Cash Book', 'opening_balance' => 0, 'is_cash_book' => true, 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $bookIds['bank'] = DB::table('books')->insertGetId([
        'name' => 'NMB Bank Account', 'bank_account_number' => '12345678901', 'opening_balance' => 0,
        'is_cash_book' => false, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    echo "   Cash books created!\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Step 8: Create Fee Particulars (with JSON class_names)
echo "\nSTEP 8: Creating fee particulars...\n";
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
    $bookId = $bookIds['cash'] ?? 1;
    foreach ($particulars as $p) {
        $id = DB::table('particulars')->insertGetId([
            'name' => $p['name'],
            'book_ids' => json_encode([$bookId]),
            'class_names' => json_encode($classNames), // JSON array of class names
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

// Step 9: Assign fees to students
echo "\nSTEP 9: Assigning fees to students...\n";
try {
    $academicYear = DB::table('academic_years')->where('is_current', true)->first();
    $deadline = date('Y-m-d', strtotime('+30 days'));
    foreach ($studentIds as $studentId) {
        foreach ($particularIds as $name => $particularId) {
            $amount = $particularAmounts[$particularId] ?? 100000;
            DB::table('particular_student')->insert([
                'particular_id' => $particularId, 'student_id' => $studentId,
                'academic_year_id' => $academicYear->id ?? 1, 'sales' => $amount, 'credit' => 0,
                'deadline' => $deadline, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }
    $totalPerStudent = array_sum($particularAmounts);
    echo "   Fees assigned to all 10 students!\n";
    echo '   Total fees per student: TZS '.number_format($totalPerStudent)."\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Step 10: Add sample payments (vouchers with correct columns)
echo "\nSTEP 10: Adding sample payments...\n";
try {
    $bookId = $bookIds['cash'] ?? 1;
    $paymentData = [
        ['idx' => 0, 'amount' => 300000, 'days' => 20],
        ['idx' => 1, 'amount' => 500000, 'days' => 18],
        ['idx' => 2, 'amount' => 250000, 'days' => 15],
        ['idx' => 3, 'amount' => 400000, 'days' => 12],
        ['idx' => 4, 'amount' => 615000, 'days' => 10],
        ['idx' => 5, 'amount' => 200000, 'days' => 8],
        ['idx' => 6, 'amount' => 350000, 'days' => 5],
        ['idx' => 7, 'amount' => 615000, 'days' => 3],
        ['idx' => 8, 'amount' => 450000, 'days' => 1],
        ['idx' => 9, 'amount' => 300000, 'days' => 0],
    ];

    foreach ($paymentData as $payment) {
        if (! isset($studentIds[$payment['idx']])) {
            continue;
        }
        $studentId = $studentIds[$payment['idx']];
        $student = DB::table('students')->find($studentId);
        $paymentDate = date('Y-m-d', strtotime("-{$payment['days']} days"));

        // Get first fee assignment for this student
        $feeAssignment = DB::table('particular_student')
            ->where('student_id', $studentId)
            ->first();

        // Create voucher with correct columns
        $voucherRef = 'VCH'.str_pad((string) rand(1000, 9999), 6, '0', STR_PAD_LEFT);
        DB::table('vouchers')->insert([
            'date' => $paymentDate,
            'student_id' => $studentId,
            'particular_id' => $feeAssignment->particular_id ?? 1,
            'book_id' => $bookId,
            'voucher_type' => 'receipt',
            'voucher_no' => $voucherRef,
            'voucher_number' => $voucherRef,
            'debit' => 0,
            'credit' => $payment['amount'],
            'payment_by_receipt_to' => $student->name,
            'notes' => 'Fee payment',
            'created_at' => $paymentDate,
            'updated_at' => $paymentDate,
        ]);

        // Apply payment to student fees
        $remainingAmount = $payment['amount'];
        $feeAssignments = DB::table('particular_student')
            ->where('student_id', $studentId)
            ->whereRaw('sales > credit')
            ->get();

        foreach ($feeAssignments as $fee) {
            if ($remainingAmount <= 0) {
                break;
            }
            $outstanding = $fee->sales - $fee->credit;
            $paymentAmount = min($remainingAmount, $outstanding);
            DB::table('particular_student')->where('id', $fee->id)->increment('credit', $paymentAmount);
            $remainingAmount -= $paymentAmount;
        }

        echo '   Payment: TZS '.number_format($payment['amount'])." by {$student->name}\n";
    }
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Step 11: Add sample expenses (with correct columns)
echo "\nSTEP 11: Adding sample expenses...\n";
try {
    $bookId = $bookIds['cash'] ?? 1;
    $expenses = [
        ['name' => 'Electricity Bill - January', 'amount' => 150000],
        ['name' => 'Office Supplies', 'amount' => 75000],
        ['name' => 'Staff Transport', 'amount' => 200000],
        ['name' => 'Maintenance - Classroom Repair', 'amount' => 350000],
        ['name' => 'Internet Subscription', 'amount' => 100000],
    ];
    foreach ($expenses as $expense) {
        $expenseNumber = 'EXP'.str_pad((string) rand(1, 999999), 6, '0', STR_PAD_LEFT);
        DB::table('expenses')->insert([
            'expense_number' => $expenseNumber,
            'expense_name' => $expense['name'],
            'category' => 'General',
            'transaction_date' => date('Y-m-d', strtotime('-'.rand(1, 20).' days')),
            'book_id' => $bookId,
            'amount' => $expense['amount'],
            'description' => $expense['name'],
            'status' => 'approved',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   Expense: {$expense['name']} - TZS ".number_format($expense['amount'])."\n";
    }
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Step 12: Add Staff
echo "\nSTEP 12: Adding staff members...\n";
try {
    $staffMembers = [
        ['name' => 'Mr. Robert Kiwelu', 'position' => 'Senior Teacher', 'salary' => 800000, 'dept' => 'Teaching'],
        ['name' => 'Mrs. Agnes Mwangi', 'position' => 'Teacher', 'salary' => 600000, 'dept' => 'Teaching'],
        ['name' => 'Mr. Paul Nyamwezi', 'position' => 'Lab Technician', 'salary' => 450000, 'dept' => 'Science'],
        ['name' => 'Ms. Fatuma Hassan', 'position' => 'Secretary', 'salary' => 400000, 'dept' => 'Admin'],
        ['name' => 'Mr. Juma Bakari', 'position' => 'Security', 'salary' => 300000, 'dept' => 'Support'],
    ];
    foreach ($staffMembers as $idx => $staff) {
        DB::table('staff')->insert([
            'name' => $staff['name'], 'employee_id' => 'STF'.str_pad($idx + 1, 3, '0', STR_PAD_LEFT),
            'position' => $staff['position'], 'department' => $staff['dept'], 'basic_salary' => $staff['salary'],
            'phone' => '+2557'.rand(10, 99).rand(100, 999).rand(100, 999),
            'email' => strtolower(str_replace([' ', '.', 'Mr', 'Mrs', 'Ms'], '', $staff['name'])).'@darasa.ac.tz',
            'bank_name' => 'NMB Bank', 'bank_account' => '123456789'.$idx,
            'hire_date' => date('Y-m-d', strtotime('-'.rand(30, 365).' days')), 'status' => 'active',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        echo "   Staff: {$staff['name']} - TZS ".number_format($staff['salary'])."/month\n";
    }
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Step 13: Add SMS Logs (with correct columns)
echo "\nSTEP 13: Adding sample SMS logs...\n";
try {
    foreach ($studentIds as $idx => $studentId) {
        if ($idx >= 5) {
            break;
        } // Only 5 SMS logs
        $student = DB::table('students')->find($studentId);
        DB::table('sms_logs')->insert([
            'student_id' => $studentId,
            'sent_by' => null,
            'recipient_phone' => $student->phone,
            'message' => 'Fee reminder: Please pay your outstanding balance. Thank you.',
            'status' => ['sent', 'delivered', 'pending'][rand(0, 2)],
            'sms_count' => 1,
            'sent_at' => now()->subDays(rand(1, 10)),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
    echo "   Added 5 sample SMS logs\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Step 14: Setup Users
echo "\nSTEP 14: Setting up users...\n";
try {
    DB::table('users')->where('email', 'accountant@darasa360.com')->update([
        'password' => Hash::make('password'), 'role' => 'accountant', 'name' => 'School Accountant',
    ]);
    echo "   Accountant: accountant@darasa360.com / password\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

// Step 15: Setup SuperAdmin
echo "\nSTEP 15: Setting up SuperAdmin...\n";
try {
    $admin = DB::connection('central')->table('super_admins')->where('email', 'admin@darasa360.com')->first();
    if ($admin) {
        DB::connection('central')->table('super_admins')->where('email', 'admin@darasa360.com')
            ->update(['password' => Hash::make('password'), 'is_active' => true]);
    } else {
        DB::connection('central')->table('super_admins')->insert([
            'name' => 'Super Admin', 'email' => 'admin@darasa360.com',
            'password' => Hash::make('password'), 'master_password' => Hash::make('master123'),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
    DB::connection('central')->table('super_admins')->where('email', '!=', 'admin@darasa360.com')
        ->update(['password' => Hash::make('password'), 'is_active' => true]);

    $allAdmins = DB::connection('central')->table('super_admins')->get();
    echo "   SuperAdmins available:\n";
    foreach ($allAdmins as $a) {
        echo "     - {$a->email} / password\n";
    }
} catch (Exception $e) {
    echo '   Note: '.$e->getMessage()."\n";
}

// Summary
$totalFees = count($studentIds) * array_sum($particularAmounts);
$totalPayments = array_sum(array_column($paymentData, 'amount'));

echo "\n============================================\n";
echo "           SETUP COMPLETE!                  \n";
echo "============================================\n\n";

echo "FINANCIAL SUMMARY:\n";
echo '  Total Fees: TZS '.number_format($totalFees)."\n";
echo '  Total Payments: TZS '.number_format($totalPayments)."\n";
echo '  Outstanding: TZS '.number_format($totalFees - $totalPayments)."\n\n";

echo "LOGIN CREDENTIALS:\n";
echo "==================\n";
echo "ACCOUNTANT: http://127.0.0.1:8000/login\n";
echo "  Email: accountant@darasa360.com | Password: password\n\n";
echo "SUPER ADMIN: http://127.0.0.1:8000/superadmin/login\n";
echo "  Email: admin@darasa360.com | Password: password\n\n";
echo "HEADMASTER: http://127.0.0.1:8000/headmaster/login\n";
echo "  Registration: HM001\n\n";
echo "PARENT PORTAL: http://127.0.0.1:8000/parent/login\n";
echo "  Students: S1001-S1010\n\n";

echo "============================================\n";
