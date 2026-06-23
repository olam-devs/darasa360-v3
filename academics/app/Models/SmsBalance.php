<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SmsBalance extends Model
{
  use HasFactory;

  protected $fillable = [
    'school_id',
    'sms_allocated',
    'sms_used',
  ];
  protected $table = 'sms_balances';

  // Relationships
  public function school()
  {
    return $this->belongsTo(School::class, 'school_id');
  }

  // Accessor: get remaining SMS
  public function getRemainingAttribute()
  {
    return $this->sms_allocated - $this->sms_used;
  }
}
