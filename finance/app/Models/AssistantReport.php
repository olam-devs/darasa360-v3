<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssistantReport extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $fillable = [
        'audience',
        'message',
        'sender_id',
        'sender_name',
        'status',
    ];
}
