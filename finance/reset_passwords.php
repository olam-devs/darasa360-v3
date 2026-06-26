<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

echo "=== Darasa Finance Password Reset & Setup ===\n\n";

// Check and create school_settings if needed
echo "1. Checking school_settings table...\n";
try {
    if (!Schema::hasTable('school_settings')) {
        echo "   Creating school_settings table...\n";
        Schema::create('school_settings', function ($table) {
            $table->id();
            $table->string('school_name')->default('School Name');
            $table->string('po_box')->nullable();
            $table->string('region')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('logo_path')->nullable();
            $table->timestamps();
        });
        echo "   Table created!\n";
    } else {
        echo "   Table exists.\n";
    }

    // Insert default settings if empty
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
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// Reset/Create Accountant User
echo "\n2. Setting up Accountant user...\n";
try {
    $user = DB::table('users')->where('email', 'accountant@darasa.com')->first();
    if ($user) {
        DB::table('users')->where('email', 'accountant@darasa.com')->update([
            'password' => Hash::make('password123'),
            'updated_at' => now(),
        ]);
        echo "   Password reset for: accountant@darasa.com\n";
    } else {
        DB::table('users')->insert([
            'name' => 'School Accountant',
            'email' => 'accountant@darasa.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   Created new user: accountant@darasa.com\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// Reset/Create Super Admin
echo "\n3. Setting up Super Admin...\n";
try {
    if (Schema::hasTable('super_admins')) {
        $admin = DB::table('super_admins')->where('email', 'admin@darasa.com')->first();
        if ($admin) {
            DB::table('super_admins')->where('email', 'admin@darasa.com')->update([
                'password' => Hash::make('password123'),
                'updated_at' => now(),
            ]);
            echo "   Password reset for: admin@darasa.com\n";
        } else {
            DB::table('super_admins')->insert([
                'name' => 'Super Admin',
                'email' => 'admin@darasa.com',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   Created new super admin: admin@darasa.com\n";
        }
    } else {
        echo "   super_admins table does not exist. Creating...\n";
        Schema::create('super_admins', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
        DB::table('super_admins')->insert([
            'name' => 'Super Admin',
            'email' => 'admin@darasa.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   Table created and admin inserted: admin@darasa.com\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// Reset/Create Headmaster
echo "\n4. Setting up Headmaster...\n";
try {
    if (Schema::hasTable('headmasters')) {
        $hm = DB::table('headmasters')->where('email', 'headmaster@darasa.com')->first();
        if ($hm) {
            DB::table('headmasters')->where('email', 'headmaster@darasa.com')->update([
                'password' => Hash::make('password123'),
                'updated_at' => now(),
            ]);
            echo "   Password reset for: headmaster@darasa.com\n";
        } else {
            DB::table('headmasters')->insert([
                'name' => 'Head Master',
                'email' => 'headmaster@darasa.com',
                'password' => Hash::make('password123'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            echo "   Created new headmaster: headmaster@darasa.com\n";
        }
    } else {
        echo "   headmasters table does not exist. Creating...\n";
        Schema::create('headmasters', function ($table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });
        DB::table('headmasters')->insert([
            'name' => 'Head Master',
            'email' => 'headmaster@darasa.com',
            'password' => Hash::make('password123'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "   Table created and headmaster inserted: headmaster@darasa.com\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

// Create a test student for parent login
echo "\n5. Setting up test Student for Parent Portal...\n";
try {
    if (Schema::hasTable('students')) {
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
            echo "   Created test student: STD/2024/001\n";
        } else {
            echo "   Test student already exists: STD/2024/001\n";
        }
    } else {
        echo "   students table does not exist.\n";
    }
} catch (Exception $e) {
    echo "   Error: " . $e->getMessage() . "\n";
}

echo "\n===========================================\n";
echo "LOGIN CREDENTIALS\n";
echo "===========================================\n\n";

echo "ACCOUNTANT PORTAL (http://127.0.0.1:8000/login)\n";
echo "  Email: accountant@darasa.com\n";
echo "  Password: password123\n\n";

echo "SUPER ADMIN PORTAL (http://127.0.0.1:8000/superadmin/login)\n";
echo "  Email: admin@darasa.com\n";
echo "  Password: password123\n\n";

echo "HEADMASTER PORTAL (http://127.0.0.1:8000/headmaster/login)\n";
echo "  Email: headmaster@darasa.com\n";
echo "  Password: password123\n\n";

echo "PARENT PORTAL (http://127.0.0.1:8000/parent/login)\n";
echo "  Student Reg No: STD/2024/001\n\n";

echo "===========================================\n";
echo "Setup complete!\n";
