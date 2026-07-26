<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Real, working per-school provisioning (dedicated DirectAdmin-created DB,
 * database/migrations/school schema, owner account). Extracted from
 * SuperAdminController::createSchool() so the same, correct logic can be
 * called both from the web form and from the internal cross-app API that
 * Finance's own SchoolProvisioningService calls for "onboard both systems"
 * (see routes/internal_api.php) - Finance used to try to run these
 * migrations itself from within its own process, which used the wrong
 * migrations path and Finance's own 'tenant' connection instead of a
 * correctly-configured one. Delegating to this service avoids that
 * entirely: Academics always provisions itself, in its own process.
 *
 * @throws \Exception on any failure - caller's transaction/cleanup should
 *         catch and roll back; on failure here the DB/user this method
 *         created (if any) is already dropped before rethrowing.
 */
class SchoolProvisioningService
{
    /**
     * @param array $data required: school_name, db_name, owner_name, owner_email,
     *                     owner_phone, owner_username, owner_password. One of
     *                     location_id or location_name required. Optional: price_per_user.
     */
    public function provisionSchool(array $data): School
    {
        $locationId = $data['location_id'] ?? null;
        if (!$locationId && !empty($data['location_name'])) {
            $locationId = Location::firstOrCreate(
                ['name' => $data['location_name']],
                ['code' => $this->nextLocationCode()]
            )->id;
        }
        if (!$locationId) {
            throw new \Exception('location_id or location_name is required.');
        }

        $dbName = $data['db_name'];
        if (School::where('database_url', $dbName)->exists()) {
            throw new \Exception("Database name '{$dbName}' is already in use.");
        }

        DB::beginTransaction();
        $nameWithoutPrefix = null;
        $prefix = null;

        try {
            $lastCode = School::max('school_code');
            $nextNum  = $lastCode ? ((int) $lastCode + 1) : 1;
            $schoolCode = str_pad($nextNum, 3, '0', STR_PAD_LEFT);

            $prefix = config('directadmin.db_prefix', '');
            $nameWithoutPrefix = str_starts_with($dbName, $prefix) && $prefix !== ''
                ? substr($dbName, strlen($prefix))
                : $dbName;

            $created = app(DirectAdminService::class)->createDatabase($nameWithoutPrefix);
            if (!$created) {
                throw new \Exception('Failed to provision the database via DirectAdmin. Check storage/logs/laravel.log for the API response.');
            }

            $school = School::create([
                'name' => $data['school_name'],
                'location_id' => $locationId,
                'database_url' => $prefix . $nameWithoutPrefix,
                'db_username' => $created['db_user'],
                'db_password' => $created['db_password'],
                'school_code' => $schoolCode,
                'price_per_user' => $data['price_per_user'] ?? 0,
                'billing_start_date' => now(),
                'next_billing_date' => now()->addMonth(),
                'billing_status' => 'active',
                'status' => 'active',
            ]);

            $school->useAsTenant();
            try {
                DB::connection('tenant')->getPdo();
            } catch (\Exception $e) {
                throw new \Exception('Database was created but the connection test failed: ' . $e->getMessage());
            }

            \Artisan::call('migrate', [
                '--path' => 'database/migrations/school',
                '--database' => 'tenant',
                '--force' => true,
            ]);

            $ownerRole = Role::where('name', 'owner')->first();

            $owner = User::create([
                'username' => $data['owner_username'],
                'password' => Hash::make($data['owner_password']),
                'email' => $data['owner_email'],
                'phone_number' => $data['owner_phone'],
                'role_id' => $ownerRole->id,
                'school_id' => $school->id,
            ]);

            $school->update(['user_id' => $owner->id]);

            DB::commit();

            return $school;
        } catch (\Exception $e) {
            DB::rollBack();
            try {
                if (!empty($nameWithoutPrefix)) {
                    app(DirectAdminService::class)->dropDatabase(($prefix ?? '') . $nameWithoutPrefix);
                }
            } catch (\Exception $cleanupEx) {
                // Ignore cleanup errors
            }
            throw $e;
        }
    }

    protected function nextLocationCode(): string
    {
        $max = Location::max('code');
        return $max ? str_pad(((int) $max) + 1, 3, '0', STR_PAD_LEFT) : '001';
    }
}
