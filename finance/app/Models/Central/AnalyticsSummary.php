<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalyticsSummary extends Model
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'central';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'analytics_summary';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'date',
        'total_students',
        'total_fees_expected',
        'total_fees_collected',
        'collection_rate',
        'active_parents',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total_students' => 'integer',
            'total_fees_expected' => 'decimal:2',
            'total_fees_collected' => 'decimal:2',
            'collection_rate' => 'decimal:2',
            'active_parents' => 'integer',
        ];
    }

    /**
     * Get the school associated with the analytics.
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Scope a query to only include analytics for a specific school.
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope a query to only include analytics for a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope a query to get the latest analytics.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('date', 'desc');
    }
}
