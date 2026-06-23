<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCard extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'report_cards';

    protected $fillable = [
        'report_card_config_id',
        'student_id',
        'template_id',
        'total_marks',
        'average_marks',
        'overall_grade',
        'class_position',
        'total_students_in_class',
        'exam_breakdown',
        'subject_performance',
        'status',
        'published_at',
        'pdf_generated',
        'pdf_path',
        'email_sent_at',
        'sms_sent_at',
        'parent_viewed_at',
        'download_count',
    ];

    protected $casts = [
        'total_marks' => 'decimal:2',
        'average_marks' => 'decimal:2',
        'exam_breakdown' => 'array',
        'subject_performance' => 'array',
        'pdf_generated' => 'boolean',
        'published_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'sms_sent_at' => 'datetime',
        'parent_viewed_at' => 'datetime',
        'download_count' => 'integer',
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

    public function template()
    {
        return $this->belongsTo(ReportCardTemplate::class, 'template_id');
    }

    public function comments()
    {
        return $this->hasMany(ReportCardComment::class, 'report_card_config_id')
            ->where('student_id', $this->student_id);
    }

    // Helper methods
    public function publish()
    {
        $this->status = 'published';
        $this->published_at = now();
        $this->save();
    }

    public function isPublished()
    {
        return $this->status === 'published';
    }

    public function getGradeAttribute()
    {
        return $this->overall_grade;
    }

    public function incrementDownloadCount()
    {
        $this->increment('download_count');
        if (!$this->parent_viewed_at) {
            $this->parent_viewed_at = now();
            $this->save();
        }
    }

    public function markEmailSent()
    {
        $this->email_sent_at = now();
        $this->save();
    }

    public function markSmsSent()
    {
        $this->sms_sent_at = now();
        $this->save();
    }

    public function isPdfGenerated()
    {
        return $this->pdf_generated && !empty($this->pdf_path);
    }

    public function getPdfUrl()
    {
        if ($this->pdf_path) {
            return asset('storage/' . $this->pdf_path);
        }
        return null;
    }
}
