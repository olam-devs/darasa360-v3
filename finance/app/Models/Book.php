<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Book extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'name',
        'bank_account_number',
        'opening_balance',
        'is_cash_book',
        'is_active',
        'bank_fees_enabled',
        'bank_fee_particular_id',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'is_cash_book' => 'boolean',
        'is_active' => 'boolean',
        'bank_fees_enabled' => 'boolean',
    ];

    // Relationships
    public function vouchers()
    {
        return $this->hasMany(Voucher::class);
    }

    public function suspenseAccounts()
    {
        return $this->hasMany(SuspenseAccount::class);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function payrollEntries()
    {
        return $this->hasMany(PayrollEntry::class);
    }

    public function transactions()
    {
        return $this->hasMany(BookTransaction::class);
    }

    public function bankFeeTiers()
    {
        return $this->hasMany(BookBankFeeTier::class)->orderBy('sort_order')->orderBy('amount_from');
    }

    public function bankFeeParticular()
    {
        return $this->belongsTo(Particular::class, 'bank_fee_particular_id');
    }

    public function feeCategories()
    {
        return $this->hasMany(BookFeeCategory::class)->orderBy('name');
    }

    public function monthlyCuts()
    {
        return $this->hasMany(BookMonthlyCut::class)->orderBy('day_of_month')->orderBy('name');
    }

    /**
     * Bank fee for a withdrawal/expense amount based on optional tiers (bank books only).
     */
    public function resolveBankFeeForWithdrawalAmount(float|string $amount): float
    {
        if ($this->is_cash_book || ! $this->bank_fees_enabled || ! $this->bank_fee_particular_id) {
            return 0.0;
        }

        $a = (float) $amount;
        $tiers = $this->relationLoaded('bankFeeTiers')
            ? $this->bankFeeTiers
            : $this->bankFeeTiers()->get();

        foreach ($tiers as $tier) {
            $from = (float) $tier->amount_from;
            $to = $tier->amount_to === null ? null : (float) $tier->amount_to;
            if ($a < $from) {
                continue;
            }
            if ($to !== null && $a > $to) {
                continue;
            }

            return (float) $tier->fee_amount;
        }

        return 0.0;
    }

    public function deposits()
    {
        return $this->hasMany(BookTransaction::class)->where('transaction_type', 'deposit');
    }

    public function withdrawals()
    {
        return $this->hasMany(BookTransaction::class)->where('transaction_type', 'withdrawal');
    }

    // Helper methods
    public function getBalance()
    {
        $totalDebit = $this->vouchers()->sum('debit');
        $totalCredit = $this->vouchers()->sum('credit');

        // Bank view (for comparing to bank statements):
        // Bank considers money in as CR, money out as DR, so we invert the accountant storage.
        return $this->opening_balance + $totalCredit - $totalDebit;
    }

    /**
     * Get balance in accountant view (canonical storage):
     * Receipts are DR (debit) and increase balance; Payments are CR (credit) and reduce balance.
     */
    public function getCashViewBalance()
    {
        $totalDebit = $this->vouchers()->sum('debit');
        $totalCredit = $this->vouchers()->sum('credit');

        return $this->opening_balance + $totalDebit - $totalCredit;
    }

    /**
     * Get balance up to a specific date (exclusive) for bank view
     */
    public function getBalanceUpToDate($date)
    {
        $totalDebit = $this->vouchers()->where('date', '<', $date)->sum('debit');
        $totalCredit = $this->vouchers()->where('date', '<', $date)->sum('credit');

        return $this->opening_balance + $totalCredit - $totalDebit;
    }

    /**
     * Get cash view balance up to a specific date (exclusive)
     */
    public function getCashViewBalanceUpToDate($date)
    {
        $totalDebit = $this->vouchers()->where('date', '<', $date)->sum('debit');
        $totalCredit = $this->vouchers()->where('date', '<', $date)->sum('credit');

        return $this->opening_balance + $totalDebit - $totalCredit;
    }
}
