<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suggestion extends Model
{
  use HasFactory;
  protected $connection = 'tenant';
  protected $fillable = [
    'sender_id',
    'destination_role_id',
    'class_id',
    'subject_id',
    'receiver_id',
    'content',
    'status',
  ];

  /**
   * Get the sender user
   */
  public function sender(): BelongsTo
  {
    return $this->belongsTo(SchoolUser::class, 'sender_id');
  }

  /**
   * Get the receiver user
   */
  public function receiver(): BelongsTo
  {
    return $this->belongsTo(SchoolUser::class, 'receiver_id');
  }

  /**
   * Get the destination role
   */
  public function destinationRole(): BelongsTo
  {
    return $this->belongsTo(SchoolRole::class, 'destination_role_id');
  }

  /**
   * Get the class
   */
  public function class(): BelongsTo
  {
    return $this->belongsTo(Classroom::class, 'class_id');
  }

  /**
   * Get the subject
   */
  public function subject(): BelongsTo
  {
    return $this->belongsTo(Subject::class, 'subject_id');
  }

  public function replier(): BelongsTo
  {
    return $this->belongsTo(SchoolUser::class, 'replied_by');
  }
}
