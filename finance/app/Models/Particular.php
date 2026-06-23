<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Particular extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'book_ids',
        'class_names',
        'is_active',
    ];

    protected $casts = [
        'book_ids' => 'array',
        'class_names' => 'array',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function students()
    {
        return $this->belongsToMany(Student::class, 'particular_student')
            ->withPivot('sales', 'debit', 'credit', 'overpayment', 'deadline', 'academic_year_id')
            ->withTimestamps();
    }

    /**
     * Get students with their fee assignments for a specific academic year
     */
    public function studentsForAcademicYear($academicYearId)
    {
        return $this->belongsToMany(Student::class, 'particular_student')
            ->withPivot('sales', 'debit', 'credit', 'overpayment', 'deadline', 'academic_year_id')
            ->wherePivot('academic_year_id', $academicYearId)
            ->withTimestamps();
    }

    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    public function books()
    {
        if (! $this->book_ids) {
            return collect();
        }

        return Book::whereIn('id', $this->book_ids)->get();
    }
}
