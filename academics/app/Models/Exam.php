<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
  protected $connection = 'tenant';
  protected $table = 'exams';

  protected $fillable = [
    'name',
    'term',
    'start_date',
    'end_date',
  ];

  public function marks()
  {
    return $this->hasMany(ExamMark::class);
  }

  public function classes()
  {
    return $this->belongsToMany(Classroom::class, 'class_exam', 'exam_id', 'class_id');
  }
}
