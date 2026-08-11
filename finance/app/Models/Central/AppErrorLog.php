<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

class AppErrorLog extends Model
{
    protected $connection = 'central';

    protected $table = 'app_error_logs';

    public $timestamps = false;

    protected $fillable = [
        'school_id',
        'context',
        'message',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
    }

    /**
     * Record an error for the super-admin error log, in addition to (not
     * instead of) the normal Laravel log. Never lets a logging failure
     * itself break the caller - wraps its own write in a try/catch.
     */
    public static function record(?int $schoolId, string $context, Throwable $e, array $meta = []): void
    {
        Log::error("[{$context}] " . $e->getMessage(), $meta);

        try {
            static::create([
                'school_id' => $schoolId,
                'context' => $context,
                'message' => $e->getMessage(),
                'meta' => $meta ?: null,
                'created_at' => now(),
            ]);
        } catch (Throwable $inner) {
            Log::error('AppErrorLog::record() itself failed: ' . $inner->getMessage());
        }
    }
}
