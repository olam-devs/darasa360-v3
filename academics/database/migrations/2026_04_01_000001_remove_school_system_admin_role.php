<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Remove school_system_admin role and all associated users/assignments
     * from the central database. The Admin role in each school's tenant DB
     * already handles all school-level admin features.
     */
    public function up(): void
    {
        // 1. Get the role ID
        $role = DB::table('roles')->where('name', 'school_system_admin')->first();

        if ($role) {
            // 2. Delete all SchoolAdmin assignments for this role's users
            $userIds = DB::table('users')->where('role_id', $role->id)->pluck('id');
            DB::table('school_admins')
                ->whereIn('system_admin_id', $userIds)
                ->delete();

            // 3. Delete all users with this role
            DB::table('users')->where('role_id', $role->id)->delete();

            // 4. Delete the role itself
            DB::table('roles')->where('id', $role->id)->delete();
        }

        // 5. Fix admin_type enum in school_admins: remove 'school_system_admin' value
        DB::statement("ALTER TABLE school_admins MODIFY COLUMN admin_type ENUM('system_admin') NOT NULL DEFAULT 'system_admin'");
    }

    public function down(): void
    {
        // Restore the enum value (data cannot be restored)
        DB::statement("ALTER TABLE school_admins MODIFY COLUMN admin_type ENUM('system_admin', 'school_system_admin') NOT NULL DEFAULT 'system_admin'");

        DB::table('roles')->insert([
            'name' => 'school_system_admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
};
