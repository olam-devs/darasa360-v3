<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCardExamConfig extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'report_card_exam_configs';

    protected $fillable = [
        'report_card_config_id',
        'exam_id',
        'percentage_weight',
        'order',
    ];

    protected $casts = [
        'percentage_weight' => 'decimal:2',
        'order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function configuration()
    {
        return $this->belongsTo(ReportCardConfiguration::class, 'report_card_config_id');
    }

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }
}
