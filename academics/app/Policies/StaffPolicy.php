<?php

namespace App\Policies;

use App\Models\SchoolUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class StaffPolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can create staff members
     * Only Owner and Headmaster can add staff
     */
    public function create(SchoolUser $user): bool
    {
        $roleName = $user->role->name ?? null;

        return in_array($roleName, ['Owner', 'owner', 'Headmaster', 'headmaster']);
    }

    /**
     * Determine if the user can update staff members
     * Only Owner and Headmaster can update staff
     */
    public function update(SchoolUser $user): bool
    {
        $roleName = $user->role->name ?? null;

        return in_array($roleName, ['Owner', 'owner', 'Headmaster', 'headmaster']);
    }

    /**
     * Determine if the user can delete staff members
     * Only Owner and Headmaster can delete staff
     */
    public function delete(SchoolUser $user): bool
    {
        $roleName = $user->role->name ?? null;

        return in_array($roleName, ['Owner', 'owner', 'Headmaster', 'headmaster']);
    }

    /**
     * Determine if the user can view staff members
     * Owner, Headmaster, and Academic can view staff
     */
    public function view(SchoolUser $user): bool
    {
        $roleName = $user->role->name ?? null;

        return in_array($roleName, ['Owner', 'owner', 'Headmaster', 'headmaster', 'Academic', 'academic']);
    }
}
