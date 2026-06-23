<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolCalendar extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'school_calendar';

    protected $fillable = [
        'academic_year',
        'start_date',
        'end_date',
        'term_count',
        'is_active',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get all events for this calendar
     */
    public function events(): HasMany
    {
        return $this->hasMany(SchoolEvent::class, 'calendar_id');
    }

    /**
     * Get upcoming events
     */
    public function upcomingEvents()
    {
        return $this->events()
            ->where('start_date', '>=', now())
            ->orderBy('start_date', 'asc');
    }
}
