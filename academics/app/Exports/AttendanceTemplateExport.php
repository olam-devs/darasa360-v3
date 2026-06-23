<?php

namespace App\Exports;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\DailyAttendanceEntry;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class AttendanceTemplateExport implements FromCollection, WithHeadings, WithStyles, WithColumnWidths, WithEvents
{
    protected $attendanceId;
    protected $dates = [];
    protected $students = [];

    public function __construct($attendanceId)
    {
        $this->attendanceId = $attendanceId;
    }

    public function collection()
    {
        $attendance = Attendance::on('tenant')->findOrFail($this->attendanceId);

        // Get all students in the class
        $this->students = Student::on('tenant')
            ->where('class_id', $attendance->class_id)
            ->when($attendance->stream_id, function($query) use ($attendance) {
                return $query->where('stream_id', $attendance->stream_id);
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        // Generate date range
        $period = CarbonPeriod::create($attendance->from_date, $attendance->to_date);
        $this->dates = [];
        foreach ($period as $date) {
            $this->dates[] = $date->format('Y-m-d');
        }

        // Build the data rows
        $data = [];
        foreach ($this->students as $index => $student) {
            $row = [
                $index + 1,
                $student->first_name . ' ' . $student->middle_name . ' ' . $student->last_name,
                $student->registration_no,
            ];

            // For each date, check if attendance exists
            foreach ($this->dates as $date) {
                $entry = DailyAttendanceEntry::on('tenant')
                    ->where('attendance_id', $attendance->id)
                    ->where('student_id', $student->id)
                    ->where('date', $date)
                    ->first();

                // Use checkmark if present, X if absent, empty if not recorded
                if ($entry) {
                    $row[] = $entry->status === 'present' ? '✓' : ($entry->status === 'absent' ? 'X' : '');
                } else {
                    $row[] = '✓'; // Default to present (checked)
                }
            }

            $data[] = $row;
        }

        return collect($data);
    }

    public function headings(): array
    {
        $headers = ['#', 'Student Name', 'Registration No'];

        foreach ($this->dates as $date) {
            $headers[] = Carbon::parse($date)->format('d/m/Y');
        }

        return $headers;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4']
                ],
                'font' => ['color' => ['rgb' => 'FFFFFF'], 'bold' => true],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ],
        ];
    }

    public function columnWidths(): array
    {
        $widths = [
            'A' => 5,
            'B' => 35,
            'C' => 20,
        ];

        // Add width for date columns
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
            AfterSheet::class => function(AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Get the highest row and column
                $highestRow = $sheet->getHighestRow();
                $highestColumn = $sheet->getHighestColumn();

                // Apply borders to all cells
                $sheet->getStyle('A1:' . $highestColumn . $highestRow)
                    ->getBorders()
                    ->getAllBorders()
                    ->setBorderStyle(Border::BORDER_THIN);

                // Center align date columns
                $dateStartColumn = 'D';
                $sheet->getStyle($dateStartColumn . '1:' . $highestColumn . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                // Set row height for header
                $sheet->getRowDimension(1)->setRowHeight(25);

                // Add instructions at the bottom
                $instructionRow = $highestRow + 2;
                $sheet->setCellValue('A' . $instructionRow, 'Instructions:');
                $sheet->setCellValue('A' . ($instructionRow + 1), '✓ = Present, X = Absent, Leave blank if no record');
                $sheet->getStyle('A' . $instructionRow)->getFont()->setBold(true);
            },
        ];
    }
}
