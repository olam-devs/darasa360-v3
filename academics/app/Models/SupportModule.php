<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportModule extends Model
{
  use HasFactory;

  protected $connection = 'tenant';

  protected $fillable = [
    'name',
    'icon',
    'color',
    'is_active',
    'ticket_count',
  ];

  protected $casts = [
    'is_active' => 'boolean',
    'ticket_count' => 'integer',
  ];

  /**
   * Get all tickets in this module
   */
  public function tickets(): HasMany
  {
    return $this->hasMany(SupportTicket::class, 'module_id');
  }

  /**
   * Get active tickets count
   */
  public function getActiveTicketsCountAttribute(): int
  {
    return $this->tickets()
      ->whereIn('status', ['open', 'in_progress', 'pending'])
      ->count();
  }
}
