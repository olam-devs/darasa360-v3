<div class="invoice-title">{{ $invoiceHeading ?? 'FEE STATEMENT' }}</div>

<div class="student-info">
    <strong>Student Name:</strong> {{ $student->name }}<br>
    <strong>Class:</strong> {{ $student->schoolClass->name ?? 'N/A' }}<br>
    <strong>Date:</strong> {{ date('d/m/Y') }}
    @if(isset($invoiceData['has_scholarships']) && $invoiceData['has_scholarships'])
        <br><span class="scholarship-badge" style="font-size: 9px; padding: 2px 6px;"> SCHOLARSHIP RECIPIENT</span>
    @endif
</div>

@if(isset($invoiceData['has_scholarships']) && $invoiceData['has_scholarships'])
<div class="scholarship-summary">
    <div style="font-weight: bold; font-size: 10px; color: #856404; margin-bottom: 4px;"> SCHOLARSHIP SUMMARY</div>
    <table style="width: 100%; font-size: 9px;">
        <tr>
            <td>Original Fee Total:</td>
            <td style="text-align: right; font-weight: bold;">TSh {{ number_format($invoiceData['total_original_fees'], 2) }}</td>
        </tr>
        <tr style="color: #28a745;">
            <td>Scholarship Amount (Forgiven):</td>
            <td style="text-align: right; font-weight: bold;">- TSh {{ number_format($invoiceData['total_scholarship_amount'], 2) }}</td>
        </tr>
        <tr style="border-top: 1px solid #ffc107; font-weight: bold;">
            <td>Net Fee Amount:</td>
            <td style="text-align: right;">TSh {{ number_format($invoiceData['total_fees'], 2) }}</td>
        </tr>
    </table>
</div>
@endif

@php $useQuarterFormat = isset($invoiceData['use_quarter_format']) && $invoiceData['use_quarter_format']; @endphp

@if($useQuarterFormat)
{{-- ── QUARTER-BASED INVOICE ─────────────────────────────── --}}
@php
    $activeQuarters = $invoiceData['active_quarters'] ?? [];
    $quarterLabels  = $invoiceData['quarter_labels']  ?? [];
@endphp

