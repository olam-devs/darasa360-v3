<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreateCentralDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            // Create the central database if it doesn't exist
            DB::statement("CREATE DATABASE IF NOT EXISTS darasa_central CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            
            $this->command->info('Central database created successfully!');
        } catch (\Exception $e) {
            $this->command->error('Failed to create central database: ' . $e->getMessage());
        }
    }
}
