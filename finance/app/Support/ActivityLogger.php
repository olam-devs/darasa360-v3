<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Throwable;

class ActivityLogger
{
    /**
     * Fire-and-forget log: never throws, never blocks the request.
     */
    public static function log(string $action, ?string $description = null, ?Model $subject = null, array $metadata = []): void
    {
        try {
            $user = auth()->user();
            ActivityLog::create([
                'user_id' => $user?->id,
                'user_name' => $user?->name,
                'action' => substr($action, 0, 80),
                'subject_type' => $subject ? class_basename($subject) : null,
                'subject_id' => $subject?->getKey(),
                'description' => $description,
                'metadata' => $metadata ?: null,
                'ip_address' => request() instanceof Request ? request()->ip() : null,
            ]);
        } catch (Throwable $e) {
            // Audit logging must never break the main flow.
        }
    }
}
