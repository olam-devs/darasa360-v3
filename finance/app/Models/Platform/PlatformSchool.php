<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class PlatformSchool extends Model
{
    protected $connection = 'platform';
    protected $table = 'platform_schools';

    protected $fillable = [
        'name', 'code', 'slug', 'location', 'status', 'package',
        'has_finance', 'has_academics', 'cross_jump_enabled', 'parent_cross_access',
        'finance_db_name', 'finance_db_host', 'finance_db_port', 'finance_db_user', 'finance_db_pass',
        'academics_db_name',
        'billing_status', 'billing_start_date', 'next_billing_date', 'monthly_charge',
    ];

    protected $casts = [
        'has_finance' => 'boolean',
        'has_academics' => 'boolean',
        'cross_jump_enabled' => 'boolean',
        'parent_cross_access' => 'boolean',
        'billing_start_date' => 'date',
        'next_billing_date' => 'date',
        'monthly_charge' => 'decimal:2',
    ];

    public function students()
    {
        return $this->hasMany(PlatformStudent::class, 'school_id');
    }

    public function classes()
    {
        return $this->hasMany(PlatformClass::class, 'school_id');
    }

    public function admins()
    {
        return $this->hasMany(PlatformSchoolAdmin::class, 'school_id');
    }

    /** Both systems enabled and the master jump switch on. */
    public function canCrossJump(): bool
    {
        return $this->has_finance && $this->has_academics && $this->cross_jump_enabled;
    }
}
