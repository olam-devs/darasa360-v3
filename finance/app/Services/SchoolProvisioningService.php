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
                        $result = $this->tenantManager->createDatabase($school->database_name);
                        if ($result === false) {
                            throw new \Exception("Failed to create Finance database for school: {$school->name}");
                        }
                        // DA returns per-DB credentials; store them so tenant connection works
                        if (is_array($result)) {
                            $school->update(['db_username' => $result['db_user'], 'db_password' => $result['db_password']]);
                        }
                        $this->runTenantMigrations($school);
                        $this->seedDefaultData($school);
                    }
                    $this->createDefaultAccountant($school, $data);
                }

                // ── Academics tenant DB ───────────────────────────────────
                // Delegates entirely to Academics' own internal API rather than
                // trying to run Academics' migrations from within Finance's own
                // process (that used to point at the wrong migrations path -
                // Academics' central/admin schema, not database/migrations/school
                // - and would have run against Finance's own 'tenant' connection
                // config since the migration files hardcode Schema::connection
                // ('tenant'), not whatever ad-hoc connection name Finance set up).
                // Academics provisioning itself, in its own process, avoids all
                // of that - see academics/app/Services/SchoolProvisioningService.
                if ($hasAcademics) {
                    $requestedAcademicsDb = $data['academics_db_name'] ?? throw new \Exception('academics_db_name is required when has_academics is set.');

                    // Don't trust the HTTP response body for the actual database
                    // name - this cross-app call runs 100+ migrations and can take
                    // long enough that this host's proxy/timeout layer has been
                    // observed to interfere with getting a clean response back,
                    // even when the underlying provisioning genuinely succeeded.
                    // Academics' DirectAdminService always prefixes deterministically
                    // the same way Finance's own does, so compute it locally instead.
                    $acadPrefix = config('directadmin.db_prefix', '');
                    $acadNameWithoutPrefix = str_starts_with($requestedAcademicsDb, $acadPrefix) && $acadPrefix !== ''
                        ? substr($requestedAcademicsDb, strlen($acadPrefix))
                        : $requestedAcademicsDb;
                    $academicsDb = $acadPrefix . $acadNameWithoutPrefix;

                    $this->provisionAcademicsSchool([
                        'school_name' => $data['name'],
                        'location_name' => $data['academics_location_name'] ?? $data['address'] ?? $data['name'],
                        'db_name' => $requestedAcademicsDb,
                        'owner_name' => $data['academics_owner_name'],
                        'owner_email' => $data['academics_owner_email'],
                        'owner_phone' => $data['academics_owner_phone'],
                        'owner_username' => $data['academics_owner_username'],
                        'owner_password' => $data['academics_owner_password'],
                    ], $academicsDb);

                    $school->update(['academics_db_name' => $academicsDb]);
                    PlatformSchool::where('id', $school->platform_school_id)
                        ->update(['academics_db_name' => $academicsDb]);
                }

                // 6. Log the activity
                $this->activityLogger->logSchoolCreation($school);

                // Carry accountant plain password on school for controller to display
                if (isset($accountant)) {
                    $school->accountant_plain_password = $accountant->plain_password ?? null;
                    $school->accountant_email = $accountant->email;
                }

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
     * Call Academics' internal cross-app API (see academics/routes/internal_api.php).
     * Academics provisions itself entirely in its own process using its own,
     * already-correct provisioning service - this just makes the request and
     * returns the decoded response.
     */
    public function callAcademicsInternalApi(string $path, array $payload, string $method = 'post'): ?array
    {
        $baseUrl = rtrim(config('services.academics.url', ''), '/');
        $secret = config('services.internal_api.secret');

        if (!$baseUrl || !$secret) {
            throw new \Exception('Academics internal API not configured (ACADEMICS_APP_URL / INTERNAL_API_SECRET).');
        }

        $http = \Illuminate\Support\Facades\Http::withHeaders([
            'X-Internal-Api-Secret' => $secret,
        ])->timeout(300);

        $response = $method === 'get' ? $http->get($baseUrl . $path, $payload) : $http->post($baseUrl . $path, $payload);

        $decoded = $response->json();
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Provision a school in Academics, tolerating this host's tendency to
     * garble/timeout the HTTP response on this specific call (it runs 100+
     * migrations and can take minutes) even when the underlying work
     * genuinely succeeded - verified this happening repeatedly during
     * testing (2026-07-26/27). Rather than treat "couldn't read the
     * response" the same as "it failed", fall back to asking Academics'
     * own fast, read-only /internal-api/schools/exists endpoint (a simple
     * DB lookup, not subject to the same timeout risk) before deciding.
     *
     * @throws \Exception only on a *confirmed* failure (either an explicit
     *         ok:false from Academics, or exists:false after checking).
     */
    public function provisionAcademicsSchool(array $payload, string $expectedDbName): void
    {
        $response = $this->callAcademicsInternalApi('/internal-api/schools', $payload);

        if ($response !== null) {
            if (!($response['ok'] ?? false)) {
                throw new \Exception('Academics provisioning failed: ' . ($response['error'] ?? 'unknown error'));
            }
            return; // clean, confirmed success
        }

        Log::warning("Academics create-school response was unreadable (likely a proxy timeout on this host) for {$expectedDbName} - verifying via the exists check instead of assuming failure.");

        $check = $this->callAcademicsInternalApi('/internal-api/schools/exists', ['db_name' => $expectedDbName], 'get');

        if (!($check['exists'] ?? false)) {
            if ($check['row_exists_but_incomplete'] ?? false) {
                throw new \Exception("Academics provisioning was interrupted mid-migration for {$expectedDbName} - a school record exists but is not usable (this host can kill very long-running requests before they finish, even server-side). This needs manual cleanup (drop the partial database via DirectAdmin, delete the incomplete school row in Academics) before retrying with the same name - see CLAUDE.md.");
            }
            throw new \Exception("Academics provisioning could not be confirmed (no readable response, and the school does not exist yet at {$expectedDbName}). It may still be running in the background - check Academics' schools list before retrying.");
        }

        Log::info("Confirmed via exists-check: Academics provisioning for {$expectedDbName} actually succeeded despite the unreadable create response.");
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
        $password = (!empty($data['accountant_password'])) ? $data['accountant_password'] : Str::random(12);

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

        // Expose plain password so controller can show it in the success message
        $accountant->plain_password = $password;

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
