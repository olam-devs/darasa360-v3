<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
    ];

    /**
     * Get the schools in this location.
     */
    public function schools()
    {
        return $this->hasMany(School::class);
    }
}
