<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{

    protected $connection = 'tenant';
    protected $table = 'payments';
    protected $fillable = [
        'student_id', 'fee_id', 'amount_paid', 'payment_date', 'method'
    ];

    // Belongs to a student
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    // Belongs to a fee
    public function fee()
    {
        return $this->belongsTo(Fee::class, 'fee_id');
    }
}
