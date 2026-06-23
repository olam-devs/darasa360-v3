<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class PlatformCrossAccess extends Model
{
    protected $connection = 'platform';
    protected $table = 'platform_cross_access';

    protected $fillable = [
        'school_id', 'user_ref', 'role', 'target_system', 'level', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(PlatformSchool::class, 'school_id');
    }
}
