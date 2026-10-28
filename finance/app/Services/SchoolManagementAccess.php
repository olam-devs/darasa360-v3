<?php

namespace App\Services;

use App\Models\Central\School;
use App\Models\Central\SchoolAccountant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchoolManagementAccess
{
    private function directGrants($user): Collection
    {
        if (!$user instanceof SchoolAccountant || !$user->is_active || !$user->is_main_accountant
            || !Schema::connection('central')->hasTable('school_management_access')) {
            return collect();
        }
        // Both ends must remain active main accountants; a stale grant cannot revive access.
        return DB::connection('central')->table('school_management_access as g')
            ->join('school_accountants as a', 'a.id', '=', 'g.school_accountant_id')
            ->join('schools as s', 's.id', '=', 'g.school_id')
            ->where('g.manager_accountant_id', $user->id)
            ->whereColumn('a.school_id', 'g.school_id')
            ->where('a.is_active', true)->where('a.is_main_accountant', true)->where('s.is_active', true)
            ->select('g.school_id', 'g.school_accountant_id')->get();
    }

    public function manager($user): ?SchoolAccountant
    {
        if (!$user instanceof SchoolAccountant || !$user->is_active || !$user->is_main_accountant) return null;
        $managerId = session('school_management_manager_id');
        if (!$managerId || (int)$managerId === $user->id) return $user;
        $manager = SchoolAccountant::find($managerId);
        // Retain the management session only for a school account explicitly delegated to it.
        $allowed = $this->directGrants($manager)->contains(fn($g) => (int)$g->school_accountant_id === $user->id && (int)$g->school_id === (int)$user->school_id);
        return $allowed ? $manager : null;
    }

    public function grants($user): Collection
    {
        return $this->directGrants($this->manager($user));
    }

    public function schools($user): Collection
    {
        return School::whereIn('id', $this->grants($user)->pluck('school_id'))->orderBy('name')->get();
    }

    public function school($user, int $schoolId): School
    {
        abort_unless($this->grants($user)->contains('school_id', $schoolId), 403);
        return School::findOrFail($schoolId);
    }

    /**
     * Schools managed together with this one (same manager in school_management_access), including itself.
     * Used where schools share bank accounts, so an account's ledger covers all of them.
     */
    public function linkedSchools(int $schoolId): Collection
    {
        $ids = collect([$schoolId]);
        if (Schema::connection('central')->hasTable('school_management_access')) {
            $managers = DB::connection('central')->table('school_management_access')->where('school_id', $schoolId)->pluck('manager_accountant_id');
            foreach ($managers as $m) {
                $manager = SchoolAccountant::find($m);
                if ($manager) $ids = $ids->merge($this->directGrants($manager)->pluck('school_id'));
            }
        }
        return School::whereIn('id', $ids->unique())->where('is_active', true)->orderBy('name')->get();
    }
}
