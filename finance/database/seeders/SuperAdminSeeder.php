<?php

namespace Database\Seeders;

use App\Models\Central\SuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create default super admin
        SuperAdmin::create([
            'name' => 'Super Administrator',
            'email' => 'admin@darasafinance.com',
            'password' => Hash::make('password'), // Change this in production!
            'master_password' => Hash::make('masterpass123'), // Change this in production!
            'is_active' => true,
        ]);

        $this->command->info('Super admin created successfully!');
        $this->command->info('Email: admin@darasafinance.com');
        $this->command->info('Password: password');
        $this->command->info('Master Password: masterpass123');
        $this->command->warn('IMPORTANT: Change these credentials in production!');
    }
}
