<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolAdmin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class AllAdminsSeeder extends Seeder
{
    /**
     * Run the database seeder.
     * Creates Super Admin, System Admin, and School Admin accounts
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            // Get or create roles
            $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
            $systemAdminRole = Role::firstOrCreate(['name' => 'system_admin']);

            // 1. Create Super Admin
            $superAdmin = User::updateOrCreate(
                ['username' => 'superadmin'],
                [
                    'password' => Hash::make('SuperAdmin@2026'),
                    'email' => 'superadmin@olam.system',
                    'phone_number' => '+255700000001',
                    'role_id' => $superAdminRole->id,
                ]
            );

            echo "✓ Super Admin created:\n";
            echo "  Username: superadmin\n";
            echo "  Password: SuperAdmin@2026\n";
            echo "  Email: superadmin@olam.system\n\n";

            // 2. Create System Admin
            $systemAdmin = User::updateOrCreate(
                ['username' => 'systemadmin'],
                [
                    'password' => Hash::make('SystemAdmin@2026'),
                    'email' => 'systemadmin@olam.system',
                    'phone_number' => '+255700000002',
                    'role_id' => $systemAdminRole->id,
                ]
            );

            echo "✓ System Admin created:\n";
            echo "  Username: systemadmin\n";
            echo "  Password: SystemAdmin@2026\n";
            echo "  Email: systemadmin@olam.system\n";
            echo "  Role: Manages schools and handles escalated tickets from school Admins\n\n";

            // Assign System Admin to a school if one exists
            $firstSchool = School::first();

            if ($firstSchool) {
                SchoolAdmin::updateOrCreate(
                    [
                        'school_id' => $firstSchool->id,
                        'system_admin_id' => $systemAdmin->id,
                    ],
                    [
                        'admin_type' => 'system_admin',
                        'is_active' => true,
                    ]
                );

                echo "✓ System Admin assigned to school: {$firstSchool->name}\n\n";
            } else {
                echo "⚠ No schools found. System Admin not assigned to any school.\n";
                echo "  You can assign them to schools later through the Super Admin panel.\n\n";
            }

            echo "=================================================\n";
            echo "ADMIN HIERARCHY SUMMARY:\n";
            echo "=================================================\n";
            echo "1. SUPER ADMIN (Highest Authority)\n";
            echo "   - Complete system oversight\n";
            echo "   - Manages all schools, admins, billing, SMS, locations\n";
            echo "   - Handles final escalations\n";
            echo "   - URL: /super-admin/dashboard\n\n";

            echo "2. SYSTEM ADMIN\n";
            echo "   - Manages assigned schools\n";
            echo "   - Handles tickets escalated from school Admins\n";
            echo "   - Can escalate to Super Admin\n";
            echo "   - URL: /system-admin/dashboard\n\n";

            echo "3. SCHOOL ADMIN (tenant DB)\n";
            echo "   - Lives in each school's own database\n";
            echo "   - Manages staff, creates support tickets, escalates to System Admin\n";
            echo "   - URL: /school-admin/dashboard\n\n";

            echo "=================================================\n";
            echo "ESCALATION WORKFLOW:\n";
            echo "=================================================\n";
            echo "School Admin (tenant) → System Admin → Super Admin\n\n";

            DB::commit();

            echo "✓ All admin accounts created successfully!\n";

        } catch (\Exception $e) {
            DB::rollBack();
            echo "✗ Error creating admin accounts: " . $e->getMessage() . "\n";
            throw $e;
        }
    }
}
