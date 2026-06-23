<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SuspenseAccount extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'amount',
        'resolved_amount',
        'book_id',
        'description',
        'reference_number',
        'date',
        'resolved',
        'resolved_student_id',
        'voucher_id',
        'resolved_at',
        'resolved_by',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'resolved_amount' => 'decimal:2',
        'date' => 'date',
        'resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    // Relationships
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'resolved_student_id');
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function resolvedByUser()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Helper methods
    public function getUnresolvedAmount()
    {
        return $this->amount - $this->resolved_amount;
    }
}