@if(count($invoiceData['items_by_year'] ?? []) > 0)
    @foreach($invoiceData['items_by_year'] as $yearData)
    @php
        $yq = $yearData['active_quarters'];
        $ql = $quarterLabels;
    @endphp
    <div class="year-block">
        <div class="year-header">
            Academic Year: {{ $yearData['year_name'] }}
            @if($yearData['subtotal_balance'] > 0)
                <span class="year-badge year-badge-due">Balance: TSh {{ number_format($yearData['subtotal_balance'], 2) }}</span>
            @else
                <span class="year-badge year-badge-paid">Paid</span>
            @endif
        </div>

        @php
            $qCount   = count($yq);
            $showTotal = $qCount > 1;           // hide Total column when only 1 quarter
            $qW       = 16;                     // fixed per-quarter column width %
            $totalW   = $showTotal ? 12 : 0;
            $feeItemW = max(20, 100 - 9 - $totalW - ($qCount * $qW));
        @endphp
        <table class="fees-table" style="margin-top: 0; table-layout: fixed; width: 100%;">
            <colgroup>
                <col style="width: {{ $feeItemW }}%;">
                <col style="width: 9%;">
                @foreach($yq as $q)
                    <col style="width: {{ $qW }}%;">
                @endforeach
                @if($showTotal)<col style="width: 12%;">@endif
            </colgroup>
            <thead>
                <tr>
                    <th style="text-align: left;">FEE ITEM</th>
                    <th style="text-align: center;">STATUS</th>
                    @foreach($yq as $q)
                        <th style="text-align: right;">{{ $ql[$q] ?? 'Q'.$q }}</th>
                    @endforeach
                    @if($showTotal)<th style="text-align: right;">Total</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($yearData['particulars'] as $part)
                {{-- Row 1: Required — name cell spans 2 rows --}}
                <tr>
                    <td rowspan="2" style="font-weight:bold; vertical-align:middle; border-right:2px solid #ddd;">
                        {{ $part['name'] }}
                        @if(!empty($part['has_scholarship']))
                            <span class="scholarship-badge"> {{ $part['scholarship_type'] === 'full' ? 'FULL' : 'PARTIAL' }}</span>
                        @endif
                    </td>
                    <td style="text-align:center; font-size:7.5px; color:#555; background:#f5f5f5;">Required</td>
                    @foreach($yq as $q)
                        <td class="amount">
                            @if(isset($part['quarters'][$q]))
                                TSh {{ number_format($part['quarters'][$q]['charged'], 2) }}
                                @if(!empty($part['quarters'][$q]['deadline']) && $part['quarters'][$q]['paid'] < $part['quarters'][$q]['charged'])
                                    <div style="font-size:6px; font-weight:normal; color:#1565c0; background:#e3f2fd; border-radius:2px; padding:1px 3px; margin-top:2px; white-space:nowrap;">
                                        Due: {{ date('d M Y', strtotime($part['quarters'][$q]['deadline'])) }}
                                    </div>
                                @endif
                            @else
                                <span style="color:#bbb;">—</span>
                            @endif
                        </td>
                    @endforeach
                    @if($showTotal)<td class="amount">TSh {{ number_format($part['total_charged'], 2) }}</td>@endif
                </tr>
                {{-- Row 2: Paid --}}
                <tr class="paid-row">
                    <td style="text-align:center; font-size:7.5px; color:#444; background:#f1f8e9;">Paid</td>
                    @foreach($yq as $q)
                        <td class="amount">
                            @if(isset($part['quarters'][$q]))
                                TSh {{ number_format($part['quarters'][$q]['paid'], 2) }}
                            @else
                                <span style="color:#bbb;">—</span>
                            @endif
                        </td>
                    @endforeach
                    @if($showTotal)<td class="amount">TSh {{ number_format($part['total_paid'], 2) }}</td>@endif
                </tr>
                @endforeach

                {{-- Quarter totals section — pale colours, dark text --}}
                <tr class="total-row" style="background-color:#bbdefb; color:#0d47a1;">
                    <td colspan="2"><strong>Total Required</strong></td>
                    @foreach($yq as $q)
                        <td class="amount">TSh {{ number_format($yearData['quarter_totals'][$q]['charged'] ?? 0, 2) }}</td>
                    @endforeach
                    @if($showTotal)<td class="amount">TSh {{ number_format($yearData['subtotal_fees'], 2) }}</td>@endif
                </tr>
                <tr class="total-row" style="background-color:#c8e6c9; color:#1b5e20;">
                    <td colspan="2"><strong>Total Paid</strong></td>
                    @foreach($yq as $q)
                        <td class="amount">TSh {{ number_format($yearData['quarter_totals'][$q]['paid'] ?? 0, 2) }}</td>
                    @endforeach
                    @if($showTotal)<td class="amount">TSh {{ number_format($yearData['subtotal_paid'], 2) }}</td>@endif
                </tr>
                @php $balColor = $yearData['subtotal_balance'] > 0 ? '#ffcdd2' : '#c8e6c9'; $balText = $yearData['subtotal_balance'] > 0 ? '#b71c1c' : '#1b5e20'; @endphp
                <tr class="total-row" style="background-color:{{ $balColor }}; color:{{ $balText }};">
                    <td colspan="2"><strong>Balance Remaining</strong></td>
                    @foreach($yq as $q)
                        <td class="amount">TSh {{ number_format($yearData['quarter_totals'][$q]['remaining'] ?? 0, 2) }}</td>
                    @endforeach
                    @if($showTotal)<td class="amount">TSh {{ number_format($yearData['subtotal_balance'], 2) }}</td>@endif
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach

    {{-- Grand total (only if more than one year) --}}
    @if(count($invoiceData['items_by_year']) > 1)
    <table class="fees-table">
        <tbody>
            @php $grandShowTotal = count($activeQuarters) > 1; @endphp
            <tr class="total-row" style="background-color:#bbdefb; color:#0d47a1;">
                <td colspan="2"><strong>GRAND TOTAL (All Years)</strong></td>
                @foreach($activeQuarters as $q)
                    @php
                        $grandCharged = collect($invoiceData['items_by_year'])->sum(fn($y) => $y['quarter_totals'][$q]['charged'] ?? 0);
                    @endphp
                    <td class="amount">TSh {{ number_format($grandCharged, 2) }}</td>
                @endforeach
                @if($grandShowTotal)<td class="amount">TSh {{ number_format($invoiceData['total_fees'], 2) }}</td>@endif
            </tr>
        </tbody>
    </table>
    @endif
