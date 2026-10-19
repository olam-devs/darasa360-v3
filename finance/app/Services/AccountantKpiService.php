<?php

namespace App\Services;

use App\Models\Central\School;
use App\Models\Central\SchoolAccountant;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class AccountantKpiService
{
    public const METRICS = [
        'collection_amount' => 'Collections recorded (TSh)',
        'receipt_count' => 'Receipts recorded',
        'recording_timeliness' => 'Receipts recorded on time (%)',
    ];

    public static function status(?float $target, ?float $actual, string $toDate, ?string $today = null): string
    {
        if ($target === null) return 'Not set';
        if ($actual === null) return 'No data';
        if ($actual >= $target) return 'Met';
        return $toDate < ($today ?? now('Africa/Dar_es_Salaam')->toDateString()) ? 'Missed' : 'In progress';
    }

    public function receipts()
    {
        // A receipt without a book applies previously collected advance credit: it is not fresh cash.
        return DB::connection('tenant')->table('vouchers')->where('voucher_type', 'Receipt')
            ->whereNull('voided_at')->whereNotNull('book_id');
    }

    public function rows(School $school, string $from, string $to, ?int $onlyAccountant = null): array
    {
        return app(TenantDatabaseManager::class)->executeForSchool($school, function () use ($school, $from, $to, $onlyAccountant) {
            $accounts = SchoolAccountant::where('school_id', $school->id)->where('is_active', true)
                ->when($onlyAccountant, fn($q) => $q->whereKey($onlyAccountant))->orderBy('name')->get();
            $targets = DB::connection('central')->table('accountant_kpi_targets')->where('school_id', $school->id)
                ->whereDate('from_date', $from)->whereDate('to_date', $to)->get();
            // Workload KPIs use the entry date, not a backdated transaction date.
            $storageZone = config('app.timezone', 'UTC');
            $periodStart = CarbonImmutable::parse($from, 'Africa/Dar_es_Salaam')->startOfDay()->setTimezone($storageZone)->format('Y-m-d H:i:s');
            $periodEnd = CarbonImmutable::parse($to, 'Africa/Dar_es_Salaam')->addDay()->startOfDay()->setTimezone($storageZone)->format('Y-m-d H:i:s');
            $entries = $this->receipts()->where('created_at', '>=', $periodStart)
                ->where('created_at', '<', $periodEnd)
                ->get(['created_by', 'date', 'created_at', 'debit']);
            $rows = [];
            foreach ($accounts as $account) {
                $own = $entries->filter(fn($r) => (int)$r->created_by === $account->id);
                foreach (self::METRICS as $metric => $label) {
                    $target = $targets->first(fn($t) => (int)$t->accountant_id === $account->id && $t->metric === $metric);
                    $days = $target?->recording_days ?? 1;
                    $actual = match ($metric) {
                        'collection_amount' => (float)$own->sum('debit'),
                        'receipt_count' => (float)$own->count(),
                        default => $own->isEmpty() ? null : round(100 * $own->filter(function ($r) use ($days, $storageZone) {
                            if (!$r->date || !$r->created_at) return false;
                            $entry = CarbonImmutable::parse($r->created_at, $storageZone)->setTimezone('Africa/Dar_es_Salaam')->startOfDay();
                            $transaction = CarbonImmutable::parse($r->date, 'Africa/Dar_es_Salaam')->startOfDay();
                            return $entry->greaterThanOrEqualTo($transaction) && $transaction->diffInDays($entry) <= $days;
                        })->count() / $own->count(), 1),
                    };
                    $goal = $target ? (float)$target->target_value : null;
                    $rows[] = ['school_id'=>$school->id, 'school'=>$school->name, 'accountant_id'=>$account->id,
                        'accountant'=>$account->name, 'metric'=>$metric, 'label'=>$label, 'actual'=>$actual,
                        'target'=>$goal, 'recording_days'=>(int)$days, 'status'=>self::status($goal, $actual, $to),
                        'shortfall'=>$goal !== null && $actual !== null ? max(0, $goal-$actual) : null,
                        'progress'=>$goal > 0 && $actual !== null ? min(100, round(100*$actual/$goal)) : null];
                }
            }
            return $rows;
        });
    }
}
