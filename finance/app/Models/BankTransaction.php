<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankTransaction extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'transaction_id',
        'control_number',
        'transaction_date',
        'bank_name',
        'account_number',
        'amount',
        'type',
        'reference',
        'description',
        'payer_name',
        'payer_phone',
        'is_reconciled',
        'processing_status',
        'processing_notes',
        'sms_sent',
        'payment_id',
        'student_id',
        'book_id',
        'voucher_id',
        'suspense_account_id',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'is_reconciled' => 'boolean',
        'sms_sent' => 'boolean',
    ];

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function suspenseAccount()
    {
        return $this->belongsTo(SuspenseAccount::class);
    }
}
