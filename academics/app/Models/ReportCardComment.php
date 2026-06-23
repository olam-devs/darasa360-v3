<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCardComment extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'report_card_comments';

    protected $fillable = [
        'report_card_config_id',
        'student_id',
        'stakeholder_type',
        'subject_id',
        'comment',
        'commented_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function configuration()
    {
        return $this->belongsTo(ReportCardConfiguration::class, 'report_card_config_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function commentedBy()
    {
        return $this->belongsTo(SchoolUser::class, 'commented_by');
    }

    // Helper method to get comments by stakeholder for a student
    public static function getCommentsForStudent($configId, $studentId)
    {
        return self::where('report_card_config_id', $configId)
            ->where('student_id', $studentId)
            ->with(['subject', 'commentedBy'])
            ->get()
            ->groupBy('stakeholder_type');
    }
}
