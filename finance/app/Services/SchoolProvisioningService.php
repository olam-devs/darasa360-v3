<?php

namespace App\Services;

use App\Models\Central\School;
use App\Models\Central\SchoolAccountant;
use App\Models\Platform\PlatformSchool;
use App\Services\PlatformRegistry;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SchoolProvisioningService
{
    protected TenantDatabaseManager $tenantManager;
    protected ActivityLogger $activityLogger;
    protected PlatformRegistry $platformRegistry;

    public function __construct(TenantDatabaseManager $tenantManager, ActivityLogger $activityLogger, PlatformRegistry $platformRegistry)
    {
        $this->tenantManager = $tenantManager;
        $this->activityLogger = $activityLogger;
        $this->platformRegistry = $platformRegistry;
    }

    /**
     * Provision a new school with database and default data.
     *
     * @param array $data
     * @return School
     * @throws \Exception
     */
    public function provisionSchool(array $data): School
    {
        return DB::connection('central')->transaction(function () use ($data) {
            // Check if using existing database
            $useExistingDatabase = !empty($data['use_existing_database']);
            $existingDatabaseName = $data['existing_database_name'] ?? null;

            // Determine database name
            $databaseName = $useExistingDatabase && $existingDatabaseName
                ? $existingDatabaseName
                : $this->generateDatabaseName($data['name']);

            // Allocate a unique 3-digit school code from the platform
            $code = $this->platformRegistry->allocateSchoolCode();

            $hasFinance  = (bool) ($data['has_finance']  ?? true);
            $hasAcademics = (bool) ($data['has_academics'] ?? false);

            // 1. Create school record in central database
            $school = School::create([
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']),
                'code' => $code,
                'database_name' => $hasFinance ? $databaseName : null,
                'db_host' => $data['db_host'] ?? null,
                'db_port' => $data['db_port'] ?? null,
                'db_username' => $data['db_username'] ?? null,
                'db_password' => $data['db_password'] ?? null,
                'domain' => $data['domain'] ?? null,
                'logo' => $data['logo'] ?? null,
                'contact_email' => $data['contact_email'],
                'contact_phone' => $data['contact_phone'] ?? null,
                'address' => $data['address'] ?? null,
                'is_active' => true,
                'subscription_status' => $data['subscription_status'] ?? 'active',
                'subscription_expires_at' => $data['subscription_expires_at'] ?? null,
                'max_students' => $data['max_students'] ?? 1000,
                'has_finance' => $hasFinance,
                'has_academics' => $hasAcademics,
                'cross_jump_enabled' => false,
                'parent_cross_access' => false,
                'academics_db_name' => $data['academics_db_name'] ?? null,
            ]);

            // 2. Mirror to platform_schools so Academics can discover this school
            $platformSchool = PlatformSchool::create([
                'name'             => $school->name,
                'code'             => $code,
                'slug'             => $school->slug,
                'location'         => $school->address,
                'status'           => 'active',
                'has_finance'      => true,
                'has_academics'    => $school->has_academics,
                'cross_jump_enabled' => false,
                'parent_cross_access' => false,
                'finance_db_name'  => $databaseName,
                'finance_db_host'  => $data['db_host'] ?? null,
                'finance_db_port'  => $data['db_port'] ?? null,
                'finance_db_user'  => $data['db_username'] ?? null,
                'finance_db_pass'  => $data['db_password'] ?? null,
                'academics_db_name' => $data['academics_db_name'] ?? null,
            ]);

            $school->update(['platform_school_id' => $platformSchool->id]);

            try {
                // ── Finance tenant DB ─────────────────────────────────────
                if ($hasFinance) {
                    if ($useExistingDatabase) {
                        $this->verifyExistingDatabase($school);
                        $this->syncSettingsToTenant($school);
                    } else {
                        if (!$this->tenantManager->createDatabase($school->database_name)) {
                            throw new \Exception("Failed to create Finance database for school: {$school->name}");
                        }
                        $this->runTenantMigrations($school);
                        $this->seedDefaultData($school);
                    }
                    $this->createDefaultAccountant($school, $data);
                }

                // ── Academics tenant DB ───────────────────────────────────
                if ($hasAcademics) {
                    $academicsDb = $data['academics_db_name'] ?? $this->generateAcademicsDbName($data['name']);
                    $school->update(['academics_db_name' => $academicsDb]);
                    PlatformSchool::where('id', $school->platform_school_id)
                        ->update(['academics_db_name' => $academicsDb]);

                    if (!$this->tenantManager->createDatabase($academicsDb)) {
                        throw new \Exception("Failed to create Academics database for school: {$school->name}");
                    }
                    $this->runAcademicsMigrations($academicsDb, $school);
                }

                // 6. Log the activity
                $this->activityLogger->logSchoolCreation($school);

                return $school;
            } catch (\Exception $e) {
                if (!$useExistingDatabase && $hasFinance && $school->database_name) {
                    $this->tenantManager->dropDatabase($school->database_name);
                }
                if ($hasAcademics && !empty($school->academics_db_name)) {
                    $this->tenantManager->dropDatabase($school->academics_db_name);
                }
                if (isset($platformSchool)) {
                    $this->platformRegistry->freeSchoolCode($platformSchool->id);
                    $platformSchool->delete();
                }
                $school->delete();

                throw $e;
            }
        });
    }

    /**
     * Verify that an existing database is accessible and has required tables.
     */
    protected function verifyExistingDatabase(School $school): void
    {
        $this->tenantManager->switchToSchool($school);

        try {
            // Check if basic required tables exist
            $requiredTables = ['users', 'students', 'books', 'vouchers', 'school_settings'];
            foreach ($requiredTables as $table) {
                if (!DB::connection('tenant')->getSchemaBuilder()->hasTable($table)) {
                    throw new \Exception("Required table '{$table}' not found in database '{$school->database_name}'");
                }
            }
        } finally {
            $this->tenantManager->switchToCentral();
        }
    }

    /**
     * Generate a unique Finance tenant DB name with the server prefix.
     */
    protected function generateDatabaseName(string $schoolName): string
    {
        $prefix = config('directadmin.db_prefix', '');
        $slug   = substr(Str::slug($schoolName, '_'), 0, 30);
        $count  = School::count() + 1;
        return $prefix . 'school_' . str_pad($count, 3, '0', STR_PAD_LEFT) . '_' . $slug;
    }

    /**
     * Generate a unique Academics tenant DB name with the server prefix.
     */
    protected function generateAcademicsDbName(string $schoolName): string
    {
        $prefix = config('directadmin.db_prefix', '');
        $slug   = substr(Str::slug($schoolName, '_'), 0, 25);
        $count  = School::count() + 1;
        return $prefix . 'acad_' . str_pad($count, 3, '0', STR_PAD_LEFT) . '_' . $slug;
    }

    /**
     * Run Academics migrations against a newly created Academics tenant DB.
     */
    protected function runAcademicsMigrations(string $academicsDb, School $school): void
    {
        $academicsPath = config('services.academics.path');
        if (!$academicsPath || !is_dir($academicsPath)) {
            Log::warning("Academics migrations skipped — ACADEMICS_APP_PATH not set or invalid.");
            return;
        }

        // Temporarily configure an academics_tenant connection
        config([
            'database.connections.academics_tenant' => array_merge(
                config('database.connections.mysql'),
                ['database' => $academicsDb]
            )
        ]);
        DB::purge('academics_tenant');

        try {
            Artisan::call('migrate', [
                '--database' => 'academics_tenant',
                '--path'     => $academicsPath . '/database/migrations',
                '--force'    => true,
            ]);
            Log::info("Academics migrations done for school {$school->name} on DB {$academicsDb}");
        } catch (\Exception $e) {
            Log::error("Academics migrations failed for {$school->name}: " . $e->getMessage());
            throw $e;
        }
    }

    protected function runTenantMigrations(School $school): void
    {
        // Switch to tenant database
        // Switch to tenant database
        // Custom connection handling is now done inside tenantManager->switchToSchool()


        $this->tenantManager->switchToSchool($school);

        try {
            // Create migrations table if it doesn't exist
            if (!DB::connection('tenant')->getSchemaBuilder()->hasTable('migrations')) {
                DB::connection('tenant')->getSchemaBuilder()->create('migrations', function ($table) {
                    $table->id();
                    $table->string('migration');
                    $table->integer('batch');
                });
            }

            // Run migrations with the tenant database path
            // This will run all migrations in the migrations folder
            $migrationsPath = database_path('migrations');

            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations',
                '--force' => true,
            ]);

            Log::info("Migrations completed for school: {$school->name}", [
                'database' => $school->database_name,
                'output' => Artisan::output()
            ]);
        } catch (\Exception $e) {
            Log::error("Migration failed for school: {$school->name}", [
                'database' => $school->database_name,
                'error' => $e->getMessage()
            ]);
            throw $e;
        } finally {
            // Switch back to central
            $this->tenantManager->switchToCentral();
        }
    }

    /**
     * Seed default data for the school.
     */
    protected function seedDefaultData(School $school): void
    {
        // Temporarily configure connection if custom DB details exist
        // Custom connection handling is now done inside tenantManager->switchToSchool()


        $this->tenantManager->executeForSchool($school, function () use ($school) {
            // Create default cash book
            DB::connection('tenant')->table('books')->insert([
                'name' => 'Cash Book',
                'type' => 'cash',
                'description' => 'Default cash book for the school',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create school settings
            DB::connection('tenant')->table('school_settings')->insert([
                'school_name' => $school->name,
                'email' => $school->contact_email,
                'phone' => $school->contact_phone,
                'address' => $school->address,
                'logo' => $school->logo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create default classes
            $classes = ['Form One', 'Form Two', 'Form Three', 'Form Four'];
            foreach ($classes as $className) {
                DB::connection('tenant')->table('school_classes')->insert([
                    'name' => $className,
                    'description' => "Default {$className} class",
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    /**
     * Create the default accountant user for the school.
     *
     * @param School $school
     * @param array $data
     * @return SchoolAccountant
     */
    protected function createDefaultAccountant(School $school, array $data): SchoolAccountant
    {
        $password = $data['accountant_password'] ?? Str::random(12);

        $accountant = SchoolAccountant::create([
            'school_id' => $school->id,
            'name' => $data['accountant_name'] ?? 'School Accountant',
            'email' => $data['accountant_email'] ?? $school->contact_email,
            'password' => Hash::make($password),
            'is_active' => true,
        ]);

        // Also create the user in the tenant database
        $this->tenantManager->executeForSchool($school, function () use ($accountant, $password) {
            DB::connection('tenant')->table('users')->insert([
                'name' => $accountant->name,
                'email' => $accountant->email,
                'password' => Hash::make($password),
                'role' => 'accountant',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return $accountant;
    }

    /**
     * Sync school info from central to the tenant's school_settings table.
     */
    public function syncSettingsToTenant(School $school): void
    {
        $this->tenantManager->executeForSchool($school, function () use ($school) {
            DB::connection('tenant')->table('school_settings')
                ->where('id', 1)
                ->update([
                    'school_name' => $school->name,
                    'email' => $school->contact_email,
                    'phone' => $school->contact_phone,
                    'address' => $school->address,
                    'updated_at' => now(),
                ]);
        });
    }

    /**
     * Deprovision (delete) a school and its database.
     *
     * @param School $school
     * @return bool
     */
    public function deprovisionSchool(School $school): bool
    {
        try {
            DB::connection('central')->transaction(function () use ($school) {
                // Log the activity
                $this->activityLogger->logSchoolDeletion($school);

                // Free platform school (purges sequences so code can be reused)
                if ($school->platform_school_id) {
                    $this->platformRegistry->freeSchoolCode($school->platform_school_id);
                    PlatformSchool::find($school->platform_school_id)?->delete();
                }

                // Drop Finance tenant database
                if ($school->database_name) {
                    $this->tenantManager->dropDatabase($school->database_name);
                }

                // Drop Academics tenant database
                if ($school->academics_db_name) {
                    $this->tenantManager->dropDatabase($school->academics_db_name);
                }

                // Delete school record
                $school->delete();
            });

            return true;
        } catch (\Exception $e) {
            \Log::error("Failed to deprovision school {$school->id}: " . $e->getMessage());
            return false;
        }
    }
}
