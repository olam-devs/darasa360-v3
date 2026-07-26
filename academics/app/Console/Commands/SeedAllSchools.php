<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use App\Models\School;
use Database\Seeders\MasterTenantSeeder;

class SeedAllSchools extends Command
{
    protected $signature = 'seed:schools';
    protected $description = 'Seed all tenant (school) databases with default data';

    public function handle()
    {
        $schools = School::all();

        foreach ($schools as $school) {
            if (!$school->db_username || !$school->db_password) {
                $this->warn("Skipping {$school->name}: no dedicated db credentials stored yet.");
                continue;
            }

            $this->info("Seeding database for school: {$school->name}");
            $school->useAsTenant();

            // Run the seeder on tenant connection
            $this->call(MasterTenantSeeder::class, [], 'tenant');
        }

        $this->info("✅ All tenant school databases seeded successfully.");
    }
}
