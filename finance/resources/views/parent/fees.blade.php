@extends('layouts.parent')

@section('title')
<span data-translate="fees-title">Fee Statements</span>
@endsection

@section('content')
<div class="space-y-8">
    <!-- Fee Breakdown -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800" data-translate="fee-structure">Fee Structure Breakdown</h3>
            <span class="text-xs font-medium bg-white px-3 py-1 rounded-full border border-gray-200 text-gray-500" data-translate="academic-year">Academic Year 2024</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4" data-translate="fee-item">Fee Item</th>
                        <th class="px-6 py-4 text-right" data-translate="total-amount">Total Amount</th>
                        <th class="px-6 py-4 text-right" data-translate="paid">Paid</th>
                        <th class="px-6 py-4 text-right" data-translate="balance">Balance</th>
                        <th class="px-6 py-4 text-center" data-translate="status">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($feeBreakdown as $item)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 font-medium text-gray-800">{{ $item->name }}</td>
                            <td class="px-6 py-4 text-right text-gray-600">{{ number_format($item->amount) }}</td>
                            <td class="px-6 py-4 text-right text-green-600 font-medium">{{ number_format($item->paid) }}</td>
                            <td class="px-6 py-4 text-right font-bold {{ $item->balance > 0 ? 'text-red-500' : 'text-gray-400' }}">
                                {{ number_format($item->balance) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($item->balance <= 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800" data-translate="status-paid">
                                        Paid
                                    </span>
                                @elseif($item->paid > 0)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800" data-translate="status-partial">
                                        Partial
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800" data-translate="status-unpaid">
                                        Unpaid
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Transaction History -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b border-gray-100 bg-gray-50 flex justify-between items-center">
            <h3 class="font-bold text-gray-800" data-translate="transaction-history">Transaction History</h3>
            <button onclick="window.print()" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                <i class="fas fa-print mr-1"></i> <span data-translate="print">Print</span>
            </button>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-semibold">
                    <tr>
                        <th class="px-6 py-4" data-translate="date">Date</th>
                        <th class="px-6 py-4" data-translate="ref-no">Ref No.</th>
                        <th class="px-6 py-4" data-translate="description">Description</th>
                        <th class="px-6 py-4" data-translate="book">Book</th>
                        <th class="px-6 py-4 text-right" data-translate="debit-invoiced">Debit (Invoiced)</th>
                        <th class="px-6 py-4 text-right" data-translate="credit-paid">Credit (Paid)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($transactions as $txn)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-sm text-gray-600">
                                {{ \Carbon\Carbon::parse($txn->date)->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-gray-500">
                                {{ $txn->ref_no ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-800">
                                {{ $txn->particular->name ?? 'General Transaction' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ $txn->book->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-gray-600">
                                @if($txn->debit > 0)
                                    {{ number_format($txn->debit) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm text-green-600 font-medium">
                                @if($txn->credit > 0)
                                    {{ number_format($txn->credit) }}
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-6 border-t border-gray-100">
            {{ $transactions->links() }}
        </div>
    </div>
</div>

<script>
    const feesTranslations = {
        en: {
            'fees-title': 'Fee Statements',
            'fee-structure': 'Fee Structure Breakdown',
            'academic-year': 'Academic Year 2024',
            'fee-item': 'Fee Item',
            'total-amount': 'Total Amount',
            'paid': 'Paid',
            'balance': 'Balance',
            'status': 'Status',
            'status-paid': 'Paid',
            'status-partial': 'Partial',
            'status-unpaid': 'Unpaid',
            'transaction-history': 'Transaction History',
            'print': 'Print',
            'date': 'Date',
            'ref-no': 'Ref No.',
            'description': 'Description',
            'book': 'Book',
            'debit-invoiced': 'Debit (Invoiced)',
            'credit-paid': 'Credit (Paid)'
        },
        sw: {
            'fees-title': 'Taarifa za Ada',
            'fee-structure': 'Maelezo ya Muundo wa Ada',
            'academic-year': 'Mwaka wa Masomo 2024',
            'fee-item': 'Kipengele cha Ada',
            'total-amount': 'Jumla ya Kiasi',
            'paid': 'Imelipwa',
            'balance': 'Salio',
            'status': 'Hali',
            'status-paid': 'Imelipwa',
            'status-partial': 'Nusu',
            'status-unpaid': 'Haijalipiwa',
            'transaction-history': 'Historia ya Miamala',
            'print': 'Chapisha',
            'date': 'Tarehe',
            'ref-no': 'Nambari ya Rejea',
            'description': 'Maelezo',
            'book': 'Kitabu',
            'debit-invoiced': 'Deni (Imeandikwa)',
            'credit-paid': 'Mkopo (Imelipwa)'
        }
    };

    Object.assign(translations.en, feesTranslations.en);
    Object.assign(translations.sw, feesTranslations.sw);
</script>
@endsection
