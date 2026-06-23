<?php

// app/Models/Subject.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Subject extends Model
{
  protected $fillable = ['name', 'code'];
  protected $connection = 'tenant';
  protected $table = 'subjects';

  public function classes()
  {
    return $this->belongsToMany(Classroom::class, 'classroom_subject', 'subject_id', 'classroom_id');
  }

  public function teachers(): BelongsToMany
  {
    return $this->belongsToMany(Teacher::class, 'subject_teacher', 'subject_id', 'teacher_id');
  }
}
