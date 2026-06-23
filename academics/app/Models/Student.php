<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{

  use HasFactory;

  protected $connection = 'tenant';
  protected $table = 'students';
  protected $fillable = [
    'user_id',
    'registration_no',
    'photo',
    'first_name',
    'middle_name',
    'last_name',
    'gender',
    'date_of_birth',
    'phone_number',
    'email',
    'stream_id',
    'address',
    'class_id',
    'comments',
    'parent_name',
    'parent_phone',
    'parent_email',
    'parent_address'
  ];

  public function stream()
  {
    return $this->belongsTo(Stream::class);
  }

  public function user()
  {
    return $this->belongsTo(SchoolUser::class, 'user_id');
  }

  public function class()
  {
    return $this->belongsTo(Classroom::class, 'class_id');
  }
}
