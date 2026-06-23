<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsMessage extends Model
{
    protected $connection = 'tenant';
    // If your table name differs, specify it here:
    protected $table = 'sms_messages';


    // Mass assignable fields
    protected $fillable = [
        'student_id',
        'sender_id',
        'phone_number',
        'message',
        'status',
        'class_id',
    ];

    /**
     * The student who received the SMS (nullable).
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    /**
     * The sender user who sent the SMS (nullable).
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(SchoolUser::class, 'sender_id');
    }

    /**
     * The class related to this SMS (nullable).
     */
    public function class(): BelongsTo
    {
        return $this->belongsTo(Classroom::class, 'class_id');
    }

    /**
     * Scope to filter only sent messages.
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope to filter only failed messages.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
