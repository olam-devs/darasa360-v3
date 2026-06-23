<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
{
  use HasFactory;

  protected $fillable = [
    'name',
    'location_id',
    'database_url',
    'package',
    'user_id',
    'is_deleted',
    'status',
    'school_code',
    'message_name',
    'logo',
    'price_per_user',
    'monthly_charge',
    'billing_status',
    'billing_start_date',
    'next_billing_date',
    'total_users',
    'active_users'
  ];


  public function administrator()
  {
    return $this->belongsTo(User::class, 'user_id');
  }

  public function location()
  {
    return $this->belongsTo(Location::class);
  }

  public function users()
  {
    return $this->hasMany(User::class, 'school_id');  // All users in the school
  }

  public function smsBalance()
  {
    return $this->hasOne(SmsBalance::class);
  }

  /**
   * Get system admins assigned to this school
   */
  public function systemAdmins()
  {
    return $this->hasMany(SchoolAdmin::class)->where('admin_type', 'system_admin');
  }

  /**
   * Get school system admins for this school
   */
  public function schoolSystemAdmins()
  {
    return $this->hasMany(SchoolAdmin::class)->where('admin_type', 'school_system_admin');
  }

  /**
   * Get all admins (both types)
   */
  public function allAdmins()
  {
    return $this->hasMany(SchoolAdmin::class);
  }
}
