<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Fee extends Model
{

    protected $connection = 'tenant';
    protected $table = 'fees';
    protected $fillable = [
        'class_id', 'name', 'amount', 'due_date'
    ];

    // A fee belongs to a class
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function studentFees()
    {
        return $this->hasMany(StudentFee::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function classes()
{
    return $this->belongsToMany(ClassModel::class, 'fee_class', 'fee_id', 'class_id');
}


}
