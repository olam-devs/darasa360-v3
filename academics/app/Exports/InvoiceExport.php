<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InvoiceExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected $invoiceData;

    public function __construct(array $invoiceData)
    {
        $this->invoiceData = $invoiceData;
    }

    public function array(): array
    {
        $data = [];

        // Student information
        $data[] = ['Student Name', $this->invoiceData['student']->first_name . ' ' . $this->invoiceData['student']->last_name];
        $data[] = ['Class', $this->invoiceData['student']->class_name ?? 'N/A'];
        $data[] = ['Admission Number', $this->invoiceData['student']->admission_number ?? 'N/A'];
        $data[] = [''];

        // Fee items
        $data[] = ['Fee Items', 'Amount', 'Paid', 'Balance'];
        foreach ($this->invoiceData['fees'] as $fee) {
            $data[] = [$fee['name'], $fee['amount'], $fee['paid'], $fee['balance']];
        }

        // Optional services
        if (!empty($this->invoiceData['optional_services'])) {
            $data[] = [''];
            $data[] = ['Optional Services', 'Amount'];
            foreach ($this->invoiceData['optional_services'] as $service) {
                $data[] = [$service['name'], $service['amount']];
            }
        }

        // Summary
        $data[] = [''];
        $data[] = ['Total Amount', $this->invoiceData['total_amount']];
        $data[] = ['Total Paid', $this->invoiceData['total_paid']];
        $data[] = ['Balance Due', $this->invoiceData['balance_due']];
        $data[] = ['Issue Date', $this->invoiceData['issue_date']];
        $data[] = ['Due Date', $this->invoiceData['due_date']];

        return $data;
    }

    public function headings(): array
    {
        return [
            ['INVOICE: ' . $this->invoiceData['invoice_number']],
            []
        ];
    }

    public function title(): string
    {
        return 'Invoice';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            5 => ['font' => ['bold' => true]],
        ];
    }
}