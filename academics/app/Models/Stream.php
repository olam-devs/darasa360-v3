<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stream extends Model
{
  use HasFactory;

  protected $fillable = ['name', 'class_id'];  // <-- here use class_id
  protected $connection = 'tenant';
  protected $table = 'streams';

  public function classroom()
  {
    return $this->belongsTo(Classroom::class, 'class_id'); // foreign key is class_id
  }

  public function students()
  {
    return $this->hasMany(Student::class);
  }
}
