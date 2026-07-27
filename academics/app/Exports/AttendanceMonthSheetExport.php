<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\DailyAttendanceEntry;
use App\Models\Student;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One calendar month's worth of attendance for a single Attendance record.
 * Row layout (see HEADER_ROW below - the importer reads by this same layout,
 * keep the two in sync):
 *   1: cutoff-date label/value the teacher edits before re-uploading
 *   2: instructions
 *   3: blank spacer
 *   4: #, Student Name, Registration No, then one column per date in this month
 *   5+: student rows
 */
class AttendanceMonthSheetExport implements FromArray, WithTitle, WithStyles, WithColumnWidths, WithEvents
{
    public const HEADER_ROW = 4;

    protected Attendance $attendance;
    protected Carbon $monthStart;
    protected array $dates = [];

    public function __construct(Attendance $attendance, Carbon $monthStart)
    {
        $this->attendance = $attendance;
        $this->monthStart = $monthStart;
    }

    public function title(): string
    {
        return $this->monthStart->format('F Y');
    }

    public function array(): array
    {
        $rangeStart = Carbon::parse($this->attendance->from_date);
        $rangeEnd = Carbon::parse($this->attendance->to_date);
        $monthEnd = $this->monthStart->copy()->endOfMonth();

        $firstDay = $rangeStart->gt($this->monthStart) ? $rangeStart->copy() : $this->monthStart->copy();
        $lastDay = $rangeEnd->lt($monthEnd) ? $rangeEnd->copy() : $monthEnd->copy();

        $this->dates = [];
        for ($d = $firstDay->copy(); $d->lte($lastDay); $d->addDay()) {
            $this->dates[] = $d->copy();
        }

        // Default cutoff: today, clamped to this attendance's own range - can't
        // sensibly mark attendance for a day that hasn't happened yet, and no
        // point defaulting past the record's own end date either.
        $today = Carbon::today();
        $cutoff = $today->gt($rangeEnd) ? $rangeEnd : ($today->lt($rangeStart) ? $rangeStart : $today);

        $students = Student::on('tenant')
            ->where('class_id', $this->attendance->class_id)
            ->when($this->attendance->stream_id, fn ($q) => $q->where('stream_id', $this->attendance->stream_id))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $existing = DailyAttendanceEntry::on('tenant')
            ->where('attendance_id', $this->attendance->id)
            ->whereIn('student_id', $students->pluck('id'))
            ->whereBetween('date', [$firstDay->toDateString(), $lastDay->toDateString()])
            ->get()
            ->groupBy(fn ($e) => $e->student_id . '_' . Carbon::parse($e->date)->toDateString());

        $rows = [];
        $rows[] = ['Attendance valid through (YYYY-MM-DD):', $cutoff->toDateString()];
        $rows[] = ['Mark X for ABSENT. Leave blank or put a check for PRESENT. Only dates on/before the date above are saved when this file is uploaded.'];
        $rows[] = [];

        $header = ['#', 'Student Name', 'Registration No'];
        foreach ($this->dates as $date) {
            $header[] = $date->format('d/m/Y');
        }
        $rows[] = $header;

        foreach ($students as $index => $student) {
            $row = [
                $index + 1,
                trim($student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name),
                $student->registration_no,
            ];

            foreach ($this->dates as $date) {
                $entry = $existing->get($student->id . '_' . $date->toDateString())?->first();

                if ($entry) {
                    $row[] = $entry->status === 'present' ? '✓' : 'X';
                } else {
                    $row[] = $date->lte($cutoff) ? '✓' : ''; // no default beyond the cutoff - hasn't happened yet
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            2 => ['font' => ['italic' => true, 'size' => 9]],
            self::HEADER_ROW => [
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 5, 'B' => 35, 'C' => 20];

        $column = 'D';
        foreach ($this->dates as $date) {
            $widths[$column] = 12;
            $column++;
        }

        return $widths;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                $sheet->getStyle('A' . self::HEADER_ROW . ':' . $highestColumn . $highestRow)
                    ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

                $sheet->getStyle('D' . self::HEADER_ROW . ':' . $highestColumn . $highestRow)
                    ->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->getRowDimension(self::HEADER_ROW)->setRowHeight(25);
                $sheet->getStyle('B1')->getFont()->setBold(true)->getColor()->setRGB('C00000');
            },
        ];
    }
}
