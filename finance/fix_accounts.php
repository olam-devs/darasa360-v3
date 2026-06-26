<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

echo "=== Darasa Finance Account Setup ===\n\n";

// Fix school_settings
echo "1. Fixing school_settings...\n";
try {
    $count = DB::table('school_settings')->count();
    if ($count == 0) {
        DB::table('school_settings')->insert([
            'school_name' => 'Darasa Secondary School',
            'po_box' => 'P.O. Box 12345',
            'region' => 'Dar es Salaam',
            'phone' => '+255 123 456 789',
            'email' => 'info@darasa.ac.tz',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   Default school settings inserted.\n";
    } else {
        echo "   School settings already exist.\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// Reset Accountant User
echo "\n2. Setting up Accountant...\n";
try {
    $user = DB::table('users')->where('email', 'accountant@darasa.com')->first();
    if ($user) {
        DB::table('users')->where('email', 'accountant@darasa.com')->update([
            'password' => Hash::make('password123'),
            'role' => 'accountant',
            'updated_at' => now(),
        ]);
        echo "   Password reset for: accountant@darasa.com\n";
    } else {
        DB::table('users')->insert([
            'name' => 'School Accountant',
            'email' => 'accountant@darasa.com',
            'password' => Hash::make('password123'),
            'role' => 'accountant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   Created: accountant@darasa.com\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// Setup SuperAdmin using central connection
echo "\n3. Setting up Super Admin (central database)...\n";
try {
    // Check if super_admins table exists in central DB
    $tableExists = Schema::connection('central')->hasTable('super_admins');

    if ($tableExists) {
        $admin = DB::connection('central')->table('super_admins')->where('email', 'admin@darasa.com')->first();
        if ($admin) {
            DB::connection('central')->table('super_admins')->where('email', 'admin@darasa.com')->update([
                'password' => Hash::make('password123'),
                'is_active' => true,
                'updated_at' => now(),
            ]);
            echo "   Password reset for: admin@darasa.com\n";
        } else {
            DB::connection('central')->table('super_admins')->insert([
                'name' => 'Super Admin',
                'email' => 'admin@darasa.com',
                'password' => Hash::make('password123'),
                'master_password' => Hash::make('master123'),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   Created: admin@darasa.com\n";
        }
    } else {
        echo "   super_admins table does not exist in central DB.\n";
        echo "   Creating table...\n";
        Schema::connection('central')->create('super_admins', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('master_password')->nullable();
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
        DB::connection('central')->table('super_admins')->insert([
            'name' => 'Super Admin',
            'email' => 'admin@darasa.com',
            'password' => Hash::make('password123'),
            'master_password' => Hash::make('master123'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   Table created and admin inserted.\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
    echo "   Note: SuperAdmin might require central database to be configured.\n";
}

// Setup Headmaster
echo "\n4. Setting up Headmaster...\n";
try {
    $hm = DB::table('headmasters')->where('registration_number', 'HM/2024/001')->first();
    if ($hm) {
        DB::table('headmasters')->where('registration_number', 'HM/2024/001')->update([
            'is_active' => true,
            'updated_at' => now(),
        ]);
        echo "   Headmaster activated: HM/2024/001\n";
    } else {
        DB::table('headmasters')->insert([
            'name' => 'Head Master',
            'registration_number' => 'HM/2024/001',
            'email' => 'headmaster@darasa.com',
            'phone' => '+255 123 456 789',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   Created headmaster: HM/2024/001\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// Setup test Student
echo "\n5. Setting up test Student...\n";
try {
    $student = DB::table('students')->where('student_reg_no', 'STD/2024/001')->first();
    if (!$student) {
        DB::table('students')->insert([
            'name' => 'Test Student',
            'student_reg_no' => 'STD/2024/001',
            'gender' => 'Male',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   Created: STD/2024/001\n";
    } else {
        echo "   Already exists: STD/2024/001\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=====================================================\n";
echo "                   LOGIN CREDENTIALS                 \n";
echo "=====================================================\n\n";

echo "ACCOUNTANT PORTAL\n";
echo "  URL: http://127.0.0.1:8000/login\n";
echo "  Email: accountant@darasa.com\n";
echo "  Password: password123\n\n";

echo "SUPER ADMIN PORTAL\n";
echo "  URL: http://127.0.0.1:8000/superadmin/login\n";
echo "  Email: admin@darasa.com\n";
echo "  Password: password123\n";
echo "  (Requires central database to be configured)\n\n";

echo "HEADMASTER PORTAL\n";
echo "  URL: http://127.0.0.1:8000/headmaster/login\n";
echo "  Registration Number: HM/2024/001\n";
echo "  (No password required - uses registration number only)\n\n";

echo "PARENT PORTAL\n";
echo "  URL: http://127.0.0.1:8000/parent/login\n";
echo "  Student Reg No: STD/2024/001\n";
echo "  (No password required - uses student reg number only)\n\n";

echo "=====================================================\n";
echo "Setup complete!\n";
