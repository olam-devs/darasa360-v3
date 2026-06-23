<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCardConfiguration extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'report_card_configurations';

    protected $fillable = [
        'name',
        'class_id',
        'term',
        'academic_year',
        'status',
        'description',
        'has_parent_letter',
        'auto_fetch_comments',
        'distribution_method',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'has_parent_letter' => 'boolean',
        'auto_fetch_comments' => 'boolean',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function class()
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    public function examConfigs()
    {
        return $this->hasMany(ReportCardExamConfig::class, 'report_card_config_id')->orderBy('order');
    }

    public function comments()
    {
        return $this->hasMany(ReportCardComment::class, 'report_card_config_id');
    }

    public function reportCards()
    {
        return $this->hasMany(ReportCard::class, 'report_card_config_id');
    }

    public function parentLetter()
    {
        return $this->hasOne(ParentLetter::class, 'report_card_config_id');
    }

    public function approver()
    {
        return $this->belongsTo(SchoolUser::class, 'approved_by');
    }

    // Helper methods
    public function getTotalPercentage()
    {
        return $this->examConfigs()->sum('percentage_weight');
    }

    public function isPercentageValid()
    {
        return abs($this->getTotalPercentage() - 100) < 0.01; // Allow for floating point precision
    }
}
