<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class SchoolSetting extends BaseModel
{
    use HasFactory;

    /**
     * The connection name for the model.
     * Using default mysql connection for single-school setup
     *
     * @var string|null
     */
    protected $connection = 'mysql';

    protected $fillable = [
        'school_name',
        'po_box',
        'region',
        'phone',
        'office_whatsapp_number',
        'parent_messenger_pin',
        'email',
        'logo_path',
        'show_logo_on_pdfs',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
    ];

    protected function casts(): array
    {
        return [
            'show_logo_on_pdfs' => 'boolean',
        ];
    }

    public static function getSettings()
    {
        return static::first() ?? static::create([
            'school_name' => 'School Name',
            'po_box' => '',
            'region' => '',
            'phone' => '',
            'email' => '',
        ]);
    }
}
