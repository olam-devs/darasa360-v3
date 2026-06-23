<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromArray, WithHeadings, WithTitle, WithStyles
{
    protected $reportData;

    public function __construct(array $reportData)
    {
        $this->reportData = $reportData;
    }

    public function array(): array
    {
        $data = [];
        $reportType = $this->reportData['report_type'];

        // Add header
        $data[] = [$this->reportData['title']];
        $data[] = ['Generated on: ' . $this->reportData['generated_at']];
        $data[] = [];

        // Add data based on report type
        switch ($reportType) {
            case 'class':
                $data = array_merge($data, $this->formatClassData());
                break;
            case 'payment_method':
                $data = array_merge($data, $this->formatPaymentMethodData());
                break;
            case 'overdue':
                $data = array_merge($data, $this->formatOverdueData());
                break;
            // Add other report types as needed
        }

        // Add summary
        $data[] = [];
        $data[] = ['SUMMARY'];
        foreach ($this->reportData['summary'] as $key => $value) {
            $data[] = [ucwords(str_replace('_', ' ', $key)), $value];
        }

        return $data;
    }

    private function formatClassData()
    {
        $data = [];
        $data[] = ['Class Name', 'Total Students', 'Total Fees', 'Total Collected', 'Collection Rate'];
        
        foreach ($this->reportData['data'] as $item) {
            $collectionRate = $item->total_fees > 0 ? ($item->total_collected / $item->total_fees) * 100 : 0;
            $data[] = [
                $item->class_name,
                $item->total_students,
                $item->total_fees,
                $item->total_collected,
                number_format($collectionRate, 2) . '%'
            ];
        }

        return $data;
    }

    private function formatPaymentMethodData()
    {
        $data = [];
        $data[] = ['Payment Method', 'Transaction Count', 'Total Amount', 'Average Amount'];
        
        foreach ($this->reportData['data'] as $item) {
            $data[] = [
                ucfirst($item->payment_method),
                $item->transaction_count,
                $item->total_amount,
                $item->average_amount
            ];
        }

        return $data;
    }

    private function formatOverdueData()
    {
        $data = [];
        $data[] = ['Student Name', 'Class', 'Registration No', 'Total Fees', 'Total Paid', 'Balance Due'];
        
        foreach ($this->reportData['data'] as $item) {
            $data[] = [
                $item->first_name . ' ' . $item->last_name,
                $item->class_name,
                $item->registration_no,
                $item->total_fees,
                $item->total_paid,
                $item->balance_due
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            [$this->reportData['title']]
        ];
    }

    public function title(): string
    {
        return substr($this->reportData['report_type'], 0, 31); // Excel sheet name limit
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            4 => ['font' => ['bold' => true]],
        ];
    }
}