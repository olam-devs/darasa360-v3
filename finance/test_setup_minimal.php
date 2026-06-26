<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "============================================\n";
echo "   DARASA FINANCE - MINIMAL TEST SETUP      \n";
echo "============================================\n\n";

// Tenant DB only (this script is for controlled local testing data)
echo "STEP 1: Cleaning tenant database tables...\n";
try {
    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    // Order matters due to FKs.
    $tables = [
        'book_transactions',
        'bank_transactions',
        'bank_accounts',
        'bank_api_settings',
        'suspense_accounts',
        'expenses',
        'vouchers',
        'sms_templates',
        'sms_logs',
        'payroll_entries',
        'staff',
        'headmasters',
        'scholarship_student',
        'scholarships',
        'particular_student',
        'particulars',
        'students',
        'school_classes',
        'academic_years',
        'books',
        'school_settings',
    ];

    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            DB::table($table)->truncate();
            echo "  - Cleaned: {$table}\n";
        }
    }

    DB::statement('SET FOREIGN_KEY_CHECKS=1');
    echo "   Done.\n";
} catch (Exception $e) {
    echo '   Warning: '.$e->getMessage()."\n";
}

echo "\nSTEP 2: Ensure school settings exist...\n";
try {
    if (Schema::hasTable('school_settings')) {
        $existing = DB::table('school_settings')->first();
        if (! $existing) {
            DB::table('school_settings')->insert([
                'school_name' => 'Darasa Secondary School (Test)',
                'po_box' => null,
                'region' => null,
                'phone' => null,
                'email' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   Created school settings.\n";
        } else {
            DB::table('school_settings')->where('id', $existing->id)->update([
                'school_name' => 'Darasa Secondary School (Test)',
                'updated_at' => now(),
            ]);
            echo "   Updated school settings.\n";
        }
    }
} catch (Exception $e) {
    echo '   Warning: '.$e->getMessage()."\n";
}

echo "\nSTEP 3: Create academic years (set current)...\n";
$currentAcademicYearId = null;
try {
    $years = [
        [
            'name' => '2025/2026',
            'start_date' => '2025-09-01',
            'end_date' => '2026-07-31',
            'is_current' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ],
    ];

    // Some schemas include is_active.
    if (Schema::hasColumn('academic_years', 'is_active')) {
        $years[0]['is_active'] = true;
    }

    DB::table('academic_years')->insert($years);
    $currentAcademicYearId = DB::table('academic_years')->where('is_current', true)->value('id');
    echo "   Current academic year id: {$currentAcademicYearId}\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

echo "\nSTEP 4: Create 3 classes...\n";
$classes = [
    ['name' => 'Form 1'],
    ['name' => 'Form 2'],
    ['name' => 'Form 3'],
];
$classIds = [];
try {
    foreach ($classes as $idx => $class) {
        $row = [
            'name' => $class['name'],
            'description' => $class['name'].' Class',
            'is_active' => true,
            'display_order' => $idx + 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('school_classes', 'code')) {
            $row['code'] = 'F'.($idx + 1);
        }
        if (Schema::hasColumn('school_classes', 'level')) {
            $row['level'] = $idx + 1;
        }
        if (Schema::hasColumn('school_classes', 'capacity')) {
            $row['capacity'] = 40;
        }

        $id = DB::table('school_classes')->insertGetId($row);
        $classIds[$class['name']] = $id;
        echo "  - Created: {$class['name']} (id {$id})\n";
    }
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

echo "\nSTEP 5: Create 5 students per class (15 total)...\n";
try {
    $firstNames = ['Asha', 'Neema', 'John', 'Sarah', 'Peter', 'Mary', 'Grace', 'David', 'Paul', 'Anna'];
    $lastNames = ['Mwamba', 'Kimaro', 'Mushi', 'Massawe', 'Mrema', 'Shirima', 'Mtui', 'Kessy', 'Mlay', 'Swai'];

    $regNum = 1001;
    foreach ($classes as $class) {
        $className = $class['name'];
        $classId = $classIds[$className] ?? null;

        for ($i = 0; $i < 5; $i++) {
            $first = $firstNames[array_rand($firstNames)];
            $last = $lastNames[array_rand($lastNames)];
            $gender = ($i % 2 === 0) ? 'Male' : 'Female';
            $phone = '+2557'.rand(10, 99).rand(100, 999).rand(100, 999);

            DB::table('students')->insert([
                'name' => "{$first} {$last}",
                'student_reg_no' => 'S'.$regNum,
                'class_id' => $classId,
                'class' => $className,
                'phone' => $phone,
                'parent_phone_1' => $phone,
                'gender' => $gender,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $regNum++;
        }
        echo "  - Created 5 students for {$className}\n";
    }
    echo '   Students created: S1001 to S'.($regNum - 1)."\n";
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

echo "\nSTEP 6: Create 2 books...\n";
$cashBookId = null;
try {
    $cashBookId = DB::table('books')->insertGetId([
        'name' => 'Main Cash Book',
        'type' => 'cash',
        'opening_balance' => 0,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $bankRow = [
        'name' => 'NMB Bank Account',
        'type' => 'bank',
        'opening_balance' => 0,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ];
    if (Schema::hasColumn('books', 'bank_account_number')) {
        $bankRow['bank_account_number'] = '12345678901';
    }
    DB::table('books')->insert($bankRow);

    // If schema supports is_cash_book, set it (older migrations add it later).
    if (Schema::hasColumn('books', 'is_cash_book')) {
        DB::table('books')->where('id', $cashBookId)->update(['is_cash_book' => true]);
        DB::table('books')->where('name', 'NMB Bank Account')->update(['is_cash_book' => false]);
    }

    echo "   Created books.\n";
} catch (Exception $e) {
    echo '   Warning: '.$e->getMessage()."\n";
}

echo "\nSTEP 7: Create a few fee particulars...\n";
try {
    $particulars = [
        ['name' => 'Tuition Fee', 'default_amount' => 200000],
        ['name' => 'Examination Fee', 'default_amount' => 20000],
        ['name' => 'Transport Fee', 'default_amount' => 50000],
    ];

    $classNames = array_map(fn ($c) => $c['name'], $classes);

    foreach ($particulars as $p) {
        $row = [
            'name' => $p['name'],
            'description' => null,
            'default_amount' => $p['default_amount'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('particulars', 'book_ids')) {
            $row['book_ids'] = json_encode([$cashBookId]);
        }
        if (Schema::hasColumn('particulars', 'class_names')) {
            $row['class_names'] = json_encode($classNames);
        }

        DB::table('particulars')->insert($row);
        echo "  - Created: {$p['name']}\n";
    }
} catch (Exception $e) {
    echo '   Error: '.$e->getMessage()."\n";
}

echo "\nDONE.\n";
echo "You can now test using 3 classes (Form 1-3) and 15 students total.\n";