@else
    <table class="fees-table">
        <tbody>
            <tr><td colspan="{{ 2 + count($activeQuarters) }}" style="text-align: center; padding: 15px;">No fees assigned yet</td></tr>
        </tbody>
    </table>
@endif

@else
{{-- ── LEGACY INVOICE (no quarters) ─────────────────────── --}}
@if(isset($invoiceData['items_by_year']) && count($invoiceData['items_by_year']) > 0)
    @foreach($invoiceData['items_by_year'] as $yearData)
    <div class="year-block">
        <div class="year-header">
            Academic Year: {{ $yearData['year_name'] }}
            @if($yearData['subtotal_balance'] > 0)
                <span class="year-badge year-badge-due">Invoice Amount: TSh {{ number_format($yearData['subtotal_balance'], 2) }}</span>
            @else
                <span class="year-badge year-badge-paid">Paid</span>
            @endif
        </div>
        <table class="fees-table" style="margin-top: 0;">
            <thead>
                <tr>
                    <th style="width: 40%;">Fee Item</th>
                    <th style="width: 20%;">Amount Required</th>
                    <th style="width: 20%;">Amount Paid</th>
                    <th style="width: 20%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($yearData['items'] as $item)
                <tr class="{{ $item['is_overdue'] ? 'overdue' : '' }} {{ isset($item['has_scholarship']) && $item['has_scholarship'] ? 'scholarship-row' : '' }}">
                    <td>
                        {{ $item['name'] }}
                        @if(isset($item['has_scholarship']) && $item['has_scholarship'])
                            <span class="scholarship-badge"> {{ $item['scholarship_type'] === 'full' ? 'FULL' : 'PARTIAL' }}</span>
                            @if($item['scholarship_name'])
                                <br><small style="color: #856404; font-size: 7px;">{{ $item['scholarship_name'] }}</small>
                            @endif
                        @endif
                        @if($item['deadline'])
                            <br><small style="color: #666; font-size: 8px;">Deadline: {{ date('d/m/Y', strtotime($item['deadline'])) }}</small>
                            @if($item['is_overdue'])
                                <br><small style="color: #d9534f; font-weight: bold; font-size: 8px;">OVERDUE</small>
                            @endif
                        @endif
                    </td>
                    <td class="amount">
                        @if(isset($item['has_scholarship']) && $item['has_scholarship'] && $item['scholarship_amount'] > 0)
                            <span class="original-amount">TSh {{ number_format($item['original_amount'], 2) }}</span><br>
                        @endif
                        TSh {{ number_format($item['amount'], 2) }}
                        @if(isset($item['has_scholarship']) && $item['has_scholarship'] && $item['scholarship_amount'] > 0)
                            <br><small style="color: #28a745; font-size: 7px;">(-{{ number_format($item['scholarship_amount'], 2) }} scholarship)</small>
                        @endif
                    </td>
                    <td class="amount">TSh {{ number_format($item['paid'], 2) }}</td>
                    <td class="amount">TSh {{ number_format($item['balance'], 2) }}</td>
                </tr>
                @endforeach
                <tr style="background-color: #e3f2fd; font-weight: bold;">
                    <td>Subtotal ({{ $yearData['year_name'] }})</td>
                    <td class="amount">TSh {{ number_format($yearData['subtotal_fees'], 2) }}</td>
                    <td class="amount">TSh {{ number_format($yearData['subtotal_paid'], 2) }}</td>
                    <td class="amount">TSh {{ number_format($yearData['subtotal_balance'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endforeach

    <table class="fees-table">
        <tbody>
            <tr class="total-row" style="background-color: #1976d2; color: white;">
                <td style="width: 40%;"><strong>GRAND TOTAL (All Academic Years)</strong></td>
                <td class="amount" style="width: 20%;">TSh {{ number_format($invoiceData['total_fees'], 2) }}</td>
                <td class="amount" style="width: 20%;">TSh {{ number_format($invoiceData['total_paid'], 2) }}</td>
                <td class="amount" style="width: 20%;">TSh {{ number_format($invoiceData['balance_remaining'], 2) }}</td>
            </tr>
        </tbody>
    </table>
@else
    <table class="fees-table">
        <thead>
            <tr>
                <th style="width: 40%;">Fee Item</th>
                <th style="width: 20%;">Amount Required</th>
                <th style="width: 20%;">Amount Paid</th>
                <th style="width: 20%;">Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($invoiceData['items'] as $item)
            <tr class="{{ $item['is_overdue'] ? 'overdue' : '' }}">
                <td>
                    {{ $item['name'] }}
                    @if($item['deadline'])
                        <br><small style="color: #666; font-size: 8px;">Deadline: {{ date('d/m/Y', strtotime($item['deadline'])) }}</small>
                        @if($item['is_overdue'])
                            <br><small style="color: #d9534f; font-weight: bold; font-size: 8px;">OVERDUE</small>
                        @endif
                    @endif
                </td>
                <td class="amount">TSh {{ number_format($item['amount'], 2) }}</td>
                <td class="amount">TSh {{ number_format($item['paid'], 2) }}</td>
                <td class="amount">TSh {{ number_format($item['balance'], 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; padding: 15px;">No fees assigned yet</td>
            </tr>
            @endforelse

            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td class="amount">TSh {{ number_format($invoiceData['total_fees'], 2) }}</td>
                <td class="amount">TSh {{ number_format($invoiceData['total_paid'], 2) }}</td>
                <td class="amount">TSh {{ number_format($invoiceData['balance_remaining'], 2) }}</td>
            </tr>
        </tbody>
    </table>
@endif
@endif
{{-- ── END FORMAT SWITCH ─────────────────────────────────── --}}

<div class="balance-box {{ $invoiceData['balance_remaining'] <= 0 ? 'paid-full' : '' }}">
    @if($invoiceData['balance_remaining'] > 0)
        <div style="font-size: 11px; margin-bottom: 5px; font-weight: bold;">TOTAL AMOUNT DUE:</div>
        <div class="balance-amount">TSh {{ number_format($invoiceData['balance_remaining'], 2) }}</div>
        <div style="margin-top: 8px; font-size: 9px; color: #666;">
            Please ensure payment is made by the deadline date(s) indicated above.
        </div>

        @if(isset($bankAccounts) && $bankAccounts->count() > 0)
        <div class="bank-section">
            <div style="font-size: 10px; font-weight: bold; color: #333; margin-bottom: 4px;"> PAYMENT DETAILS:</div>
            @foreach($bankAccounts as $index => $bankAccount)
            <div class="bank-option">
                <strong>{{ $index + 1 }}.</strong> {{ $bankAccount->bank_name }}: {{ $bankAccount->account_number }}
            </div>
            @endforeach
        </div>
        @endif
    @else
        <div style="font-size: 14px; color: #155724; font-weight: bold;"> ALL FEES PAID IN FULL</div>
        <div style="margin-top: 3px; font-size: 10px; color: #155724;">
            Thank you for your prompt payment!
        </div>
    @endif
</div>

<div class="footer">
    Generated by Darasa 360 Finance System<br>
    For inquiries, please contact the school office.
</div>
