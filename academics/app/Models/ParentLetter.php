<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParentLetter extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'parent_letters';

    protected $fillable = [
        'report_card_config_id',
        'letter_title',
        'closing_semester_message',
        'upcoming_semester_message',
        'fee_payment_notice',
        'special_announcements',
        'safari_trips',
        'other_information',
        'signature_name',
        'signature_title',
        'created_by'
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

    public function creator()
    {
        return $this->belongsTo(SchoolUser::class, 'created_by');
    }

    // Helper methods
    public function getFullLetterContent()
    {
        $content = [];

        if ($this->closing_semester_message) {
            $content[] = [
                'title' => 'Closing Semester',
                'content' => $this->closing_semester_message
            ];
        }

        if ($this->upcoming_semester_message) {
            $content[] = [
                'title' => 'Upcoming Semester',
                'content' => $this->upcoming_semester_message
            ];
        }

        if ($this->fee_payment_notice) {
            $content[] = [
                'title' => 'Fee Payment Information',
                'content' => $this->fee_payment_notice
            ];
        }

        if ($this->special_announcements) {
            $content[] = [
                'title' => 'Special Announcements',
                'content' => $this->special_announcements
            ];
        }

        if ($this->safari_trips) {
            $content[] = [
                'title' => 'Safari Trips & Excursions',
                'content' => $this->safari_trips
            ];
        }

        if ($this->other_information) {
            $content[] = [
                'title' => 'Additional Information',
                'content' => $this->other_information
            ];
        }

        return $content;
    }

    public function hasContent()
    {
        return !empty($this->closing_semester_message) ||
               !empty($this->upcoming_semester_message) ||
               !empty($this->fee_payment_notice) ||
               !empty($this->special_announcements) ||
               !empty($this->safari_trips) ||
               !empty($this->other_information);
    }
}
