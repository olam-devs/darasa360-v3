<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fee Statement - {{ $student->name }}</title>
    <style>
        @page { margin: 15mm 10mm; }
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 0; padding: 0; }
        .statement-title { font-size: 14px; font-weight: bold; margin: 8px 0; text-align: center; background: #2196f3; color: white; padding: 8px; }
        .student-info { background-color: #f5f5f5; padding: 8px; margin-bottom: 10px; border-left: 3px solid #2196f3; font-size: 9px; line-height: 1.4; }
        .section-header { background: #1976d2; color: white; padding: 6px 10px; font-weight: bold; font-size: 11px; margin-top: 15px; margin-bottom: 0; }
        .year-header { background: #42a5f5; color: white; padding: 5px 10px; font-weight: bold; font-size: 10px; }
        .transactions-table { width: 100%; border-collapse: collapse; margin: 0; }
        .transactions-table th { background-color: #e3f2fd; color: #1565c0; padding: 5px; font-size: 8px; text-align: left; border: 1px solid #ddd; }
        .transactions-table td { border: 1px solid #ddd; padding: 4px 5px; font-size: 8px; }
        .amount { text-align: right; font-weight: bold; }
        .debit { color: #d32f2f; }
        .credit { color: #388e3c; }
        .subtotal-row { background-color: #e3f2fd; font-weight: bold; font-size: 9px; }
        .total-row { background-color: #1976d2; color: white; font-weight: bold; font-size: 10px; }
        .balance-box { background-color: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin: 10px 0; text-align: center; }
        .balance-amount { font-size: 18px; font-weight: bold; color: #d9534f; }
        .paid-full { background-color: #d4edda; border-color: #28a745; }
        .footer { margin-top: 15px; padding-top: 8px; border-top: 1px solid #999; font-size: 8px; text-align: center; color: #666; }
    </style>
</head>
<body>
    @include('components.pdf-header', ['school' => $school ?? null])

    <div class="statement-title">STUDENT FEE STATEMENT</div>

    <!-- Student Information -->
    <div class="student-info">
        <strong>Student Name:</strong> {{ $student->name }}<br>
        <strong>Registration No:</strong> {{ $student->student_reg_no }}<br>
        <strong>Class:</strong> {{ $student->schoolClass->name ?? 'N/A' }}<br>
        <strong>Statement Date:</strong> {{ date('d/m/Y') }}
    </div>

    @php
        $academicYears = \App\Models\AcademicYear::orderBy('start_date', 'asc')->get();
        $feesByYear = [];
        $grandTotalFees = 0;
        $grandTotalPaid = 0;

        // Organize particulars by academic year
        foreach ($student->particulars as $particular) {
            $yearId = $particular->pivot->academic_year_id;
            $year = $academicYears->firstWhere('id', $yearId);
            $yearName = $year ? $year->name : 'Unassigned';
            $startDate = $year ? $year->start_date : null;

            if (!isset($feesByYear[$yearName])) {
                $feesByYear[$yearName] = [
                    'start_date' => $startDate,
                    'items' => [],
                    'total_fees' => 0,
                    'total_paid' => 0,
                ];
            }

            $sales = $particular->pivot->sales ?? 0;
            $credit = $particular->pivot->credit ?? 0;
            $balance = $sales - $credit;
            $deadline = $particular->pivot->deadline;
            $isOverdue = $deadline && \Carbon\Carbon::parse($deadline)->isPast() && $balance > 0;

            $feesByYear[$yearName]['items'][] = [
                'name' => $particular->name,
                'amount' => $sales,
                'paid' => $credit,
                'balance' => $balance,
                'deadline' => $deadline,
                'is_overdue' => $isOverdue,
            ];

            $feesByYear[$yearName]['total_fees'] += $sales;
            $feesByYear[$yearName]['total_paid'] += $credit;
            $grandTotalFees += $sales;
            $grandTotalPaid += $credit;
        }

        // Sort by start date (oldest first)
        uasort($feesByYear, function($a, $b) {
            if ($a['start_date'] === null) return 1;
            if ($b['start_date'] === null) return -1;
            return strtotime($a['start_date']) - strtotime($b['start_date']);
        });
    @endphp

    <!-- Fee Summary by Academic Year -->
    <div class="section-header">FEE SUMMARY BY ACADEMIC YEAR (Oldest to Newest)</div>

    @foreach($feesByYear as $yearName => $yearData)
        <div class="year-header">{{ $yearName }} - Balance: TSh {{ number_format($yearData['total_fees'] - $yearData['total_paid'], 2) }}</div>
        <table class="transactions-table">
            <thead>
                <tr>
                    <th style="width: 35%;">Fee Item</th>
                    <th style="width: 15%;">Amount</th>
                    <th style="width: 15%;">Paid</th>
                    <th style="width: 15%;">Balance</th>
                    <th style="width: 20%;">Deadline</th>
                </tr>
            </thead>
            <tbody>
                @foreach($yearData['items'] as $item)
                <tr style="{{ $item['is_overdue'] ? 'background-color: #ffebee;' : '' }}">
                    <td>
                        {{ $item['name'] }}
                        @if($item['is_overdue'])
                            <span style="color: #d32f2f; font-weight: bold; font-size: 7px;"> [OVERDUE]</span>
                        @endif
                    </td>
                    <td class="amount">TSh {{ number_format($item['amount'], 2) }}</td>
                    <td class="amount credit">TSh {{ number_format($item['paid'], 2) }}</td>
                    <td class="amount {{ $item['balance'] > 0 ? 'debit' : '' }}">TSh {{ number_format($item['balance'], 2) }}</td>
                    <td>{{ $item['deadline'] ? date('d/m/Y', strtotime($item['deadline'])) : '-' }}</td>
                </tr>
                @endforeach
                <tr class="subtotal-row">
                    <td><strong>Subtotal ({{ $yearName }})</strong></td>
                    <td class="amount">TSh {{ number_format($yearData['total_fees'], 2) }}</td>
                    <td class="amount credit">TSh {{ number_format($yearData['total_paid'], 2) }}</td>
                    <td class="amount {{ ($yearData['total_fees'] - $yearData['total_paid']) > 0 ? 'debit' : '' }}">TSh {{ number_format($yearData['total_fees'] - $yearData['total_paid'], 2) }}</td>
                    <td></td>
                </tr>
            </tbody>
        </table>
    @endforeach

    <!-- Grand Total -->
    <table class="transactions-table" style="margin-top: 10px;">
        <tbody>
            <tr class="total-row">
                <td style="width: 35%;"><strong>GRAND TOTAL (All Years)</strong></td>
                <td class="amount" style="width: 15%;">TSh {{ number_format($grandTotalFees, 2) }}</td>
                <td class="amount" style="width: 15%;">TSh {{ number_format($grandTotalPaid, 2) }}</td>
                <td class="amount" style="width: 15%;">TSh {{ number_format($grandTotalFees - $grandTotalPaid, 2) }}</td>
                <td style="width: 20%;"></td>
            </tr>
        </tbody>
    </table>

    <!-- Balance Summary -->
    <div class="balance-box {{ ($grandTotalFees - $grandTotalPaid) <= 0 ? 'paid-full' : '' }}">
        @if(($grandTotalFees - $grandTotalPaid) > 0)
            <div style="font-size: 11px; margin-bottom: 5px; font-weight: bold;">TOTAL OUTSTANDING BALANCE:</div>
            <div class="balance-amount">TSh {{ number_format($grandTotalFees - $grandTotalPaid, 2) }}</div>
            <div style="margin-top: 8px; font-size: 9px; color: #666;">
                Please ensure payment is made as soon as possible. Older balances should be cleared first.
            </div>
        @else
            <div style="font-size: 14px; color: #155724; font-weight: bold;">ALL FEES PAID IN FULL</div>
            <div style="margin-top: 3px; font-size: 10px; color: #155724;">
                Thank you for your prompt payment!
            </div>
        @endif
    </div>

    <!-- Transaction History -->
    @if($transactions && count($transactions) > 0)
        <div class="section-header">TRANSACTION HISTORY</div>
        <table class="transactions-table">
            <thead>
                <tr>
                    <th style="width: 15%;">Date</th>
                    <th style="width: 20%;">Voucher No</th>
                    <th style="width: 25%;">Particular</th>
                    <th style="width: 15%;">Debit</th>
                    <th style="width: 15%;">Credit</th>
                    <th style="width: 10%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                @php $runningBalance = 0; @endphp
                @foreach($transactions as $transaction)
                    @php $runningBalance += $transaction->debit - $transaction->credit; @endphp
                    <tr>
                        <td>{{ date('d/m/Y', strtotime($transaction->date)) }}</td>
                        <td>{{ $transaction->voucher_number ?? '-' }}</td>
                        <td>{{ $transaction->particular->name ?? 'General' }}</td>
                        <td class="amount debit">{{ $transaction->debit > 0 ? 'TSh ' . number_format($transaction->debit, 2) : '-' }}</td>
                        <td class="amount credit">{{ $transaction->credit > 0 ? 'TSh ' . number_format($transaction->credit, 2) : '-' }}</td>
                        <td class="amount">TSh {{ number_format($runningBalance, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="footer">
        Generated by Darasa Finance System | {{ date('l, F j, Y \a\t g:i A') }}<br>
        For inquiries, please contact the school office.
    </div>
</body>
</html>
