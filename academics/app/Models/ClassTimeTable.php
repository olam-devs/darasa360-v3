<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassTimeTable extends Model
{
  use HasFactory;

  protected $fillable = ['class_id', 'day_of_week', 'start_time', 'end_time', 'subject_id', 'teacher_id'];

  public function class()
  {
    return $this->belongsTo(Classroom::class, 'class_id');
  }

  public function subject()
  {
    return $this->belongsTo(Subject::class);
  }

  public function teacher()
  {
    return $this->belongsTo(Teacher::class);
  }
}
