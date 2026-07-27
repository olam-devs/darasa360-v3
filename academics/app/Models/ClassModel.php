<?php

// app/Models/ClassModel.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ClassModel extends Model
{
  protected $connection = 'tenant';

  protected $table = 'classes'; // important if your model isn't named `Class`

  public function teachers(): BelongsToMany
  {
    return $this->belongsToMany(Teacher::class, 'class_teacher', 'class_id', 'teacher_id');
  }
}
