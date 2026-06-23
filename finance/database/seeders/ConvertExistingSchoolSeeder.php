<?php

namespace Database\Seeders;

use App\Models\Central\School;
use App\Models\Central\SchoolAccountant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ConvertExistingSchoolSeeder extends Seeder
{
    /**
     * Convert the existing database to School #1.
     */
    public function run(): void
    {
        $this->command->info('Converting existing school to multi-tenant School #1...');

        // Check if school already exists
        $existingSchool = School::where('slug', 'school-1')->first();
        
        if ($existingSchool) {
            $this->command->warn('School #1 already exists! Skipping conversion.');
            return;
        }

        try {
            DB::connection('central')->beginTransaction();

            // Create School #1 record
            $school = School::create([
                'name' => env('SCHOOL_NAME', 'Main School'),
                'slug' => 'school-1',
                'database_name' => env('DB_DATABASE', 'darasa_finance_db'),
                'contact_email' => env('SCHOOL_EMAIL', 'school@example.com'),
                'contact_phone' => env('SCHOOL_PHONE'),
                'address' => env('SCHOOL_ADDRESS'),
                'is_active' => true,
                'subscription_status' => 'active',
                'subscription_expires_at' => now()->addYear(),
                'max_students' => 2000,
            ]);

            $this->command->info("Created School: {$school->name}");
            $this->command->info("Database: {$school->database_name}");

            // Find or create accountant entry
            $accountantEmail = env('ACCOUNTANT_EMAIL', 'accountant@school.com');
            
            $accountant = SchoolAccountant::create([
                'school_id' => $school->id,
                'name' => env('ACCOUNTANT_NAME', 'School Accountant'),
                'email' => $accountantEmail,
                'password' => Hash::make(env('ACCOUNTANT_PASSWORD', 'password')),
                'is_active' => true,
            ]);

            $this->command->info("Created/Updated Accountant: {$accountant->email}");

            DB::connection('central')->commit();

            $this->command->info('✅ Successfully converted existing school to School #1!');
            $this->command->info('');
            $this->command->info('IMPORTANT: Update your .env file with these values:');
            $this->command->info("SCHOOL_NAME='{$school->name}'");
            $this->command->info("SCHOOL_EMAIL={$accountantEmail}");
            
        } catch (\Exception $e) {
            DB::connection('central')->rollBack();
            $this->command->error('Failed to convert school: ' . $e->getMessage());
            throw $e;
        }
    }
}
