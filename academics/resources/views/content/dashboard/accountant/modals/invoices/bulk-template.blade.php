<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Bulk Invoices Summary - {{ date('Y-m-d') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .school-name { font-size: 16px; font-weight: bold; }
        .invoice-title { font-size: 14px; margin: 10px 0; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        .table th { background-color: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .total-row { font-weight: bold; background-color: #f8f9fa; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #666; }
    </style>
</head>
<body>
   <div class="header">
    <div class="school-name">
    {{ $invoices[0]['school_info']['name'] ?? $school_info['name'] ?? '' }}
</div>

<div class="school-contact" style="font-size: 12px; margin-top: 4px; color: #555;">
    @php
        $school = $invoices[0]['school_info'] ?? $school_info ?? [];
        $contactParts = [];
        if(!empty($school['report_phone'])) $contactParts[] = $school['report_phone'];
        if(!empty($school['report_email'])) $contactParts[] = $school['report_email'];
        if(!empty($school['report_address'])) $contactParts[] = $school['report_address'];
    @endphp

    {{ implode(' | ', $contactParts) }}
</div>

    <div class="invoice-title">BULK INVOICE SUMMARY - {{ date('F j, Y') }}</div>
    </div>


    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Invoice No</th>
                <th>Student Name</th>
                <th>Class</th>
                <th>Phone No</th>
                <th>Total Amount</th>
                <th>Paid</th>
                <th>Balance Due</th>
                <th>Due Date</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $index => $invoice)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $invoice['invoice_number'] }}</td>
                <td>{{ $invoice['student']->first_name }} {{ $invoice['student']->last_name }}</td>
                <td>{{ $invoice['student']->class_name ?? 'N/A' }}</td>
                <td>{{ $invoice['student']->phone_number ?? 'N/A' }}</td>
                <td class="text-right">Tsh {{ number_format($invoice['total_amount'], 2) }}</td>
                <td class="text-right">Tsh {{ number_format($invoice['total_paid'], 2) }}</td>
                <td class="text-right">Tsh {{ number_format($invoice['balance_due'], 2) }}</td>
                <td>{{ $invoice['due_date'] }}</td>
            </tr>
            @endforeach
            
            <tr class="total-row">
                <td colspan="5" class="text-right"><strong>TOTALS:</strong></td>
                <td class="text-right"><strong>Tsh {{ number_format(collect($invoices)->sum('total_amount'), 2) }}</strong></td>
                <td class="text-right"><strong>Tsh {{ number_format(collect($invoices)->sum('total_paid'), 2) }}</strong></td>
                <td class="text-right"><strong>Tsh {{ number_format(collect($invoices)->sum('balance_due'), 2) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Generated on: {{ date('Y-m-d H:i:s') }} | Total Invoices: {{ count($invoices) }}</p>
    </div>
</body>
</html>