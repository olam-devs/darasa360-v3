<?php

namespace App\Models\Platform;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PlatformSuperAdmin extends Authenticatable
{
    use Notifiable;

    protected $connection = 'platform';
    protected $table = 'platform_super_admins';

    protected $fillable = [
        'name', 'email', 'password', 'master_password', 'is_active',
    ];

    protected $hidden = [
        'password', 'master_password', 'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'password' => 'hashed',
        'master_password' => 'hashed',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
