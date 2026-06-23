<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class SchoolAccountant extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'central';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'school_accountants';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'name',
        'email',
        'password',
        'is_active',
        'is_primary',
        'can_edit_history',
        'can_view_logs',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_primary' => 'boolean',
            'can_edit_history' => 'boolean',
            'can_view_logs' => 'boolean',
        ];
    }

    /**
     * Get the school that owns the accountant.
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Scope a query to only include active accountants.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
