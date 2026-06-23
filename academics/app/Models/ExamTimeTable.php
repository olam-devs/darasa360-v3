<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamTimeTable extends Model
{
  use HasFactory;

  protected $fillable = ['class_id', 'exam_name', 'exam_date', 'start_time', 'end_time', 'subject_id'];

  public function class()
  {
    return $this->belongsTo(Classroom::class, 'class_id');
  }

  public function subject()
  {
    return $this->belongsTo(Subject::class);
  }
}
