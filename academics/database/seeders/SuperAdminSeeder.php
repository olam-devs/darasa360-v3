<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the super_admin role
        $superAdminRole = Role::where('name', 'super_admin')->first();

        if (!$superAdminRole) {
            $this->command->error('Super admin role not found. Please run migrations first.');
            return;
        }

        // Check if super admin already exists (by username or email)
        $existingSuperAdmin = User::where('username', 'admin')
            ->orWhere('email', 'superadmin@olam.com')
            ->first();

        if ($existingSuperAdmin) {
            // Update existing super admin
            $existingSuperAdmin->update([
                'username' => 'admin',
                'password' => Hash::make('admin222'),
                'role_id' => $superAdminRole->id,
                'email' => 'superadmin@olam.com',
                'phone_number' => '+255000000000',
            ]);
            $this->command->info('Super admin user updated successfully!');
        } else {
            // Create super admin user
            User::create([
                'username' => 'admin',
                'password' => Hash::make('admin222'),
                'role_id' => $superAdminRole->id,
                'email' => 'superadmin@olam.com',
                'phone_number' => '+255000000000',
            ]);
            $this->command->info('Super admin user created successfully!');
        }

        $this->command->info('Username: admin');
        $this->command->info('Password: admin222');
    }
}
