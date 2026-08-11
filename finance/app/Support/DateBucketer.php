<?php

namespace App\Support;

use DateTime;

/**
 * Extracted from the old ExpenseController::analytics()'s inline day/month
 * bucketing logic (same threshold, same label formats) so both the legacy-
 * style trend endpoint and the new expected-vs-actual chart share one
 * implementation instead of duplicating it.
 */
class DateBucketer
{
    /**
     * Daily buckets for a range of 31 days or less, monthly buckets beyond that.
     */
    public static function isDaily(string $fromDate, string $toDate): bool
    {
        $daysDiff = (strtotime($toDate) - strtotime($fromDate)) / (60 * 60 * 24);

        return $daysDiff <= 31;
    }

    /**
     * Buckets $records into daily or monthly buckets spanning
     * [$fromDate, $toDate] (inclusive, empty buckets included), summing
     * $amountAccessor($record) per bucket keyed by $dateAccessor($record).
     *
     * @return array<int, array{label: string, amount: float}>
     */
    public static function bucket(string $fromDate, string $toDate, iterable $records, callable $dateAccessor, callable $amountAccessor): array
    {
        $daily = self::isDaily($fromDate, $toDate);
        $buckets = [];

        $current = new DateTime($fromDate);
        $end = new DateTime($toDate);
        while ($current <= $end) {
            $key = $daily ? $current->format('Y-m-d') : $current->format('Y-m');
            if (!isset($buckets[$key])) {
                $buckets[$key] = [
                    'label' => $daily ? $current->format('M d') : $current->format('M Y'),
                    'amount' => 0.0,
                ];
            }
            $current->modify($daily ? '+1 day' : '+1 month');
        }

        foreach ($records as $record) {
            $dateValue = (string) $dateAccessor($record);
            $key = $daily ? date('Y-m-d', strtotime($dateValue)) : date('Y-m', strtotime($dateValue));
            if (isset($buckets[$key])) {
                $buckets[$key]['amount'] += (float) $amountAccessor($record);
            }
        }

        return array_values($buckets);
    }
}
