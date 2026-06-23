<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SmsLog extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'student_id',
        'sent_by',
        'recipient_phone',
        'message',
        'message_id',
        'reference',
        'status',
        'status_code',
        'status_description',
        'sent_at',
        'delivered_at',
        'sms_count',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function sentBy()
    {
        return $this->sender();
    }
}
