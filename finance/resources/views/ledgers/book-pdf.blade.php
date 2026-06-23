<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Book Ledger - {{ $book->name }}</title>
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
        .book-info {
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
        /* Entry type color highlights */
        .row-expense {
            background-color: #ffebee !important; /* Light red */
        }
        .row-expense td {
            color: #c62828;
        }
        .row-suspense {
            background-color: #fff8e1 !important; /* Light yellow */
        }
        .row-suspense td {
            color: #f57c00;
        }
        .row-suspense-resolution {
            background-color: #e8f5e9 !important; /* Light green */
        }
        .row-suspense-resolution td {
            color: #2e7d32;
        }
        .row-deposit {
            background-color: #e3f2fd !important; /* Light blue */
        }
        .row-deposit td {
            color: #1565c0;
        }
        .row-withdrawal {
            background-color: #fce4ec !important; /* Light pink */
        }
        .row-withdrawal td {
            color: #ad1457;
        }
        /* Page break helpers */
        .page-opening {
            background-color: #e1f5fe !important;
            border: 2px solid #039be5;
        }
        .page-closing {
            background-color: #fff3e0 !important;
            border: 2px solid #ff9800;
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

    <div class="ledger-title">BOOK LEDGER</div>

    <div class="book-info">
        <strong>Book:</strong> {{ $book->name }}
        @if($book->bank_account_number)
        | <strong>Account:</strong> {{ $book->bank_account_number }}
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
                <th style="width: 7%;">Date</th>
                <th style="width: 16%;">Student / Particular</th>
                <th style="width: 15%;">Detail</th>
                <th style="width: 9%;">Type</th>
                <th style="width: 10%;">Voucher No.</th>
                @if($viewType === 'cash')
                <th style="width: 10%;">DR (Rec/In)</th>
                <th style="width: 10%;">CR (Pay/Out)</th>
                @else
                <th style="width: 10%;">DR (Pay/Out)</th>
                <th style="width: 10%;">CR (Rec/In)</th>
                @endif
                <th style="width: 10%;">Balance (TSh)</th>
                <th style="width: 13%;">Notes</th>
            </tr>
        </thead>
        <tbody>
            @forelse($ledgerData as $entry)
                @if(isset($entry['is_month_start']) && $entry['is_month_start'])
                    <tr style="background-color: #e8f5e9; font-weight: bold;">
                        <td colspan="7" style="text-align: left; padding: 8px; color: #2e7d32;">
                            📅 {{ $entry['month'] }} - OPENING BALANCE
                        </td>
                        <td class="amount-balance" style="background-color: #c8e6c9;">{{ number_format($entry['opening_balance'], 2) }}</td>
                        <td></td>
                    </tr>
                @elseif(isset($entry['is_month_end']) && $entry['is_month_end'])
                    <tr style="background-color: #fff3e0; font-weight: bold;">
                        <td colspan="5" style="text-align: left; padding: 8px; color: #e65100;">
                            📅 {{ $entry['month'] }} - CLOSING BALANCE
                        </td>
                        <td class="amount-debit" style="background-color: #ffe0b2;">{{ number_format($entry['monthly_debit'], 2) }}</td>
                        <td class="amount-credit" style="background-color: #ffe0b2;">{{ number_format($entry['monthly_credit'], 2) }}</td>
                        <td class="amount-balance" style="background-color: #ffcc80;">{{ number_format($entry['closing_balance'], 2) }}</td>
                        <td></td>
                    </tr>
                @else
                    @php
                        // Determine row class based on entry type
                        $rowClass = '';
                        $particular = $entry['particular'] ?? '';
                        $paymentTo = $entry['student'] ?? '';

                        if ($particular === 'Expense' || (strpos($paymentTo, 'Expense') !== false)) {
                            $rowClass = 'row-expense';
                        } elseif ($particular === 'Suspense Account' || $paymentTo === 'Suspense Account') {
                            $rowClass = 'row-suspense';
                        } elseif ($particular === 'Suspense Resolution' || $particular === 'Suspense Reversal') {
                            $rowClass = 'row-suspense-resolution';
                        } elseif ($particular === 'Bank Deposit' || $paymentTo === 'Bank Deposit') {
                            $rowClass = 'row-deposit';
                        } elseif ($particular === 'Bank Withdrawal' || $paymentTo === 'Bank Withdrawal') {
                            $rowClass = 'row-withdrawal';
                        }
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <td>{{ $entry['date'] }}</td>
                        <td>
                            @if(isset($entry['student']) && $entry['student'] !== 'N/A')
                                {{ $entry['student'] }}
                            @else
                                {{ $entry['particular'] }}
                            @endif
                        </td>
                        <td>{{ $entry['particular'] }}</td>
                        <td>{{ $entry['voucher_type'] }}</td>
                        <td>{{ $entry['voucher_number'] }}</td>
                        <td class="amount-debit">{{ number_format($entry['debit'], 2) }}</td>
                        <td class="amount-credit">{{ number_format($entry['credit'], 2) }}</td>
                        <td class="amount-balance">{{ number_format($entry['balance'], 2) }}</td>
                        <td style="font-size: 8px;">{{ $entry['notes'] ?? '' }}</td>
                    </tr>
                @endif
            @empty
            <tr>
                <td colspan="9" style="text-align: center; padding: 20px;">No transactions found</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td colspan="5" style="text-align: right; padding-right: 10px;"><strong>TOTALS:</strong></td>
                <td class="amount-debit"><strong>{{ number_format($totalReceipts, 2) }}</strong></td>
                <td class="amount-credit"><strong>{{ number_format($totalPayments, 2) }}</strong></td>
                <td class="amount-balance"><strong>{{ number_format($balance, 2) }}</strong></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 20px; padding: 10px; background-color: #e3f2fd; border: 2px solid #2196f3; text-align: center;">
        <strong style="font-size: 12px;">CLOSING BALANCE: TSh {{ number_format($balance, 2) }}</strong>
    </div>

    <!-- Color Legend -->
    <div style="margin-top: 15px; padding: 10px; border: 1px solid #ccc; font-size: 9px;">
        <strong>Legend:</strong>
        <span style="display: inline-block; margin-left: 15px; padding: 2px 6px; background-color: #ffebee; color: #c62828;">Expense</span>
        <span style="display: inline-block; margin-left: 10px; padding: 2px 6px; background-color: #fff8e1; color: #f57c00;">Suspense</span>
        <span style="display: inline-block; margin-left: 10px; padding: 2px 6px; background-color: #e8f5e9; color: #2e7d32;">Resolved</span>
        <span style="display: inline-block; margin-left: 10px; padding: 2px 6px; background-color: #e3f2fd; color: #1565c0;">Deposit</span>
        <span style="display: inline-block; margin-left: 10px; padding: 2px 6px; background-color: #fce4ec; color: #ad1457;">Withdrawal</span>
        <span style="display: inline-block; margin-left: 10px; padding: 2px 6px; background-color: #e8f5e9; color: #2e7d32;">Month Start</span>
        <span style="display: inline-block; margin-left: 10px; padding: 2px 6px; background-color: #fff3e0; color: #e65100;">Month End</span>
    </div>

    <div class="footer">
        Generated by Darasa Finance System | {{ date('l, F j, Y \a\t g:i A') }}
    </div>
</body>
</html>
