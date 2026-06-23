<?php

// app/Models/Teacher.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Teacher extends Model
{
  protected $fillable = ['name', 'phone', 'user_id'];
  protected $connection = 'tenant';
  public function classes(): BelongsToMany
  {
    return $this->belongsToMany(ClassModel::class, 'class_teacher', 'teacher_id', 'class_id');
  }

  public function subjects(): BelongsToMany
  {
    return $this->belongsToMany(Subject::class, 'subject_teacher', 'teacher_id', 'subject_id')
      ->withPivot('class_id')
      ->withTimestamps();
  }


  public function user(): BelongsTo
  {
    return $this->belongsTo(SchoolUser::class, 'user_id');
  }

  public function classIds()
  {
    return $this->classes()->pluck('classes.id')->toArray();
  }
}
