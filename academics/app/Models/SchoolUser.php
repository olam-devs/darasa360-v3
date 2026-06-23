<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;


class SchoolUser extends Model
{
  use HasFactory, Notifiable;

  protected $table = 'schoolUsers';
  protected $fillable = ['username', 'password', 'role_id', 'phone_number', 'email', 'registration_no'];

  protected $hidden = ['password'];
  protected $connection = 'tenant';

  public function role_name()
  {
    return $this->belongsTo(SchoolRole::class, 'role_id');
  }

  // Alias for role_name to match DashboardController expectations
  public function role()
  {
    return $this->belongsTo(SchoolRole::class, 'role_id');
  }

  public function school()
  {
    return $this->belongsTo(School::class);
  }
  public function student()
  {
    return $this->hasOne(Student::class, 'user_id');
  }
}
