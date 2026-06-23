<?php

namespace App\Exports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\DB;

class StudentsFinanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    protected $classId;
    protected $filters;

    public function __construct($classId = null, $filters = [])
    {
        $this->classId = $classId;
        $this->filters = $filters;
    }

    /**
     * Get the collection of students to export for finance system
     */
    public function collection()
    {
        $query = DB::connection('tenant')->table('students')
            ->join('schoolUsers', 'students.user_id', '=', 'schoolUsers.id')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->select(
                'schoolUsers.username as full_name',
                'schoolUsers.registration_no',
                'classes.name as class_name',
                'students.gender',
                'schoolUsers.phone_number'
            )
            ->orderBy('classes.name')
            ->orderBy('schoolUsers.username');

        // Filter by class if specified
        if ($this->classId) {
            $query->where('students.class_id', $this->classId);
        }

        // Apply additional filters
        if (isset($this->filters['gender']) && $this->filters['gender']) {
            $query->where('students.gender', $this->filters['gender']);
        }

        if (isset($this->filters['search']) && $this->filters['search']) {
            $search = $this->filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('schoolUsers.username', 'like', "%{$search}%")
                    ->orWhere('schoolUsers.registration_no', 'like', "%{$search}%");
            });
        }

        return collect($query->get());
    }

    /**
     * Define the headings for the finance export
     */
    public function headings(): array
    {
        return [
            'Full Name',
            'Registration Number',
            'Class',
            'Gender',
            'Phone Number'
        ];
    }

    /**
     * Map the data for each row
     */
    public function map($student): array
    {
        return [
            $student->full_name,
            $student->registration_no,
            $student->class_name,
            $student->gender ?? 'N/A',
            $student->phone_number ?? 'N/A',
        ];
    }

    /**
     * Apply styles to the worksheet
     */
    public function styles(Worksheet $sheet)
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Style the header row
        $sheet->getStyle('A1:' . $highestColumn . '1')->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ]);

        // Style all data rows
        if ($highestRow > 1) {
            $sheet->getStyle('A2:' . $highestColumn . $highestRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color' => ['rgb' => 'CCCCCC'],
                    ],
                ],
            ]);

            // Center align Registration Number, Class, and Gender columns
            $sheet->getStyle('B2:D' . $highestRow)->applyFromArray([
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ],
            ]);

            // Add zebra striping for better readability
            for ($row = 2; $row <= $highestRow; $row++) {
                if ($row % 2 == 0) {
                    $sheet->getStyle('A' . $row . ':' . $highestColumn . $row)->applyFromArray([
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'F2F2F2'],
                        ],
                    ]);
                }
            }
        }

        // Set row height for header
        $sheet->getRowDimension(1)->setRowHeight(25);

        return $sheet;
    }
}
