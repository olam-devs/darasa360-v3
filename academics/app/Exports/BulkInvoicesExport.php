<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BulkInvoicesExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected $invoices;

    public function __construct(array $invoices)
    {
        $this->invoices = $invoices;
    }

    public function array(): array
    {
        $data = [];

        foreach ($this->invoices as $invoice) {
            $data[] = ['Invoice Number:', $invoice['invoice_number']];
            $data[] = ['Student:', $invoice['student']->first_name . ' ' . $invoice['student']->last_name];
            $data[] = ['Class:', $invoice['student']->class_name ?? 'N/A'];
            $data[] = ['Total Amount:', $invoice['total_amount']];
            $data[] = ['Balance Due:', $invoice['balance_due']];
            $data[] = ['Due Date:', $invoice['due_date']];
            $data[] = [''];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            ['BULK INVOICES - GENERATED ON ' . date('Y-m-d H:i:s')],
            []
        ];
    }

    public function title(): string
    {
        return 'Bulk Invoices';
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
        ];
    }
}