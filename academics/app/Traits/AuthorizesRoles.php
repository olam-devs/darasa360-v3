<?php

namespace App\Traits;

use Illuminate\Http\Response;

trait AuthorizesRoles
{
    /**
     * Check if the current user can create staff
     * Only Owner and Headmaster can add staff
     */
    protected function authorizeStaffCreation(): bool
    {
        $userRole = session('role');

        if (!in_array($userRole, ['Owner', 'owner', 'Headmaster', 'headmaster'])) {
            abort(403, 'Unauthorized. Only Owner and Headmaster can add staff members.');
        }

        return true;
    }

    /**
     * Check if the current user can create students
     * Students can be added by: Headmaster, Academic, Class Teacher, School System Admin
     * Owner CANNOT add students (below pay grade as per requirements)
     */
    protected function authorizeStudentCreation(): bool
    {
        $userRole = session('role');

        // Explicitly block Owner from adding students
        if (in_array($userRole, ['Owner', 'owner'])) {
            abort(403, 'Owners cannot add students. This task should be delegated to academic staff.');
        }

        // Check if user has permission
        if (!in_array($userRole, [
            'Headmaster',
            'headmaster',
            'Academic',
            'academic',
            'ClassTeacher',
            'class_teacher',
            'school_system_admin'
        ])) {
            abort(403, 'Unauthorized. Only Headmaster, Academic, Class Teacher, or School System Admin can add students.');
        }

        return true;
    }

    /**
     * Check if the current user can update staff
     */
    protected function authorizeStaffUpdate(): bool
    {
        $userRole = session('role');

        if (!in_array($userRole, ['Owner', 'owner', 'Headmaster', 'headmaster'])) {
            abort(403, 'Unauthorized. Only Owner and Headmaster can modify staff members.');
        }

        return true;
    }

    /**
     * Check if the current user can delete staff
     */
    protected function authorizeStaffDeletion(): bool
    {
        $userRole = session('role');

        if (!in_array($userRole, ['Owner', 'owner', 'Headmaster', 'headmaster'])) {
            abort(403, 'Unauthorized. Only Owner and Headmaster can delete staff members.');
        }

        return true;
    }

    /**
     * Check if user has one of the specified roles
     */
    protected function authorizeRoles(array $allowedRoles, string $message = 'Unauthorized access.'): bool
    {
        $userRole = session('role');

        if (!in_array($userRole, $allowedRoles)) {
            abort(403, $message);
        }

        return true;
    }
}
