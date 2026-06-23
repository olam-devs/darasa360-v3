<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $connection = 'tenant';
    protected $table = 'grades';

    protected $fillable = [
        'name',
        'type',
        'min_mark',
        'max_mark',
    ];
}
