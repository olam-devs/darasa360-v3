<?php

namespace App\Models;

use App\Models\Classroom;
use App\Models\Exam;
use App\Models\Student;
use Illuminate\Database\Eloquent\Model;

class ExamResult extends Model
{
  protected $connection = 'tenant'; // important for tenant DB
  protected $table = 'exam_results';

  protected $fillable = [
    'class_id',
    'exam_id',
    'student_id',
    'student_name',
    'total_marks',
    'average_marks',
    'final_grade',
    'grading_type',
    'subjects',
    'sent_to_headteacher',
    'sent_to_class_teacher',
    'sent_to_student',
    'is_verified',
    'sent_to'
  ];

  protected $casts = [
    'subjects' => 'array',  // auto-convert JSON to array
    'sent_to_headteacher' => 'boolean',
    'sent_to_class_teacher' => 'boolean',
    'sent_to_student' => 'boolean',
  ];

  // Optional relationships
  public function student()
  {
    return $this->belongsTo(Student::class, 'student_id');
  }

  public function class()
  {
    return $this->belongsTo(Classroom::class, 'class_id');
  }

  public function exam()
  {
    return $this->belongsTo(Exam::class, 'exam_id');
  }
}
