<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class School extends Model
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
    protected $table = 'schools';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'slug',
        'code',
        'database_name',
        'db_host',
        'db_port',
        'db_username',
        'db_password',
        'domain',
        'logo',
        'contact_email',
        'contact_phone',
        'address',
        'is_active',
        'subscription_status',
        'subscription_expires_at',
        'max_students',
        'sms_credits_assigned',
        'sms_credits_used',
        'has_finance',
        'has_academics',
        'cross_jump_enabled',
        'parent_cross_access',
        'academics_db_name',
        'platform_school_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'subscription_expires_at' => 'datetime',
            'max_students' => 'integer',
            'sms_credits_assigned' => 'integer',
            'sms_credits_used' => 'integer',
            'has_finance' => 'boolean',
            'has_academics' => 'boolean',
            'cross_jump_enabled' => 'boolean',
            'parent_cross_access' => 'boolean',
            'platform_school_id' => 'integer',
        ];
    }

    /**
     * Get remaining SMS credits.
     */
    public function getSmsCreditsRemainingAttribute(): int
    {
        return max(0, $this->sms_credits_assigned - $this->sms_credits_used);
    }

    /**
     * Check if school has SMS credits available.
     */
    public function hasSmsCredits(int $count = 1): bool
    {
        return $this->sms_credits_remaining >= $count;
    }

    /**
     * Deduct SMS credits.
     */
    public function deductSmsCredits(int $count = 1): bool
    {
        if (!$this->hasSmsCredits($count)) {
            return false;
        }

        $this->increment('sms_credits_used', $count);
        return true;
    }

    /**
     * Add SMS credits.
     */
    public function addSmsCredits(int $count): void
    {
        $this->increment('sms_credits_assigned', $count);
    }

    /**
     * Get the accountants for the school.
     */
    public function accountants()
    {
        return $this->hasMany(SchoolAccountant::class);
    }

    /**
     * Get the activity logs for the school.
     */
    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get the analytics summaries for the school.
     */
    public function analyticsSummaries()
    {
        return $this->hasMany(AnalyticsSummary::class);
    }

    /**
     * Whether this school can do cross-system SSO jumps.
     */
    public function canCrossJump(): bool
    {
        return $this->has_finance && $this->has_academics && $this->cross_jump_enabled;
    }

    /**
     * Scope a query to only include active schools.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include schools with active subscriptions.
     */
    public function scopeSubscribed($query)
    {
        return $query->where('subscription_status', 'active')
            ->where(function ($q) {
                $q->whereNull('subscription_expires_at')
                    ->orWhere('subscription_expires_at', '>', now());
            });
    }

    /**
     * Check if the school's subscription is expired.
     */
    public function isSubscriptionExpired(): bool
    {
        if ($this->subscription_status !== 'active') {
            return true;
        }

        if ($this->subscription_expires_at && $this->subscription_expires_at->isPast()) {
            return true;
        }

        return false;
    }

    /**
     * Get the full URL for the school.
     */
    public function getUrl(): string
    {
        if ($this->domain) {
            return 'https://' . $this->domain;
        }

        return 'https://' . $this->slug . '.' . config('app.domain', 'darasafinance.com');
    }
}
