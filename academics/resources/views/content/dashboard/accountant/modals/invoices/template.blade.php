<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .school-name { font-size: 18px; font-weight: bold; }
        .invoice-title { font-size: 16px; margin: 10px 0; }
        .student-info { margin: 15px 0; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f5f5f5; font-weight: bold; }
        .summary { margin-top: 20px; float: right; width: 300px; }
        .summary-row { display: flex; justify-content: space-between; margin: 5px 0; }
        .total { font-weight: bold; border-top: 2px solid #333; padding-top: 5px; }
        .footer { margin-top: 50px; text-align: center; font-size: 10px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <div class="school-name">{{ $school_info['name'] }}</div>
                <div class="school-contact" style="font-size: 12px; margin-top: 4px; color: #555;">
    @php
        $contactParts = [];
        if(!empty($school_info['report_phone'])) $contactParts[] = $school_info['report_phone'];
        if(!empty($school_info['report_email'])) $contactParts[] = $school_info['report_email'];
        if(!empty($school_info['report_address'])) $contactParts[] = $school_info['report_address'];
    @endphp

    {{ implode(' | ', $contactParts) }}
</div>
        
        <div class="invoice-title">FEE INVOICE: {{ $invoice_number }}</div>
    </div>

    <div class="student-info">
        <strong>Student:</strong> {{ $student->first_name }} {{ $student->last_name }}<br>
        <strong>Class:</strong> {{ $student->class_name ?? 'N/A' }}<br>
        <strong>Admission No:</strong> {{ $student->registration_no ?? 'N/A' }}<br>
        <strong>Issue Date:</strong> {{ $issue_date }} | <strong>Due Date:</strong> {{ $due_date }}
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>Description</th>
                <th>Amount (Tsh)</th>
                <th>Paid (Tsh)</th>
                <th>Balance (Tsh)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($fees as $fee)
            <tr>
                <td>{{ $fee['name'] }}</td>
                <td>{{ number_format($fee['amount'], 2) }}</td>
                <td>{{ number_format($fee['paid'], 2) }}</td>
                <td>{{ number_format($fee['balance'], 2) }}</td>
            </tr>
            @endforeach
            
            @if(!empty($optional_services))
                @foreach($optional_services as $service)
                <tr>
                    <td>{{ $service['name'] }} (Optional)</td>
                    <td>{{ number_format($service['amount'], 2) }}</td>
                    <td>-</td>
                    <td>{{ number_format($service['amount'], 2) }}</td>
                </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-row">
            <span>Total Amount:</span>
            <span>Tsh {{ number_format($total_amount, 2) }}</span>
        </div>
        <div class="summary-row">
            <span>Total Paid:</span>
            <span>Tsh {{ number_format($total_paid, 2) }}</span>
        </div>
        <div class="summary-row total">
            <span>Balance Due:</span>
            <span>Tsh {{ number_format($balance_due, 2) }}</span>
        </div>
    </div>

    <div class="footer">
       <p>Generated on: {{ now()->format('l, jS F Y') }}</p>
        <p>For inquiries, please contact the accounts office.</p>
    </div>
</body>
</html>