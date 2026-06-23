<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
  protected $connection = 'tenant';

  protected $fillable = [
    'class_id',
    'subject_id',
    'title',
    'description',
    'file_path',
    'uploaded_at',
    'assigned_by'
  ];

  // Relationships
  public function class()
  {
    return $this->belongsTo(Classroom::class, 'class_id');
  }

  public function subject()
  {
    return $this->belongsTo(Subject::class, 'subject_id');
  }
}
