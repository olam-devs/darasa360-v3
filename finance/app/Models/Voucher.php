<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Voucher extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'date',
        'student_id',
        'particular_id',
        'book_id',
        'voucher_type',
        'voucher_no',
        'voucher_number',
        'debit',
        'credit',
        'payment_by_receipt_to',
        'notes',
        'created_by',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'date' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function isVoided(): bool
    {
        return ! is_null($this->voided_at);
    }

    // Relationships
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function particular()
    {
        return $this->belongsTo(Particular::class);
    }

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function suspenseAccount()
    {
        return $this->hasOne(SuspenseAccount::class);
    }

    public function expense()
    {
        return $this->hasOne(Expense::class);
    }

    public function payrollEntry()
    {
        return $this->hasOne(PayrollEntry::class);
    }

    public function bankTransaction()
    {
        return $this->hasOne(BankTransaction::class);
    }

    // Auto-generate voucher number
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($voucher) {
            if (! $voucher->voucher_number && ! $voucher->voucher_no) {
                $lastVoucher = static::where('voucher_type', $voucher->voucher_type)
                    ->latest('id')
                    ->first();

                $lastRaw = $lastVoucher?->voucher_number ?: $lastVoucher?->voucher_no;
                $lastNumber = $lastRaw ? (int) substr($lastRaw, -6) : 0;
                $newNumber = $lastNumber + 1;

                $prefix = strtoupper(substr($voucher->voucher_type, 0, 3));
                $generated = $prefix.str_pad($newNumber, 6, '0', STR_PAD_LEFT);
                $voucher->voucher_number = $generated;
                $voucher->voucher_no = $generated;
            } else {
                // Keep the legacy + new column in sync if only one is set.
                if ($voucher->voucher_number && ! $voucher->voucher_no) {
                    $voucher->voucher_no = $voucher->voucher_number;
                }
                if ($voucher->voucher_no && ! $voucher->voucher_number) {
                    $voucher->voucher_number = $voucher->voucher_no;
                }
            }
        });
    }
}
