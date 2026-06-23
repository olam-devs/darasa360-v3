<?php

namespace App\Services;

use App\Models\Platform\PlatformRegSequence;
use App\Models\Platform\PlatformSchool;
use Illuminate\Support\Facades\DB;

class PlatformRegistry
{
    /**
     * Allocate the next available 3-digit school code.
     * Picks the lowest code (001–999) not currently held by any active platform school.
     */
    public function allocateSchoolCode(): string
    {
        return DB::connection('platform')->transaction(function () {
            $used = PlatformSchool::pluck('code')->map(fn($c) => (int) $c)->toArray();

            for ($i = 1; $i <= 999; $i++) {
                if (!in_array($i, $used, true)) {
                    return str_pad($i, 3, '0', STR_PAD_LEFT);
                }
            }

            throw new \RuntimeException('No available school codes (001–999 all in use).');
        });
    }

    /**
     * Free a school code when a school is deleted (no-op here; code is freed
     * automatically because the platform_schools row is deleted).
     * Also purge reg sequences so a future school reusing this code starts fresh.
     */
    public function freeSchoolCode(int $platformSchoolId): void
    {
        PlatformRegSequence::where('school_id', $platformSchoolId)->delete();
    }

    /**
     * Generate the next registration number for a given school + role.
     * Format: S{code3}{role1}{seq4}  e.g. S00150042
     *
     * Role digits: 1=student, 2=teacher/headmaster, 3=accountant, 4=owner/admin
     *
     * @param int    $platformSchoolId
     * @param int    $roleDigit  (1–4)
     * @param string $code3      The school's 3-digit code (e.g. "001")
     */
    public function nextRegNo(int $platformSchoolId, int $roleDigit, string $code3): string
    {
        return DB::connection('platform')->transaction(function () use ($platformSchoolId, $roleDigit, $code3) {
            $seq = PlatformRegSequence::lockForUpdate()->firstOrCreate(
                ['school_id' => $platformSchoolId, 'role_digit' => $roleDigit],
                ['last_sequence' => 0]
            );

            $seq->increment('last_sequence');
            $seq->refresh();

            if ($seq->last_sequence > 9999) {
                throw new \RuntimeException("Sequence limit reached for school {$platformSchoolId} role {$roleDigit}.");
            }

            $seq4 = str_pad($seq->last_sequence, 4, '0', STR_PAD_LEFT);

            return "S{$code3}{$roleDigit}{$seq4}";
        });
    }
}
