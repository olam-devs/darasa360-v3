<?php

namespace App\Services;

use App\Models\Central\ActivityLog;
use App\Models\Central\School;
use Illuminate\Foundation\Auth\User as SuperAdmin; // accepts both Central\SuperAdmin and Platform\PlatformSuperAdmin
use App\Models\Central\SchoolAccountant;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Log super admin action.
     *
     * @param SuperAdmin $superAdmin
     * @param string $action
     * @param string|null $description
     * @param School|null $school
     * @return ActivityLog
     */
    public function logSuperAdminAction(SuperAdmin $superAdmin, string $action, ?string $description = null, ?School $school = null): ActivityLog
    {
        return ActivityLog::create([
            'school_id' => $school?->id,
            'user_type' => 'super_admin',
            'user_id' => $superAdmin->id,
            'user_name' => $superAdmin->name,
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Log school accountant action.
     *
     * @param SchoolAccountant $accountant
     * @param string $action
     * @param string|null $description
     * @return ActivityLog
     */
    public function logAccountantAction(SchoolAccountant $accountant, string $action, ?string $description = null): ActivityLog
    {
        return ActivityLog::create([
            'school_id' => $accountant->school_id,
            'user_type' => 'accountant',
            'user_id' => $accountant->id,
            'user_name' => $accountant->name,
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Log accountant action by user (from web auth).
     * Used when logging from the tenant context where we don't have SchoolAccountant directly.
     *
     * @param \App\Models\User $user
     * @param int $schoolId
     * @param string $action
     * @param string|null $description
     * @return ActivityLog
     */
    public function logAccountantActionByUser($user, int $schoolId, string $action, ?string $description = null): ActivityLog
    {
        return ActivityLog::create([
            'school_id' => $schoolId,
            'user_type' => 'accountant',
            'user_id' => $user->id,
            'user_name' => $user->name,
            'action' => $action,
            'description' => $description,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Log school creation.
     *
     * @param School $school
     * @return ActivityLog
     */
    public function logSchoolCreation(School $school): ActivityLog
    {
        $superAdmin = auth('superadmin')->user();
        
        return $this->logSuperAdminAction(
            $superAdmin,
            'create_school',
            "Created new school: {$school->name} (Database: {$school->database_name})",
            $school
        );
    }

    /**
     * Log school deletion.
     *
     * @param School $school
     * @return ActivityLog
     */
    public function logSchoolDeletion(School $school): ActivityLog
    {
        $superAdmin = auth('superadmin')->user();
        
        return $this->logSuperAdminAction(
            $superAdmin,
            'delete_school',
            "Deleted school: {$school->name} (ID: {$school->id})",
            $school
        );
    }

    /**
     * Log school status toggle.
     *
     * @param School $school
     * @param bool $newStatus
     * @return ActivityLog
     */
    public function logSchoolStatusToggle(School $school, bool $newStatus): ActivityLog
    {
        $superAdmin = auth('superadmin')->user();
        $status = $newStatus ? 'activated' : 'deactivated';
        
        return $this->logSuperAdminAction(
            $superAdmin,
            'toggle_school_status',
            "School {$school->name} has been {$status}",
            $school
        );
    }

    /**
     * Log impersonation start.
     *
     * @param SuperAdmin $superAdmin
     * @param School $school
     * @return ActivityLog
     */
    public function logImpersonationStart(SuperAdmin $superAdmin, School $school): ActivityLog
    {
        return $this->logSuperAdminAction(
            $superAdmin,
            'impersonate_start',
            "Started impersonation session for school: {$school->name}",
            $school
        );
    }

    /**
     * Log impersonation end.
     *
     * @param SuperAdmin $superAdmin
     * @param School $school
     * @return ActivityLog
     */
    public function logImpersonationEnd(SuperAdmin $superAdmin, School $school): ActivityLog
    {
        return $this->logSuperAdminAction(
            $superAdmin,
            'impersonate_end',
            "Ended impersonation session for school: {$school->name}",
            $school
        );
    }

    /**
     * Log super admin login.
     *
     * @param SuperAdmin $superAdmin
     * @return ActivityLog
     */
    public function logSuperAdminLogin(SuperAdmin $superAdmin): ActivityLog
    {
        return $this->logSuperAdminAction(
            $superAdmin,
            'login',
            "Super admin logged in: {$superAdmin->email}"
        );
    }

    /**
     * Log super admin logout.
     *
     * @param SuperAdmin $superAdmin
     * @return ActivityLog
     */
    public function logSuperAdminLogout(SuperAdmin $superAdmin): ActivityLog
    {
        return $this->logSuperAdminAction(
            $superAdmin,
            'logout',
            "Super admin logged out: {$superAdmin->email}"
        );
    }

    /**
     * Get recent activities for a school.
     *
     * @param School $school
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSchoolActivities(School $school, int $limit = 50)
    {
        return ActivityLog::forSchool($school->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get all super admin activities.
     *
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getSuperAdminActivities(int $limit = 100)
    {
        return ActivityLog::superAdminOnly()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Log headmaster-related action by accountant.
     *
     * @param int $schoolId
     * @param string $accountantName
     * @param int $accountantId
     * @param string $action
     * @param string|null $description
     * @return ActivityLog
     */
    public function logHeadmasterAction(int $schoolId, string $accountantName, int $accountantId, string $action, ?string $description = null): ActivityLog
    {
        return ActivityLog::create([
            'school_id' => $schoolId,
            'user_type' => 'accountant',
            'user_id' => $accountantId,
            'user_name' => $accountantName,
            'action' => 'headmaster_' . $action,
            'description' => $description,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Log parent portal action.
     *
     * @param int $schoolId
     * @param int $studentId
     * @param string $studentName
     * @param string $action
     * @param string|null $description
     * @return ActivityLog
     */
    public function logParentAction(int $schoolId, int $studentId, string $studentName, string $action, ?string $description = null): ActivityLog
    {
        return ActivityLog::create([
            'school_id' => $schoolId,
            'user_type' => 'parent',
            'user_id' => $studentId,
            'user_name' => "Parent of {$studentName}",
            'action' => 'parent_' . $action,
            'description' => $description,
            'ip_address' => Request::ip(),
        ]);
    }

    /**
     * Log headmaster portal action.
     *
     * @param int $schoolId
     * @param int $headmasterId
     * @param string $headmasterName
     * @param string $action
     * @param string|null $description
     * @return ActivityLog
     */
    public function logHeadmasterPortalAction(int $schoolId, int $headmasterId, string $headmasterName, string $action, ?string $description = null): ActivityLog
    {
        return ActivityLog::create([
            'school_id' => $schoolId,
            'user_type' => 'headmaster',
            'user_id' => $headmasterId,
            'user_name' => $headmasterName,
            'action' => 'headmaster_' . $action,
            'description' => $description,
            'ip_address' => Request::ip(),
        ]);
    }
}
