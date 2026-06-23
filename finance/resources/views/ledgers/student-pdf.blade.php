<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Student Ledger - {{ $student->name }}</title>
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
        .student-info {
            margin-bottom: 15px;
        }
        .student-info table {
            width: 100%;
        }
        .student-info td {
            padding: 3px;
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
        .totals-row {
            background-color: #f9f9f9;
            font-weight: bold;
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

    <div class="ledger-title">STUDENT LEDGER</div>

    <div class="student-info">
        <table>
            <tr>
                <td style="width: 20%;"><strong>Student Name:</strong></td>
                <td style="width: 30%;">{{ $student->name }}</td>
                <td style="width: 20%;"><strong>Reg. Number:</strong></td>
                <td style="width: 30%;">{{ $student->student_reg_no }}</td>
            </tr>
            <tr>
                <td><strong>Class:</strong></td>
                <td>{{ $student->schoolClass->name ?? 'N/A' }}</td>
                <td><strong>Date Range:</strong></td>
                <td>{{ $dateRange }}</td>
            </tr>
        </table>
    </div>

    <table class="ledger-table">
        <!-- SALES ASSIGNED SECTION -->
        <thead>
            <tr style="background-color: #ffcccc;">
                <th colspan="8" style="text-align: left; padding: 8px; font-size: 12px;">SALES ASSIGNED</th>
            </tr>
            <tr>
                <th style="width: 8%;">Date</th>
                <th style="width: 18%;">Particular</th>
                <th style="width: 10%;">Type</th>
                <th style="width: 12%;">Voucher No.</th>
                <th style="width: 13%;">Debit (TSh)</th>
                <th style="width: 13%;">Credit (TSh)</th>
                <th style="width: 13%;">Balance (TSh)</th>
                <th style="width: 13%;">Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($salesData as $sale)
            <tr style="background-color: #fff5f5;">
                <td>{{ $sale['date'] }}</td>
                <td><strong>{{ $sale['particular'] }}</strong></td>
                <td>{{ $sale['voucher_type'] }}</td>
                <td>{{ $sale['voucher_number'] }}</td>
                <td class="amount-debit">{{ number_format($sale['debit'], 2) }}</td>
                <td class="amount-credit">{{ number_format($sale['credit'], 2) }}</td>
                <td class="amount-balance">{{ number_format($sale['balance'], 2) }}</td>
                <td style="font-size: 9px;">{{ $sale['notes'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 10px;">No sales assigned</td>
            </tr>
            @endforelse
        </tbody>

        <!-- PAYMENT ENTRIES SECTION -->
        <thead>
            <tr style="background-color: #ccffcc;">
                <th colspan="8" style="text-align: left; padding: 8px; font-size: 12px;">PAYMENT ENTRIES</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entryData as $entry)
            <tr>
                <td>{{ $entry['date'] }}</td>
                <td>{{ $entry['particular'] }}</td>
                <td>{{ $entry['voucher_type'] }}</td>
                <td>{{ $entry['voucher_number'] }}</td>
                <td class="amount-debit">{{ number_format($entry['debit'], 2) }}</td>
                <td class="amount-credit">{{ number_format($entry['credit'], 2) }}</td>
                <td class="amount-balance">{{ number_format($entry['balance'], 2) }}</td>
                <td style="font-size: 9px;">{{ $entry['notes'] }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 10px;">No payment entries</td>
            </tr>
            @endforelse
        </tbody>

        <!-- TOTALS -->
        <tfoot>
            <tr class="totals-row">
                <td colspan="4" style="text-align: right; padding-right: 10px;"><strong>TOTALS:</strong></td>
                <td class="amount-debit"><strong>{{ number_format($totalDebit, 2) }}</strong></td>
                <td class="amount-credit"><strong>{{ number_format($totalCredit, 2) }}</strong></td>
                <td class="amount-balance"><strong>{{ number_format($balance, 2) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 20px; padding: 10px; background-color: #e3f2fd; border: 2px solid #2196f3; text-align: center;">
        <strong style="font-size: 12px;">CLOSING BALANCE (Outstanding): TSh {{ number_format($balance, 2) }}</strong>
    </div>

    <div class="footer">
        Generated by Darasa Finance System | {{ date('l, F j, Y \a\t g:i A') }}
    </div>
</body>
</html>
