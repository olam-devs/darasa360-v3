<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Particular Ledger - {{ $particular->name }}</title>
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
        .particular-info {
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
            font-size: 9px;
            font-weight: bold;
        }
        .ledger-table td {
            border: 1px solid #999;
            padding: 5px;
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
        .month-start-row {
            background-color: #e3f2fd;
            border: 2px solid #2196f3;
            font-weight: bold;
        }
        .month-end-row {
            background-color: #fff3e0;
            border: 2px solid #ff9800;
            font-weight: bold;
        }
        .month-end-balance-row {
            background-color: #ffe0b2;
            border: 2px solid #f57c00;
            font-weight: bold;
            font-size: 11px;
        }
        .page-balance-row {
            background-color: #e1bee7;
            border: 2px solid #9c27b0;
            font-weight: bold;
            font-size: 11px;
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

    <div class="ledger-title">PARTICULAR LEDGER</div>

    <div class="particular-info">
        <strong>Particular:</strong> {{ $particular->name }}
        @if(isset($particular->amount) && $particular->amount)
        | <strong>Standard Amount:</strong> TSh {{ number_format($particular->amount, 2) }}
        @endif
        <br>
        <strong>Date Range:</strong> {{ $dateRange }}
    </div>

    <div style="background-color: #e3f2fd; border: 2px solid #2196f3; padding: 10px; margin-bottom: 15px; text-align: center;">
        <strong style="font-size: 12px;">OPENING BALANCE: TSh {{ number_format($openingBalance, 2) }}</strong>
    </div>

    <table class="ledger-table">
        <thead>
            <tr>
                <th style="width: 10%;">Date</th>
                <th style="width: 20%;">Student</th>
                <th style="width: 10%;">Class</th>
                <th style="width: 12%;">Book</th>
                <th style="width: 10%;">Type</th>
                <th style="width: 12%;">DR (TSh)</th>
                <th style="width: 12%;">CR (TSh)</th>
                <th style="width: 14%;">Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vouchers as $voucher)
                @if(isset($voucher->is_page_opening) && $voucher->is_page_opening)
                    <tr style="background-color: #e1bee7; border: 2px solid #9c27b0;">
                        <td colspan="5" style="padding: 8px; font-size: 10px; font-weight: bold; color: #4a148c;">
                            Page Opening Balance
                        </td>
                        <td colspan="3" style="text-align: right; padding: 8px; font-size: 10px; font-weight: bold; color: #4a148c;">
                            TSh {{ number_format($voucher->opening_balance, 2) }}
                        </td>
                    </tr>
                @elseif(isset($voucher->is_page_closing) && $voucher->is_page_closing)
                    <tr style="background-color: #e1bee7; border: 2px solid #9c27b0;">
                        <td colspan="5" style="text-align: right; padding: 8px; font-size: 10px; font-weight: bold; color: #4a148c;">
                            Page Closing Balance:
                        </td>
                        <td colspan="3" style="text-align: right; padding: 8px; font-size: 10px; font-weight: bold; color: #4a148c;">
                            TSh {{ number_format($voucher->closing_balance, 2) }}
                        </td>
                    </tr>
                @elseif(isset($voucher->is_month_start) && $voucher->is_month_start)
                    <tr class="month-start-row">
                        <td colspan="5" style="padding: 8px; font-size: 10px;">
                            <strong>Month Start - {{ $voucher->month }}</strong>
                        </td>
                        <td colspan="3" style="text-align: right; padding: 8px; font-size: 10px;">
                            Opening Balance: TSh {{ number_format($voucher->opening_balance, 2) }}
                        </td>
                    </tr>
                @elseif(isset($voucher->is_month_end) && $voucher->is_month_end)
                    <tr class="month-end-row">
                        <td colspan="5" style="padding: 6px; font-size: 10px;">
                            <strong>Month End - {{ $voucher->month }}</strong>
                        </td>
                        <td style="text-align: right; padding: 6px; font-size: 9px;">
                            {{ number_format($voucher->monthly_debit, 2) }}
                        </td>
                        <td style="text-align: right; padding: 6px; font-size: 9px;">
                            {{ number_format($voucher->monthly_credit, 2) }}
                        </td>
                        <td></td>
                    </tr>
                    <tr class="month-end-balance-row">
                        <td colspan="5" style="text-align: right; padding: 8px;">
                            <strong>CLOSING BALANCE:</strong>
                        </td>
                        <td colspan="3" style="text-align: right; padding: 8px;">
                            <strong>TSh {{ number_format($voucher->closing_balance, 2) }}</strong>
                        </td>
                    </tr>
                @else
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($voucher->date)->format('d/m/Y') }}</td>
                        <td>{{ $voucher->student ? $voucher->student->name : 'N/A' }}</td>
                        <td>{{ $voucher->student && $voucher->student->schoolClass ? $voucher->student->schoolClass->name : 'N/A' }}</td>
                        <td>{{ $voucher->book ? $voucher->book->name : 'N/A' }}</td>
                        <td>{{ $voucher->voucher_type }}</td>
                        <td class="amount-debit">{{ number_format($voucher->display_debit ?? 0, 2) }}</td>
                        <td class="amount-credit">{{ number_format($voucher->display_credit ?? 0, 2) }}</td>
                        <td style="font-size: 8px;">{{ $voucher->notes ?? '' }}</td>
                    </tr>
                @endif
            @empty
            <tr>
                <td colspan="8" style="text-align: center; padding: 20px;">No transactions found</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td colspan="5" style="text-align: right; padding-right: 10px;"><strong>TOTALS:</strong></td>
                <td class="amount-debit"><strong>{{ number_format($totalDebit, 2) }}</strong></td>
                <td class="amount-credit"><strong>{{ number_format($totalCredit, 2) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 20px; padding: 10px; background-color: #e3f2fd; border: 2px solid #2196f3; text-align: center;">
        <strong style="font-size: 12px;">CLOSING BALANCE: TSh {{ number_format($balance, 2) }}</strong>
    </div>

    <div class="footer">
        Generated by Darasa Finance System | {{ date('l, F j, Y \a\t g:i A') }}
    </div>
</body>
</html>
