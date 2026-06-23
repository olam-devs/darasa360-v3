<?php

namespace App\Helpers;

use App\Models\Role;
use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\DB;

class RegistrationHelper
{
  /**
   * Generate registration number for a user.
   *
   * Format: LOCATION_CODE-XXX (e.g. DAR-001)
   *
   * @param int $schoolId
   * @param string $locationCode e.g. 'DAR'
   * @return string
   */



  public static function generateRegistrationNo($roleId, $schoolId, $tenantDb = null)
  {
    if ($roleId == 1) {
      // Superadmin/global admin format: adminXXX
      $roleName = 'admin';
      $lastUser = DB::table('users')
        ->where('role_id', $roleId)
        ->where('registration_no', 'LIKE', "{$roleName}%")
        ->orderByDesc('id')
        ->first();

      $lastSerial = 0;
      if ($lastUser && isset($lastUser->registration_no)) {
        $lastSerial = (int)substr($lastUser->registration_no, -3);
      }
      $newSerial = str_pad($lastSerial + 1, 3, '0', STR_PAD_LEFT);
      return "{$roleName}{$newSerial}";
    }

    // Tenant DB for other roles
    $tenantDb = $tenantDb ?? session('school_db');
    if (!$tenantDb) {
      throw new \Exception('Tenant database not found.');
    }

    config(['database.connections.tenant.database' => $tenantDb]);
    DB::purge('tenant');
    DB::reconnect('tenant');

    // Find last created user for this role
    $lastUser = DB::connection('tenant')
      ->table('schoolUsers')
      ->where('role_id', $roleId)
      ->orderByDesc('id')
      ->first();

    // Get new user ID (last id + 1, padded to 4 digits)
    $newUserId = $lastUser ? $lastUser->id + 1 : 1;
    $userIdPadded = str_pad($newUserId, 4, '0', STR_PAD_LEFT);

    // Format: S{schoolId}{roleId}{userIdPadded}
    return 'S' . str_pad($schoolId, 3, '0', STR_PAD_LEFT) . $roleId . $userIdPadded;
  }
}
