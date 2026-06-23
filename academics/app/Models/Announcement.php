<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
  use HasFactory;
  protected $connection = 'tenant';
  protected $table = 'announcements';
  protected $fillable = [
    'title',
    'content',
    'filepath',
    'created_by'
  ];

  public function classes()
  {
    return $this->belongsToMany(Classroom::class, 'announcement_class', 'announcement_id', 'class_id');
  }


  public function creator()
  {
    return $this->belongsTo(User::class, 'created_by');
  }
}
