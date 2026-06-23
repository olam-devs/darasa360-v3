<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class PlatformClass extends Model
{
    protected $connection = 'platform';
    protected $table = 'platform_classes';

    protected $fillable = [
        'school_id', 'name', 'level', 'stream',
        'finance_class_id', 'academics_class_id',
        'synced_finance', 'synced_academics',
    ];

    protected $casts = [
        'synced_finance' => 'boolean',
        'synced_academics' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(PlatformSchool::class, 'school_id');
    }

    public function students()
    {
        return $this->hasMany(PlatformStudent::class, 'platform_class_id');
    }
}
