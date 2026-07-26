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
    'db_username',
    'db_password',
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

  protected $hidden = [
    'db_password',
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

  /**
   * Build the full 'tenant' database connection config array for this school.
   * Falls back to the shared env credentials only if this school doesn't yet
   * have its own dedicated db_username/db_password (e.g. legacy rows created
   * before per-school credentials existed) - but the shared credential is
   * NOT guaranteed to have access to every tenant database on this host, so
   * that fallback should be treated as "probably broken until backfilled",
   * not a real working default.
   */
  public function tenantConnectionConfig(): array
  {
    return [
      'driver' => 'mysql',
      'host' => env('DB_HOST', '127.0.0.1'),
      'port' => env('DB_PORT', '3306'),
      'database' => $this->database_url,
      'username' => $this->db_username ?: env('DB_USERNAME', 'root'),
      'password' => $this->db_password ?: env('DB_PASSWORD', ''),
      'charset' => 'utf8mb4',
      'collation' => 'utf8mb4_unicode_ci',
      'prefix' => '',
      'strict' => true,
      'engine' => env('DB_ENGINE', 'InnoDB'),
    ];
  }

  /**
   * Point Laravel's 'tenant' connection at this school and reconnect.
   * The one canonical way to switch tenant context - use this instead of
   * hand-rolling Config::set('database.connections.tenant', ...) again.
   */
  public function useAsTenant(): void
  {
    \Illuminate\Support\Facades\Config::set('database.connections.tenant', $this->tenantConnectionConfig());
    \Illuminate\Support\Facades\DB::purge('tenant');
    \Illuminate\Support\Facades\DB::reconnect('tenant');
  }
}
