<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherNote extends Model
{
  protected $connection = 'tenant';

  protected $table = 'teacher_notes';

  protected $fillable = [
    'class_id',
    'subject_id',
    'document_path',
    'description',
    'original_name',
    'uploaded_by',
  ];

  /**
   * Get the class related to this note.
   */
  public function class()
  {
    return $this->belongsTo(Classroom::class, 'class_id');
  }

  /**
   * Get the subject related to this note.
   */
  public function subject()
  {
    return $this->belongsTo(Subject::class, 'subject_id');
  }
}
