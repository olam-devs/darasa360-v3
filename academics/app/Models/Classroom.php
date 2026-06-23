<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
  use HasFactory;

  protected $fillable = ['name', 'description'];
  protected $connection = 'tenant';
  protected $table = 'classes';

  public function streams()
  {
    // Specify foreign key as school_class_id
    return $this->hasMany(Stream::class, 'class_id');
  }


  public function subjects()
  {
    return $this->belongsToMany(Subject::class, 'classroom_subject', 'classroom_id', 'subject_id');
  }

  public function exams()
  {
    return $this->belongsToMany(Exam::class, 'class_exam');
  }

  public function students()
  {
    return $this->hasMany(Student::class, 'class_id');
  }


  public function announcements()
  {
    return $this->belongsToMany(Announcement::class, 'announcement_class');
  }

  public function assignments()
  {
    return $this->hasMany(Assignment::class, 'class_id');
  }

  public function teachers()
  {
    return $this->belongsToMany(Teacher::class, 'class_teacher', 'class_id', 'teacher_id');
  }
}
