<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubjectPerformance extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'subject_performance';

    protected $fillable = [
        'subject_id',
        'class_id',
        'exam_id',
        'average_score',
        'total_students',
        'students_passed',
        'students_failed',
        'pass_rate',
        'class_rank',
        'school_rank',
        'highest_score',
        'lowest_score',
        'grade_distribution',
    ];

    protected $casts = [
        'average_score' => 'decimal:2',
        'pass_rate' => 'decimal:2',
        'highest_score' => 'decimal:2',
        'lowest_score' => 'decimal:2',
        'grade_distribution' => 'array',
    ];

    /**
     * Get the subject
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * Get the class
     */
    public function classModel(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    /**
     * Get the exam
     */
    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
