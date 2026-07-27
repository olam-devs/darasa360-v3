<?php

namespace App\Exports;

use App\Models\Attendance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * One worksheet per calendar month covered by the attendance's date range,
 * instead of a single sheet with one column per day - a Term 1 attendance
 * (July-December) would otherwise need 100+ date columns on one sheet.
 */
class AttendanceTemplateExport implements WithMultipleSheets
{
    protected $attendanceId;

    public function __construct($attendanceId)
    {
        $this->attendanceId = $attendanceId;
    }

    public function sheets(): array
    {
        $attendance = Attendance::on('tenant')->findOrFail($this->attendanceId);

        $months = [];
        $cursor = Carbon::parse($attendance->from_date)->startOfMonth();
        $end = Carbon::parse($attendance->to_date)->startOfMonth();
        while ($cursor->lte($end)) {
            $months[] = $cursor->copy();
            $cursor->addMonth();
        }

        $sheets = [];
        foreach ($months as $monthStart) {
            $sheets[] = new AttendanceMonthSheetExport($attendance, $monthStart);
        }

        return $sheets;
    }
}
