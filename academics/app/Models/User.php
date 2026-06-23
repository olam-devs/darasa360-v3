<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
  use HasFactory, Notifiable;

  protected $table = 'users';
  protected $fillable = ['username', 'password', 'role_id', 'phone_number', 'email', 'registration_no'];

  protected $hidden = ['password'];


  public function role_name()
  {
    return $this->belongsTo(Role::class, 'role_id');
  }

  public function userRole()
  {
    return $this->belongsTo(Role::class, 'role_id');
  }

  public function school()
  {
    return $this->belongsTo(School::class);
  }

  public function schoolAdmins()
  {
    return $this->hasMany(SchoolAdmin::class, 'system_admin_id');
  }
}
