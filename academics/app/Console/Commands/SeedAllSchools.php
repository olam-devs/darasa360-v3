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
            $this->info("Seeding database for school: {$school->name}");

            // Set dynamic tenant DB config
            Config::set('database.connections.tenant', [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => $school->database_url,
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
            ]);

            // Refresh connection
            DB::purge('tenant');
            DB::reconnect('tenant');

            // Run the seeder on tenant connection
            $this->call(MasterTenantSeeder::class, [], 'tenant');
        }

        $this->info("✅ All tenant school databases seeded successfully.");
    }
}
