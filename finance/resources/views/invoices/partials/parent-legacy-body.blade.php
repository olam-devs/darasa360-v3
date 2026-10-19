@php
    $parentYears = $invoiceData['items_by_year'] ?? [];
    if (count($parentYears) === 0) {
        $parentYears = [['year_name' => null, 'items' => $invoiceData['items'] ?? [],
            'subtotal_fees' => $invoiceData['total_fees'], 'subtotal_paid' => $invoiceData['total_paid'],
            'subtotal_balance' => $invoiceData['balance_remaining']]];
    }
@endphp
@foreach($parentYears as $yearData)
<div class="year-block">
    @if($yearData['year_name'])
    <div class="year-header">Academic Year: {{ $yearData['year_name'] }}</div>
    @endif
    <table class="fees-table" style="margin-top: 0;">
        <thead><tr><th style="width: 65%;">Fee Item</th><th style="width: 35%;">Amount Required</th></tr></thead>
        <tbody>
            @forelse($yearData['items'] as $item)
            <tr>
                <td>
                    {{ $item['name'] }}
                    @if(!empty($item['has_scholarship']))
                        <span class="scholarship-badge">{{ $item['scholarship_type'] === 'full' ? 'FULL' : 'PARTIAL' }}</span>
                    @endif
                    @if(!empty($item['deadline']))
                        <br><small style="color: #666; font-size: 8px;">Deadline: {{ date('d/m/Y', strtotime($item['deadline'])) }}</small>
                    @endif
                </td>
                <td class="amount">TSh {{ number_format($item['amount'], 2) }}</td>
            </tr>
            @empty
            <tr><td colspan="2" style="text-align: center; padding: 15px;">No fees assigned yet</td></tr>
            @endforelse
            <tr class="total-row" style="background-color:#bbdefb; color:#0d47a1;"><td>Total Required</td><td class="amount">TSh {{ number_format($yearData['subtotal_fees'], 2) }}</td></tr>
            <tr class="total-row" style="background-color:#c8e6c9; color:#1b5e20;"><td>Total Paid</td><td class="amount">TSh {{ number_format($yearData['subtotal_paid'], 2) }}</td></tr>
            <tr class="total-row" style="background-color:#ffcdd2; color:#b71c1c;"><td>Invoice Amount</td><td class="amount">TSh {{ number_format($yearData['subtotal_balance'], 2) }}</td></tr>
        </tbody>
    </table>
</div>
@endforeach
@if(count($parentYears) > 1)
<table class="fees-table">
    <tbody>
        <tr class="total-row"><td>Grand Total Required (All Years)</td><td class="amount">TSh {{ number_format($invoiceData['total_fees'], 2) }}</td></tr>
        <tr class="total-row"><td>Grand Total Paid (All Years)</td><td class="amount">TSh {{ number_format($invoiceData['total_paid'], 2) }}</td></tr>
        <tr class="total-row"><td>Invoice Amount (All Years)</td><td class="amount">TSh {{ number_format($invoiceData['balance_remaining'], 2) }}</td></tr>
    </tbody>
</table>
@endif
