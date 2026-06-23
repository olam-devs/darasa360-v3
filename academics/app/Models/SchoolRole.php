<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SchoolRole extends Model
{
    use HasFactory;

    protected $table = 'school_roles'; // the renamed tenant roles table

    protected $connection = 'tenant'; // use tenant DB connection

    protected $fillable = ['name'];
}
