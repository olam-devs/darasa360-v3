<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class AcademicYear extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_current',
        'is_active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the current academic year
     */
    public static function current()
    {
        return static::where('is_current', true)->first();
    }

    /**
     * Get all active academic years ordered by start date (oldest first)
     */
    public static function activeYears()
    {
        return static::where('is_active', true)
            ->orderBy('start_date', 'asc')
            ->get();
    }

    /**
     * Set this academic year as current
     */
    public function setAsCurrent()
    {
        // Remove current flag from all other years
        static::where('is_current', true)->update(['is_current' => false]);

        // Set this as current
        $this->is_current = true;
        $this->save();
    }

    /**
     * Get fee assignments for this academic year
     */
    public function feeAssignments()
    {
        return $this->hasMany(Pivot::class, 'academic_year_id');
    }

    /**
     * Format the academic year display name
     */
    public function getDisplayNameAttribute()
    {
        return $this->name.($this->is_current ? ' (Current)' : '');
    }
}
