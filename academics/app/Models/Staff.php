<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $table = 'staffs';  // verify if your table is actually 'staffs' or 'staff'

    protected $connection = 'tenant';

    protected $fillable = [
        'registration_no',
        'name',
        'email',
        'phone',
        'role_id',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
    ];

    public function role()
    {
        return $this->belongsTo(SchoolRole::class, 'role_id'); // use SchoolRole here
    }
}
