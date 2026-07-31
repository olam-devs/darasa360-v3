<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class SmsDeliveryIndex extends Model
{
    protected $connection = 'central';

    public $timestamps = false;

    protected $fillable = [
        'message_id',
        'school_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
