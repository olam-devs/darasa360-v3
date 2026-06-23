<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportCardTemplate extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'report_card_templates';

    protected $fillable = [
        'name',
        'header_content',
        'footer_content',
        'layout_settings',
        'is_default',
    ];

    protected $casts = [
        'layout_settings' => 'array',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Get default template
    public static function getDefaultTemplate()
    {
        return self::where('is_default', true)->first();
    }

    // Replace placeholders with actual data
    public function replacePlaceholders($content, $data)
    {
        $placeholders = [
            '{student_name}' => $data['student_name'] ?? '',
            '{student_reg_no}' => $data['student_reg_no'] ?? '',
            '{class_name}' => $data['class_name'] ?? '',
            '{term}' => $data['term'] ?? '',
            '{academic_year}' => $data['academic_year'] ?? '',
            '{school_name}' => $data['school_name'] ?? '',
            '{school_address}' => $data['school_address'] ?? '',
            '{school_phone}' => $data['school_phone'] ?? '',
            '{school_email}' => $data['school_email'] ?? '',
            '{date}' => now()->format('d F Y'),
            '{total_marks}' => $data['total_marks'] ?? '',
            '{average_marks}' => $data['average_marks'] ?? '',
            '{grade}' => $data['grade'] ?? '',
            '{position}' => $data['position'] ?? '',
            '{total_students}' => $data['total_students'] ?? '',
        ];

        return str_replace(array_keys($placeholders), array_values($placeholders), $content);
    }
}
