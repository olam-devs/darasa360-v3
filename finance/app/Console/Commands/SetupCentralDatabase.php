<?php

namespace App\Console\Commands;

use App\Models\Central\School;
use App\Models\Central\SuperAdmin;
use App\Models\Central\SchoolAccountant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SetupCentralDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'central:setup
                            {--sync-school : Sync existing tenant school to central database}
                            {--create-admin : Create default super admin}
                            {--reset : Reset and recreate all central data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set up the central database with default data and sync existing school';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Setting up Central Database...');

        if ($this->option('reset')) {
            if ($this->confirm('This will DELETE all central database data. Are you sure?')) {
                $this->resetCentralDatabase();
            }
        }

        if ($this->option('create-admin') || $this->option('reset')) {
            $this->createDefaultSuperAdmin();
        }

        if ($this->option('sync-school') || $this->option('reset')) {
            $this->syncExistingSchool();
        }

        $this->info('Central database setup complete!');
        $this->newLine();
        $this->showStatus();
    }

    /**
     * Reset central database tables.
     */
    protected function resetCentralDatabase(): void
    {
        $this->warn('Resetting central database...');

        DB::connection('central')->table('activity_logs')->truncate();
        DB::connection('central')->table('analytics_summary')->truncate();
        DB::connection('central')->table('school_accountants')->truncate();
        DB::connection('central')->table('schools')->truncate();
        DB::connection('central')->table('super_admins')->truncate();

        $this->info('Central database reset complete.');
    }

    /**
     * Create default super admin account.
     */
    protected function createDefaultSuperAdmin(): void
    {
        $this->info('Creating default super admin...');

        // Check if super admin already exists
        $existingAdmin = SuperAdmin::where('email', 'admin@darasa360.com')->first();

        if ($existingAdmin) {
            $this->warn('Default super admin already exists. Skipping...');
            return;
        }

        $admin = SuperAdmin::create([
            'name' => 'Super Admin',
            'email' => 'admin@darasa360.com',
            'password' => Hash::make('Darasa@2024'),
            'master_password' => Hash::make('Master@2024'),
            'is_active' => true,
        ]);

        $this->info('Default super admin created:');
        $this->table(
            ['Email', 'Password'],
            [['admin@darasa360.com', 'Darasa@2024']]
        );
        $this->warn('IMPORTANT: Change this password immediately after first login!');
    }

    /**
     * Sync existing tenant school to central database.
     */
    protected function syncExistingSchool(): void
    {
        $this->info('Syncing existing school from tenant database...');

        // Get tenant database name from config
        $tenantDbName = env('TENANT_DB_DATABASE') ?: env('DB_DATABASE');

        if (!$tenantDbName) {
            $this->error('Could not determine tenant database name from environment.');
            return;
        }

        $this->info("Tenant database: {$tenantDbName}");

        // Get school settings from tenant database
        try {
            $tenantSettings = DB::connection('mysql')->table('school_settings')->first();

            if (!$tenantSettings) {
                $this->error('No school_settings found in tenant database.');
                return;
            }

            $schoolName = $tenantSettings->school_name ?? 'Unknown School';
            $this->info("Found school: {$schoolName}");

            // Check if school already exists in central
            $existingSchool = School::where('database_name', $tenantDbName)->first();

            if ($existingSchool) {
                // Update existing school
                $existingSchool->update([
                    'name' => $schoolName,
                    'contact_email' => $tenantSettings->email ?? $existingSchool->contact_email,
                    'contact_phone' => $tenantSettings->phone ?? $existingSchool->contact_phone,
                    'address' => $tenantSettings->address ?? $tenantSettings->region ?? $existingSchool->address,
                ]);
                $this->info("Updated existing school record (ID: {$existingSchool->id})");
                $school = $existingSchool;
            } else {
                // Create new school record
                $school = School::create([
                    'name' => $schoolName,
                    'slug' => \Illuminate\Support\Str::slug($schoolName),
                    'database_name' => $tenantDbName,
                    'contact_email' => $tenantSettings->email ?? 'school@example.com',
                    'contact_phone' => $tenantSettings->phone,
                    'address' => $tenantSettings->address ?? $tenantSettings->region,
                    'is_active' => true,
                    'subscription_status' => 'active',
                    'max_students' => 1000,
                    'sms_credits_assigned' => 0,
                    'sms_credits_used' => 0,
                ]);
                $this->info("Created new school record (ID: {$school->id})");
            }

            // Sync accountants from tenant users table
            $this->syncAccountants($school);

            $this->info('School sync complete!');
            $this->table(
                ['Field', 'Value'],
                [
                    ['ID', $school->id],
                    ['Name', $school->name],
                    ['Database', $school->database_name],
                    ['Email', $school->contact_email],
                    ['SMS Credits', $school->sms_credits_assigned],
                ]
            );

        } catch (\Exception $e) {
            $this->error('Error syncing school: ' . $e->getMessage());
        }
    }

    /**
     * Sync accountants from tenant users table.
     */
    protected function syncAccountants(School $school): void
    {
        $this->info('Syncing accountants...');

        try {
            // Get users from tenant database
            $users = DB::connection('mysql')->table('users')
                ->where('role', 'accountant')
                ->orWhereNull('role')
                ->get();

            foreach ($users as $user) {
                $existingAccountant = SchoolAccountant::where('email', $user->email)->first();

                if (!$existingAccountant) {
                    SchoolAccountant::create([
                        'school_id' => $school->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'password' => $user->password, // Already hashed
                        'is_active' => true,
                        'is_primary' => true,
                    ]);
                    $this->info("  - Added accountant: {$user->email}");
                } else {
                    $this->warn("  - Accountant already exists: {$user->email}");
                }
            }
        } catch (\Exception $e) {
            $this->warn('Could not sync accountants: ' . $e->getMessage());
        }
    }

    /**
     * Show current status.
     */
    protected function showStatus(): void
    {
        $this->info('Current Central Database Status:');
        $this->newLine();

        // Super Admins
        $adminCount = SuperAdmin::count();
        $this->info("Super Admins: {$adminCount}");

        if ($adminCount > 0) {
            $admins = SuperAdmin::all(['id', 'name', 'email', 'is_active']);
            $this->table(['ID', 'Name', 'Email', 'Active'], $admins->toArray());
        }

        $this->newLine();

        // Schools
        $schoolCount = School::count();
        $this->info("Schools: {$schoolCount}");

        if ($schoolCount > 0) {
            $schools = School::all(['id', 'name', 'database_name', 'is_active', 'sms_credits_assigned']);
            $this->table(['ID', 'Name', 'Database', 'Active', 'SMS Credits'], $schools->toArray());
        }
    }
}
