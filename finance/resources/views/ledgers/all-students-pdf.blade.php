<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>All Students Ledgers</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 10px;
            margin: 15px;
        }
        .main-title {
            font-size: 13px;
            font-weight: bold;
            margin: 10px 0;
            text-align: center;
            text-decoration: underline;
        }
        .student-section {
            page-break-inside: avoid;
            margin-bottom: 25px;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .student-header {
            background-color: #f5f5f5;
            padding: 8px;
            margin-bottom: 10px;
            border-left: 4px solid #0275d8;
        }
        .student-name {
            font-size: 12px;
            font-weight: bold;
            color: #333;
        }
        .student-details {
            font-size: 9px;
            color: #666;
            margin-top: 3px;
        }
        .ledger-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }
        .ledger-table th {
            background-color: #f0f0f0;
            border: 1px solid #333;
            padding: 5px;
            font-size: 9px;
            font-weight: bold;
        }
        .ledger-table td {
            border: 1px solid #999;
            padding: 4px;
            font-size: 9px;
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
        .no-transactions {
            text-align: center;
            padding: 15px;
            color: #999;
            font-style: italic;
        }
        .page-break {
            page-break-after: always;
        }
        .footer {
            margin-top: 15px;
            padding-top: 8px;
            border-top: 1px solid #999;
            font-size: 8px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    @include('components.pdf-header', ['school' => $school ?? null])

    <div class="main-title">ALL STUDENTS INDIVIDUAL LEDGERS</div>
    <div style="text-align: center; font-size: 9px; margin-bottom: 15px;">
        <strong>Print Date:</strong> {{ date('d/m/Y H:i') }}
    </div>

    @foreach($allLedgers as $index => $studentLedger)
    <!-- Each student ledger on its own page -->
    @if($index > 0)
    <div style="page-break-before: always;"></div>
    @include('components.pdf-header', ['school' => $school ?? null])
    <div class="main-title">STUDENT LEDGER</div>
    @endif

    <div class="student-section">
        <div class="student-header">
            <div class="student-name">{{ $studentLedger['student']->name }}</div>
            <div class="student-details">
                Reg. No: {{ $studentLedger['student']->student_reg_no }} |
                Class: {{ $studentLedger['student']->schoolClass->name ?? 'N/A' }}
            </div>
        </div>

        @if(count($studentLedger['ledgerData']) > 0)
        <table class="ledger-table">
            <thead>
                <tr>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 20%;">Particular</th>
                    <th style="width: 13%;">Type</th>
                    <th style="width: 15%;">Voucher No.</th>
                    <th style="width: 14%;">Debit (TSh)</th>
                    <th style="width: 14%;">Credit (TSh)</th>
                    <th style="width: 14%;">Balance (TSh)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($studentLedger['ledgerData'] as $entry)
                <tr>
                    <td>{{ $entry['date'] }}</td>
                    <td>{{ $entry['particular'] }}</td>
                    <td>{{ $entry['voucher_type'] }}</td>
                    <td>{{ $entry['voucher_number'] }}</td>
                    <td class="amount-debit">{{ number_format($entry['debit'], 2) }}</td>
                    <td class="amount-credit">{{ number_format($entry['credit'], 2) }}</td>
                    <td class="amount-balance">{{ number_format($entry['balance'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="totals-row">
                    <td colspan="4" style="text-align: right; padding-right: 8px;"><strong>TOTALS:</strong></td>
                    <td class="amount-debit"><strong>{{ number_format($studentLedger['totalDebit'], 2) }}</strong></td>
                    <td class="amount-credit"><strong>{{ number_format($studentLedger['totalCredit'], 2) }}</strong></td>
                    <td class="amount-balance"><strong>{{ number_format($studentLedger['balance'], 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>
        @else
        <div class="no-transactions">No transactions recorded for this student</div>
        @endif
    </div>
    @endforeach

    <div class="footer">
        Generated by Darasa Finance System | {{ date('l, F j, Y \a\t g:i A') }} | Total Students: {{ count($allLedgers) }}
    </div>
</body>
</html>
