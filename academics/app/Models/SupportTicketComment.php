<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicketComment extends Model
{
  use HasFactory;

  protected $connection = 'tenant';

  protected $fillable = [
    'ticket_id',
    'user_id',
    'comment',
    'is_internal',
  ];

  protected $casts = [
    'is_internal' => 'boolean',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * Get the ticket this comment belongs to
   */
  public function ticket(): BelongsTo
  {
    return $this->belongsTo(SupportTicket::class, 'ticket_id');
  }

  /**
   * Get the user who made the comment
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(SchoolUser::class, 'user_id');
  }
}
