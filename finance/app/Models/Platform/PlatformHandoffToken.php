<?php

namespace App\Models\Platform;

use Illuminate\Database\Eloquent\Model;

class PlatformHandoffToken extends Model
{
    protected $connection = 'platform';
    protected $table = 'platform_handoff_tokens';

    protected $fillable = [
        'token', 'school_id', 'user_ref', 'role',
        'source_system', 'target_system', 'payload',
        'expires_at', 'used_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function isValid(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }
}
