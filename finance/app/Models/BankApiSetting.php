<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class BankApiSetting extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     *
     * @var string|null
     */
    protected $connection = 'tenant';

    protected $fillable = [
        'bank_name',
        'api_url',
        'api_key',
        'api_secret',
        'is_active',
        'use_simulation',
        'webhook_secret',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'use_simulation' => 'boolean',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'webhook_secret',
    ];
}
