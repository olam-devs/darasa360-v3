<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentFee extends Model
{
    protected $connection = 'tenant';
    protected $table = 'student_fees';
    protected $fillable = [
        'student_id', 'fee_id', 'amount', 'status','is_verified'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function fee()
    {
        return $this->belongsTo(Fee::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'fee_id', 'fee_id')
                    ->where('student_id', $this->student_id);
    }

    public function totalPaid()
    {
        return $this->payments()->sum('amount_paid');
    }

    public function balance()
    {
        return $this->amount - $this->totalPaid();
    }

  // Payment Status
    public function paymentStatus()
    {
        if ($this->balance() <= 0) return 'Paid';
        elseif ($this->totalPaid() > 0) return 'Partial';
        else return 'Pending';
    }
}
