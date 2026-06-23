<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetableDocument extends Model
{
  protected $fillable = [
    'class_id',
    'type',
    'document_path',
    'original_name',
    'description',

  ];
  protected $connection = 'tenant';
  public function class()
  {
    return $this->belongsTo(Classroom::class, 'class_id');
  }
}
