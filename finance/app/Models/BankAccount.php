<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankAccount extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'account_name',
        'account_number',
        'bank_name',
        'branch',
        'swift_code',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
