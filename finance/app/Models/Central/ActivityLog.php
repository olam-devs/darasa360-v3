<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

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
    protected $table = 'activity_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'school_id',
        'user_type',
        'user_id',
        'user_name',
        'action',
        'description',
        'ip_address',
    ];

    /**
     * Get the school associated with the log.
     */
    public function school()
    {
        return $this->belongsTo(School::class);
    }

    /**
     * Get the user associated with the log (polymorphic).
     */
    public function user()
    {
        if ($this->user_type === 'super_admin') {
            return \App\Models\Platform\PlatformSuperAdmin::find($this->user_id);
        } elseif ($this->user_type === 'accountant') {
            return SchoolAccountant::find($this->user_id);
        }
        
        return null;
    }

    /**
     * Scope a query to only include logs for a specific school.
     */
    public function scopeForSchool($query, $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    /**
     * Scope a query to only include logs for super admins.
     */
    public function scopeSuperAdminOnly($query)
    {
        return $query->where('user_type', 'super_admin');
    }

    /**
     * Scope a query to only include logs for accountants.
     */
    public function scopeAccountantOnly($query)
    {
        return $query->where('user_type', 'accountant');
    }
}
