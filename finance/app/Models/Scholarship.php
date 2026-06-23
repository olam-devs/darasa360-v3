<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Scholarship extends BaseModel
{
    protected $fillable = [
        'student_id',
        'particular_id',
        'academic_year_id',
        'original_amount',
        'forgiven_amount',
        'remaining_amount',
        'scholarship_type',
        'scholarship_name',
        'notes',
        'applied_date',
        'applied_by',
        'is_active',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'forgiven_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'applied_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the student this scholarship belongs to
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Get the particular (fee type) this scholarship applies to
     */
    public function particular(): BelongsTo
    {
        return $this->belongsTo(Particular::class);
    }

    /**
     * Get the academic year this scholarship applies to
     */
    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    /**
     * Get the user who applied this scholarship
     */
    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    /**
     * Check if this is a full scholarship
     */
    public function isFullScholarship(): bool
    {
        return $this->scholarship_type === 'full';
    }

    /**
     * Check if this is a partial scholarship
     */
    public function isPartialScholarship(): bool
    {
        return $this->scholarship_type === 'partial';
    }

    /**
     * Get percentage of fee forgiven
     */
    public function getForgivenPercentageAttribute(): float
    {
        if ($this->original_amount <= 0) {
            return 0;
        }

        return round(($this->forgiven_amount / $this->original_amount) * 100, 2);
    }

    /**
     * Scope for active scholarships
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for scholarships in a specific academic year
     */
    public function scopeForAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }
}
