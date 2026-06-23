<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
  protected $connection = 'tenant';
  protected $fillable = [
    'class_id',
    'stream_id',
    'from_date',
    'to_date',
    'title',
    'created_by'
  ];

  protected $table = 'attendances';
  public function class()
  {
    return $this->belongsTo(ClassModel::class, 'class_id');
  }

  public function stream()
  {
    return $this->belongsTo(Stream::class, 'stream_id');
  }

  public function createdBy()
  {
    return $this->belongsTo(SchoolUser::class, 'created_by');
  }
}
