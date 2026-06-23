<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class PlatformRegSequence extends Model
{
    protected $connection = 'platform';
    protected $table = 'platform_reg_sequences';

    protected $fillable = [
        'school_id', 'role_digit', 'last_sequence',
    ];
}
