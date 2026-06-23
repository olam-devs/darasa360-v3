<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .school-name { font-size: 16px; font-weight: bold; }
        .report-title { font-size: 14px; margin: 10px 0; }
        .table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f5f5f5; font-weight: bold; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { background-color: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .footer { margin-top: 20px; text-align: center; font-size: 9px; color: #666; }
        .total-row { font-weight: bold; background-color: #e9ecef; }
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

        {{-- <div> {{ $school_info['phone'] }} | {{ $school_info['email'] }}</div> --}}
        <div class="report-title">{{ $title }}</div>
        <div>
            @if($start_date && $end_date)
                Period: {{ $start_date }} to {{ $end_date }}
            @else
                Generated on: {{ $generated_at }}
            @endif
            @if($class_id)
                | Class: {{ \App\Models\Classroom::find($class_id)->name ?? 'Selected Class' }}
            @endif
        </div>
    </div>

    {{-- Report Content --}}
    @if($report_type == 'class')
        {{-- Class Report --}}
        <table class="table">
            <thead>
                <tr>
                    <th>Class Name</th>
                    <th class="text-right">Total Students</th>
                    <th class="text-right">Total Fees (Tsh)</th>
                    <th class="text-right">Total Collected (Tsh)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                <tr>
                    <td>{{ $item->class_name }}</td>
                    <td class="text-right">{{ intval($item->total_students) }}</td>
                    <td class="text-right">{{ number_format($item->total_fees, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total_collected, 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>TOTALS</strong></td>
                    <td class="text-right"><strong>{{ intval($summary['total_students']) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($summary['total_fees'], 2) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($summary['total_collected'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

    @elseif($report_type == 'payment_method')
        {{-- Payment Method Report --}}
        <table class="table">
            <thead>
                <tr>
                    <th>Payment Method</th>
                    <th class="text-right">Transaction Count</th>
                    <th class="text-right">Total Amount (Tsh)</th>
                    <th class="text-right">Average Amount (Tsh)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                <tr>
                    <td>{{ ucfirst($item->payment_method) }}</td>
                    <td class="text-right">{{ intval($item->transaction_count) }}</td>
                    <td class="text-right">{{ number_format($item->total_amount, 2) }}</td>
                    <td class="text-right">{{ number_format($item->average_amount, 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td><strong>TOTALS</strong></td>
                    <td class="text-right"><strong>{{ intval($summary['total_transactions']) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($summary['total_amount'], 2) }}</strong></td>
                    <td class="text-right">-</td>
                </tr>
            </tbody>
        </table>

    @elseif($report_type == 'overdue')
        {{-- Overdue Payments Report --}}
        <table class="table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Class</th>
                    <th>Phone No</th>
                    <th class="text-right">Total Fees (Tsh)</th>
                    <th class="text-right">Total Paid (Tsh)</th>
                    <th class="text-right">Balance Due (Tsh)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                <tr>
                    <td>{{ $item->first_name }} {{ $item->last_name }}</td>
                    <td>{{ $item->class_name }}</td>
                    <td>{{ $item->phone_number }}</td>
                    <td class="text-right">{{ number_format($item->total_fees, 2) }}</td>
                    <td class="text-right">{{ number_format($item->total_paid, 2) }}</td>
                    <td class="text-right">{{ number_format($item->balance_due, 2) }}</td>
                </tr>
                @endforeach
                <tr class="total-row">
                    <td colspan="3"><strong>TOTALS</strong></td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right"><strong>{{ number_format($summary['total_overdue_amount'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>

    @else
        {{-- Generic Report --}}
        <table class="table">
            <thead>
                <tr>
                    <th>Data</th>
                    <th class="text-right">Value</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data as $item)
                    @foreach(get_object_vars($item) as $key => $value)
                    <tr>
                        <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                        <td class="text-right">{{ is_numeric($value) ? (intval($value) == $value ? intval($value) : number_format($value, 2)) : $value }}</td>
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Summary --}}
    <div class="summary">
        <h4>Report Summary</h4>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
            @foreach($summary as $key => $value)
            <div>
                <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                {{ is_numeric($value) ? (intval($value) == $value ? intval($value) : number_format($value, 2)) : $value }}
            </div>
            @endforeach
        </div>
    </div>

    <div class="footer">
        <p>Generated on: {{ now()->format('l, jS F Y') }} | Report Type: {{ ucfirst($report_type) }}</p>
        {{-- <p>System: Darasa360</p> --}}
    </div>
</body>
</html>
