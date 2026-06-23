<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogModal extends Model
{
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
    return $this->belongsTo(User::class, 'user_id');
  }

  public function school()
  {
    return $this->belongsTo(School::class, 'school_id');
  }
}
