<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Class Ledger - {{ $className }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            margin: 20px;
        }
        .ledger-title {
            font-size: 14px;
            font-weight: bold;
            margin: 15px 0;
            text-align: center;
            text-decoration: underline;
        }
        .class-info {
            margin-bottom: 15px;
            text-align: center;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .ledger-table th {
            background-color: #f0f0f0;
            border: 1px solid #333;
            padding: 6px;
            font-size: 10px;
            font-weight: bold;
        }
        .ledger-table td {
            border: 1px solid #999;
            padding: 5px;
            font-size: 10px;
        }
        .amount-debit {
            text-align: right;
            color: #d9534f;
        }
        .amount-credit {
            text-align: right;
            color: #5cb85c;
        }
        .amount-balance {
            text-align: right;
            font-weight: bold;
            color: #0275d8;
        }
        .footer {
            margin-top: 30px;
            padding-top: 10px;
            border-top: 1px solid #999;
            font-size: 9px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    @include('components.pdf-header', ['school' => $school ?? null])

    <div class="ledger-title">CLASS LEDGER SUMMARY</div>

    <div class="class-info">
        <strong>Class:</strong> {{ $className }}<br>
        <strong>Date Range:</strong> {{ $dateRange }}
    </div>

    <!-- INDIVIDUAL STUDENT PARTICULARS -->
    @php $sn = 1; @endphp
    @forelse($classLedger as $entry)
    <div style="margin-bottom: 25px; page-break-inside: avoid;">
        <div style="background-color: #f5f5f5; border-left: 4px solid #2196f3; padding: 8px; margin-bottom: 10px;">
            <strong style="font-size: 11px;">{{ $sn++ }}. {{ $entry['student']->name }}</strong> ({{ $entry['student']->student_reg_no }})
            <div style="float: right; font-size: 10px;">
                <span style="color: #d9534f;">DR: TSh {{ number_format($entry['total_debit'], 2) }}</span> |
                <span style="color: #5cb85c;">CR: TSh {{ number_format($entry['total_credit'], 2) }}</span> |
                <span style="color: #0275d8; font-weight: bold;">Bal: TSh {{ number_format($entry['balance'], 2) }}</span>
            </div>
        </div>

        @if(count($entry['particulars']) > 0)
        <table class="ledger-table" style="margin-bottom: 15px;">
            <thead>
                <tr style="background-color: #f9f9f9;">
                    <th style="width: 12%;">Date</th>
                    <th style="width: 30%;">Particular</th>
                    <th style="width: 12%;">Type</th>
                    <th style="width: 14%;">Voucher No.</th>
                    <th style="width: 16%;">Debit (TSh)</th>
                    <th style="width: 16%;">Credit (TSh)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry['particulars'] as $particular)
                <tr>
                    <td style="font-size: 9px;">{{ $particular['date'] }}</td>
                    <td style="font-size: 9px;">{{ $particular['particular'] }}</td>
                    <td style="font-size: 9px;">{{ $particular['voucher_type'] }}</td>
                    <td style="font-size: 9px;">{{ $particular['voucher_number'] }}</td>
                    <td class="amount-debit" style="font-size: 9px;">{{ number_format($particular['debit'], 2) }}</td>
                    <td class="amount-credit" style="font-size: 9px;">{{ number_format($particular['credit'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p style="text-align: center; color: #999; font-size: 9px; margin: 10px 0;">No transactions</p>
        @endif
    </div>
    @empty
    <p style="text-align: center; padding: 20px;">No students found</p>
    @endforelse

    <!-- CLASS SUMMARY -->
    <div style="background-color: #e3f2fd; border: 2px solid #2196f3; padding: 10px; margin-bottom: 15px; margin-top: 30px; page-break-before: avoid;">
        <h4 style="text-align: center; margin: 0 0 10px 0; font-size: 12px;">OVERALL CLASS SUMMARY</h4>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 33%; text-align: center; padding: 5px;">
                    <div style="font-size: 9px; color: #666;">Total Sales (DR)</div>
                    <div style="font-size: 13px; font-weight: bold; color: #d9534f;">TSh {{ number_format($classSummary['total_sales'], 2) }}</div>
                </td>
                <td style="width: 33%; text-align: center; padding: 5px; border-left: 1px solid #2196f3; border-right: 1px solid #2196f3;">
                    <div style="font-size: 9px; color: #666;">Total Receipts (CR)</div>
                    <div style="font-size: 13px; font-weight: bold; color: #5cb85c;">TSh {{ number_format($classSummary['total_receipts'], 2) }}</div>
                </td>
                <td style="width: 33%; text-align: center; padding: 5px;">
                    <div style="font-size: 9px; color: #666;">Total Balance</div>
                    <div style="font-size: 13px; font-weight: bold; color: #0275d8;">TSh {{ number_format($classSummary['total_balance'], 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Generated by Darasa Finance System | {{ date('l, F j, Y \a\t g:i A') }}
    </div>
</body>
</html>
