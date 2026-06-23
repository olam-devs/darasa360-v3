<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolEvent extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'calendar_id',
        'title',
        'description',
        'event_type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'created_by',
        'notify_staff',
        'notify_students',
        'notify_parents',
        'reminder_days_before',
        'is_recurring',
        'recurrence_pattern',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'notify_staff' => 'boolean',
        'notify_students' => 'boolean',
        'notify_parents' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    /**
     * Get the calendar this event belongs to
     */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(SchoolCalendar::class, 'calendar_id');
    }

    /**
     * Get the user who created this event
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    /**
     * Check if event is upcoming
     */
    public function isUpcoming(): bool
    {
        return $this->start_date >= now()->toDateString();
    }

    /**
     * Get days until event
     */
    public function daysUntil(): int
    {
        return now()->diffInDays($this->start_date, false);
    }
}
