<?php

namespace App\Services;

use App\Models\Platform\PlatformCrossAccess;
use App\Models\Platform\PlatformHandoffToken;
use App\Models\Platform\PlatformSchool;
use Illuminate\Support\Str;

class HandoffService
{
    /**
     * Issue a single-use handoff token (Finance → Academics direction).
     *
     * @param int    $platformSchoolId
     * @param string $userRef          The user's reg_no or identifier
     * @param string $role             headmaster|owner|parent
     * @param string $source           finance|academics
     * @param string $target           finance|academics
     * @param array  $payload          Extra data to carry over (e.g. student_reg_no for parents)
     */
    public function issueToken(
        int $platformSchoolId,
        string $userRef,
        string $role,
        string $source,
        string $target,
        array $payload = []
    ): string {
        $token = Str::random(64);

        PlatformHandoffToken::create([
            'token'         => $token,
            'school_id'     => $platformSchoolId,
            'user_ref'      => $userRef,
            'role'          => $role,
            'source_system' => $source,
            'target_system' => $target,
            'payload'       => $payload,
            'expires_at'    => now()->addSeconds(90), // 90s window — enough for redirect
        ]);

        return $token;
    }

    /**
     * Consume a handoff token. Returns the token record or null if invalid/expired.
     */
    public function consumeToken(string $token): ?PlatformHandoffToken
    {
        $record = PlatformHandoffToken::where('token', $token)->first();

        if (!$record || !$record->isValid()) {
            return null;
        }

        $record->update(['used_at' => now()]);
        return $record;
    }

    /**
     * Check whether a given user is allowed to jump from Finance to Academics
     * for the specified school.
     *
     * @param string $role      headmaster|owner|parent
     * @param string $userRef   Registration number or identifier
     * @param PlatformSchool $school
     */
    public function canJump(string $role, string $userRef, PlatformSchool $school): bool
    {
        if (!$school->canCrossJump()) {
            return false;
        }

        if ($role === 'parent') {
            return (bool) $school->parent_cross_access;
        }

        // Owners and headmasters need a per-user grant
        return PlatformCrossAccess::where('school_id', $school->id)
            ->where('user_ref', $userRef)
            ->where('role', $role)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Build the full redirect URL for the target system's handoff consume endpoint.
     */
    public function consumeUrl(string $target, string $token): string
    {
        $base = match ($target) {
            'academics' => rtrim(config('platform.academics_url', env('ACADEMICS_APP_URL', '')), '/'),
            'finance'   => rtrim(config('platform.finance_url', env('APP_URL', '')), '/'),
            default     => '',
        };

        return $base . '/handoff/consume?token=' . urlencode($token);
    }
}
