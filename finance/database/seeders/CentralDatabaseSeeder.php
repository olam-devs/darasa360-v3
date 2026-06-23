<?php

namespace Database\Seeders;

use App\Models\Central\SuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CentralDatabaseSeeder extends Seeder
{
    /**
     * Seed the central database with default data.
     */
    public function run(): void
    {
        // Create default super admin if not exists
        if (!SuperAdmin::where('email', 'admin@darasa360.com')->exists()) {
            SuperAdmin::create([
                'name' => 'Super Admin',
                'email' => 'admin@darasa360.com',
                'password' => Hash::make('Darasa@2024'),
                'is_active' => true,
            ]);

            $this->command->info('Default super admin created: admin@darasa360.com / Darasa@2024');
        } else {
            $this->command->warn('Default super admin already exists.');
        }
    }
}
