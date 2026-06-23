<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamMark extends Model
{
  protected $connection = 'tenant';
  protected $table = 'exams_marks';

  protected $fillable = [
    'exam_id',
    'class_id',
    'student_id',
    'subject_id',
    'marks',
    'teacher_comment',
    'submitted_by_teacher',
    'verified_by_academic'
  ];
  public function exam()
  {
    return $this->belongsTo(Exam::class);
  }

  public function student()
  {
    return $this->belongsTo(Student::class);
  }

  public function subject()
  {
    return $this->belongsTo(Subject::class);
  }

  public function class()
  {
    return $this->belongsTo(Classroom::class);
  }
}
