<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantLog extends Model
{
  protected $connection = 'tenant';
  protected $table = 'logs';
  protected $fillable = [
    'user_id',
    'level',
    'method',
    'url',
    'ip',
    'message',
    'context',
    'user_name',
    'registration_no',
    'role',
    'school_code',
    'school_name'
  ];

  protected $casts = [
    'context' => 'array',
  ];

  public function user()
  {
    return $this->belongsTo(SchoolUser::class, 'user_id');
  }
}
