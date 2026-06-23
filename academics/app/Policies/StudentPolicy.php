<?php

namespace App\Policies;

use App\Models\SchoolUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class StudentPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can create students
     * Students can be added by: Headmaster, Academic, Class Teacher, School System Admin
     * Owner CANNOT add students (below pay grade as per requirements)
     */
    public function create(SchoolUser $user): bool
    {
        $roleName = $user->role->name ?? null;

        // Explicitly exclude Owner
        if (in_array($roleName, ['Owner', 'owner'])) {
            return false;
        }

        // Allow these roles to add students
        return in_array($roleName, [
            'Headmaster',
            'headmaster',
            'Academic',
            'academic',
            'ClassTeacher',
            'class_teacher',
            'school_system_admin'
        ]);
    }

    /**
     * Determine if the user can update students
     * Same roles as create, plus Owner can view but not add
     */
    public function update(SchoolUser $user): bool
    {
        $roleName = $user->role->name ?? null;

        return in_array($roleName, [
            'Owner',
            'owner',
            'Headmaster',
            'headmaster',
            'Academic',
            'academic',
            'ClassTeacher',
            'class_teacher',
            'school_system_admin'
        ]);
    }

    /**
     * Determine if the user can delete students
     * Only Headmaster and Owner can delete
     */
    public function delete(SchoolUser $user): bool
    {
        $roleName = $user->role->name ?? null;

        return in_array($roleName, [
            'Owner',
            'owner',
            'Headmaster',
            'headmaster'
        ]);
    }

    /**
     * Determine if the user can view students
     * Most roles can view students
     */
    public function view(SchoolUser $user): bool
    {
        $roleName = $user->role->name ?? null;

        return in_array($roleName, [
            'Owner',
            'owner',
            'Headmaster',
            'headmaster',
            'Academic',
            'academic',
            'ClassTeacher',
            'class_teacher',
            'Teacher',
            'subject_teacher',
            'Accountant',
            'accountant',
            'school_system_admin'
        ]);
    }
}
