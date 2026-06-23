<?php

namespace App\Models\Platform;

use Illuminate\Foundation\Auth\User as Authenticatable;

class PlatformSchoolAdmin extends Authenticatable
{
    protected $connection = 'platform';
    protected $table = 'platform_school_admins';

    protected $fillable = [
        'school_id', 'name', 'email', 'username', 'password',
        'role', 'systems', 'reg_no', 'is_active',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(PlatformSchool::class, 'school_id');
    }

    public function hasSystem(string $system): bool
    {
        return $this->systems === 'both' || $this->systems === $system;
    }
}
