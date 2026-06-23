<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class PlatformStudent extends Model
{
    protected $connection = 'platform';
    protected $table = 'platform_students';

    protected $fillable = [
        'school_id', 'student_reg_no',
        'first_name', 'middle_name', 'last_name',
        'gender', 'date_of_birth',
        'parent_name', 'parent_phone', 'parent_email',
        'platform_class_id', 'status',
        'synced_finance', 'synced_academics',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'synced_finance' => 'boolean',
        'synced_academics' => 'boolean',
    ];

    public function school()
    {
        return $this->belongsTo(PlatformSchool::class, 'school_id');
    }

    public function platformClass()
    {
        return $this->belongsTo(PlatformClass::class, 'platform_class_id');
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->middle_name} {$this->last_name}");
    }
}
