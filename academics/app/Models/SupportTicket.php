<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
  use HasFactory;

  protected $connection = 'tenant';

  protected $fillable = [
    'ticket_number',
    'subject',
    'module_id',
    'sub_module',
    'description',
    'user_id',
    'assigned_to',
    'status',
    'priority',
    'resolved_at',
    'resolved_by',
    'resolution_notes',
    'is_escalated',
    'escalation_level',
    'escalated_at',
    'escalated_by',
    'escalated_to',
    'escalation_reason',
    'notify_sms',
    'notify_email',
  ];

  protected $casts = [
    'resolved_at' => 'datetime',
    'escalated_at' => 'datetime',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
    'is_escalated' => 'boolean',
    'notify_sms' => 'boolean',
    'notify_email' => 'boolean',
  ];

  /**
   * Get the user who created the ticket
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(SchoolUser::class, 'user_id');
  }

  /**
   * Get the user assigned to handle the ticket
   */
  public function assignedTo(): BelongsTo
  {
    return $this->belongsTo(SchoolUser::class, 'assigned_to');
  }

  /**
   * Get the user who resolved the ticket
   */
  public function resolvedBy(): BelongsTo
  {
    return $this->belongsTo(SchoolUser::class, 'resolved_by');
  }

  /**
   * Get the module category
   */
  public function module(): BelongsTo
  {
    return $this->belongsTo(SupportModule::class, 'module_id');
  }

  /**
   * Get all comments for this ticket
   */
  public function comments(): HasMany
  {
    return $this->hasMany(SupportTicketComment::class, 'ticket_id');
  }

  /**
   * Get the user who escalated the ticket
   */
  public function escalatedBy(): BelongsTo
  {
    return $this->belongsTo(SchoolUser::class, 'escalated_by');
  }

  /**
   * Get the user to whom the ticket was escalated
   */
  public function escalatedTo(): BelongsTo
  {
    return $this->belongsTo(SchoolUser::class, 'escalated_to');
  }

  /**
   * Generate a unique ticket number
   */
  public static function generateTicketNumber(): string
  {
    $year = date('Y');
    $lastTicket = self::whereYear('created_at', $year)
      ->orderBy('id', 'desc')
      ->first();

    $number = $lastTicket ? intval(substr($lastTicket->ticket_number, -4)) + 1 : 1;

    return 'TKT-' . $year . '-' . str_pad($number, 4, '0', STR_PAD_LEFT);
  }

  /**
   * Get status badge color
   */
  public function getStatusColorAttribute(): string
  {
    return match ($this->status) {
      'open' => 'primary',
      'in_progress' => 'warning',
      'pending' => 'info',
      'resolved' => 'success',
      'closed' => 'secondary',
      default => 'secondary'
    };
  }

  /**
   * Get priority badge color
   */
  public function getPriorityColorAttribute(): string
  {
    return match ($this->priority) {
      'low' => 'info',
      'medium' => 'warning',
      'high' => 'danger',
      'critical' => 'dark',
      default => 'secondary'
    };
  }
}
