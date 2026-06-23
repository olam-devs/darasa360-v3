<?php

namespace Database\Seeders;

use App\Models\Platform\PlatformSuperAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PlatformSeeder extends Seeder
{
    /**
     * Seed the shared darasa_platform DB with the default super admin.
     * This single account logs into BOTH Finance and Academics.
     */
    public function run(): void
    {
        $admin = PlatformSuperAdmin::firstOrNew(['email' => 'admin@darasa360.com']);

        if (!$admin->exists) {
            $admin->name = 'Super Admin';
            $admin->password = 'Admin@2024';            // hashed via model cast
            $admin->master_password = 'Master@2024';    // override into any school account
            $admin->is_active = true;
            $admin->save();

            $this->command->info('Platform super admin created: admin@darasa360.com / Admin@2024 (master: Master@2024)');
        } else {
            $this->command->warn('Platform super admin already exists: admin@darasa360.com');
        }
    }
}
