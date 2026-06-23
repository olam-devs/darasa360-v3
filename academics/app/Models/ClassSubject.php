<?php

// app/Models/ClassSubject.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassSubject extends Model
{
    protected $table = 'classroom_subject'; // custom pivot table
    protected $fillable = ['classroom_id', 'subject_id'];
    protected $connection = 'tenant';

    public function classroom()
    {
        return $this->belongsTo(Classroom::class, 'classroom_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

}
