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
        'school_setting_id',
        'bank_name',
        'account_number',
        'display_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relationships
    public function schoolSetting()
    {
        return $this->belongsTo(SchoolSetting::class);
    }
}
