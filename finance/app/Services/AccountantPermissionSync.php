<?php

namespace App\Services;

use App\Models\Central\School;
use App\Models\Central\SchoolAccountant;
use Illuminate\Support\Facades\DB;

/**
 * Shared by SuperAdmin\SchoolController and AccountantPermissionController -
 * both write can_edit_history/can_view_logs on the authoritative central
 * SchoolAccountant record, then best-effort mirror it to the legacy tenant
 * `users` table for backward compatibility. Central record is always
 * authoritative; the tenant mirror is not read by any enforcement code.
 */
class AccountantPermissionSync
{
    public function __construct(protected TenantDatabaseManager $tenantManager)
    {
    }

    public function sync(School $school, SchoolAccountant $accountant): void
    {
        try {
            $this->tenantManager->executeForSchool($school, function () use ($accountant) {
                DB::connection('tenant')->table('users')
                    ->where('email', $accountant->email)
                    ->update([
                        'can_edit_history' => (bool) $accountant->can_edit_history,
                        'can_view_logs' => (bool) $accountant->can_view_logs,
                        'updated_at' => now(),
                    ]);
            });
        } catch (\Throwable $e) {
            // Non-fatal: central record is authoritative; tenant may be single-DB setup.
        }
    }
}
