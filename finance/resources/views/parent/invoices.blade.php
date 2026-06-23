@extends('layouts.parent')

@section('title')
<span data-translate="invoices-title">My Invoices</span>
@endsection

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Invoice Details -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="p-8 border-b border-gray-100 text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-file-invoice text-blue-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800" data-translate="invoice-title">Current Outstanding Invoice</h3>
                <p class="text-gray-500 text-sm mt-1">
                    <span data-translate="generated-on">Generated on</span> {{ now()->format('d M Y') }}
                </p>
                
                <div class="mt-8 flex justify-center gap-4">
                    <a href="{{ route('parent.invoices.download') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg shadow-blue-200">
                        <i class="fas fa-download"></i> <span data-translate="download-invoice">Download PDF Invoice</span>
                    </a>
                </div>
            </div>

            <div class="p-8">
                <h4 class="font-bold text-gray-800 mb-6" data-translate="payment-breakdown">Payment Breakdown by Academic Year</h4>

                @if(isset($itemsByYear) && count($itemsByYear) > 0)
                    @foreach($itemsByYear as $yearData)
                        <div class="mb-6">
                            <!-- Academic Year Header -->
                            <div class="flex justify-between items-center bg-blue-600 text-white px-4 py-3 rounded-t-xl">
                                <span class="font-bold"><i class="fas fa-calendar-alt mr-2"></i>{{ $yearData['year_name'] }}</span>
                                <span class="text-sm">
                                    @if($yearData['subtotal_balance'] > 0)
                                        <span class="bg-red-500 px-2 py-1 rounded text-xs font-bold">Balance: TSh {{ number_format($yearData['subtotal_balance']) }}</span>
                                    @else
                                        <span class="bg-green-500 px-2 py-1 rounded text-xs font-bold">Paid in Full</span>
                                    @endif
                                </span>
                            </div>

                            <!-- Year Items -->
                            <div class="border border-gray-200 rounded-b-xl overflow-hidden">
                                @foreach($yearData['items'] as $item)
                                    <div class="flex justify-between items-center p-4 {{ $item['balance'] > 0 ? 'bg-orange-50' : 'bg-gray-50' }} {{ !$loop->last ? 'border-b border-gray-200' : '' }}">
                                        <div>
                                            <h5 class="font-bold text-gray-800">{{ $item['name'] }}</h5>
                                            <div class="flex gap-2 text-xs mt-1">
                                                <span class="text-gray-500"><span data-translate="invoiced">Invoiced</span>: TSh {{ number_format($item['amount']) }}</span>
                                                <span class="text-gray-300">|</span>
                                                <span class="text-green-600"><span data-translate="paid">Paid</span>: TSh {{ number_format($item['paid']) }}</span>
                                            </div>
                                            @if($item['is_overdue'])
                                                <div class="mt-2 text-red-600 text-xs font-bold flex items-center gap-1">
                                                    <i class="fas fa-exclamation-triangle"></i> <span data-translate="overdue">Overdue</span> (<span data-translate="deadline">Deadline</span>: {{ $item['deadline'] }})
                                                </div>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <p class="text-xs text-gray-500 uppercase font-bold mb-1" data-translate="balance-due">Balance Due</p>
                                            <p class="text-lg font-bold {{ $item['balance'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                                TSh {{ number_format($item['balance']) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach

                                <!-- Year Subtotal -->
                                <div class="bg-blue-50 p-4 flex justify-between items-center border-t border-blue-200">
                                    <span class="font-bold text-blue-700">Subtotal ({{ $yearData['year_name'] }})</span>
                                    <span class="font-bold text-blue-700">TSh {{ number_format($yearData['subtotal_balance']) }}</span>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Fallback for old data without academic year -->
                    <div class="space-y-4">
                        @foreach($items as $item)
                            <div class="flex justify-between items-center p-4 rounded-xl {{ $item['balance'] > 0 ? 'bg-orange-50 border border-orange-100' : 'bg-gray-50 border border-gray-100' }}">
                                <div>
                                    <h5 class="font-bold text-gray-800">{{ $item['name'] }}</h5>
                                    <div class="flex gap-2 text-xs mt-1">
                                        <span class="text-gray-500"><span data-translate="invoiced">Invoiced</span>: TSh {{ number_format($item['amount']) }}</span>
                                        <span class="text-gray-300">|</span>
                                        <span class="text-green-600"><span data-translate="paid">Paid</span>: TSh {{ number_format($item['paid']) }}</span>
                                    </div>
                                    @if($item['is_overdue'])
                                        <div class="mt-2 text-red-600 text-xs font-bold flex items-center gap-1">
                                            <i class="fas fa-exclamation-triangle"></i> <span data-translate="overdue">Overdue</span> (<span data-translate="deadline">Deadline</span>: {{ $item['deadline'] }})
                                        </div>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <p class="text-xs text-gray-500 uppercase font-bold mb-1" data-translate="balance-due">Balance Due</p>
                                    <p class="text-lg font-bold {{ $item['balance'] > 0 ? 'text-red-600' : 'text-gray-400' }}">
                                        TSh {{ number_format($item['balance']) }}
                                    </p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Summary Card -->
    <div class="lg:col-span-1">
        <div class="bg-gradient-to-br from-gray-900 to-gray-800 rounded-2xl shadow-xl text-white p-8 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-64 h-64 bg-white/5 rounded-full blur-3xl -mr-20 -mt-20"></div>
            
            <h3 class="font-bold text-lg mb-6 relative z-10" data-translate="payment-summary">Payment Summary</h3>
            
            <div class="space-y-4 relative z-10">
                <div class="flex justify-between items-center pb-4 border-b border-white/10">
                    <span class="text-gray-400" data-translate="total-invoiced">Total Invoiced</span>
                    <span class="font-bold">TSh {{ number_format($totalFees) }}</span>
                </div>
                
                <div class="flex justify-between items-center pb-4 border-b border-white/10">
                    <span class="text-gray-400" data-translate="total-paid">Total Paid</span>
                    <span class="font-bold text-green-400">TSh {{ number_format($totalPaid) }}</span>
                </div>
                
                <div class="pt-2">
                    <span class="block text-gray-400 text-sm mb-1 uppercase tracking-wider" data-translate="total-amount-due">Total Amount Due</span>
                    <span class="block text-4xl font-bold text-white">
                        TSh {{ number_format($totalFees - $totalPaid) }}
                    </span>
                </div>
            </div>

            <div class="mt-8 pt-6 border-t border-white/10 relative z-10">
                <p class="text-xs text-gray-400 mb-4">
                    <span data-translate="payment-instructions">Please make payments using the account numbers below. Reference student</span> {{ $student->name }} ({{ $student->student_reg_no }}).
                </p>
                @if($school->phone || $school->email)
                    <div class="bg-white/10 rounded-lg p-4 mb-4">
                        @if($school->phone)
                            <p class="font-mono text-sm text-center tracking-wider text-yellow-400 font-bold">
                                <i class="fas fa-phone mr-2"></i>{{ $school->phone }}
                            </p>
                        @endif
                        @if($school->email)
                            <p class="font-mono text-sm text-center tracking-wider text-green-400 font-bold mt-2">
                                <i class="fas fa-envelope mr-2"></i>{{ $school->email }}
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    const invoicesTranslations = {
        en: {
            'invoices-title': 'My Invoices',
            'invoice-title': 'Current Outstanding Invoice',
            'generated-on': 'Generated on',
            'download-invoice': 'Download PDF Invoice',
            'payment-breakdown': 'Payment Breakdown',
            'invoiced': 'Invoiced',
            'paid': 'Paid',
            'overdue': 'Overdue',
            'deadline': 'Deadline',
            'balance-due': 'Balance Due',
            'payment-summary': 'Payment Summary',
            'total-invoiced': 'Total Invoiced',
            'total-paid': 'Total Paid',
            'total-amount-due': 'Total Amount Due',
            'payment-instructions': 'Please make payments using the account numbers below. Reference student'
        },
        sw: {
            'invoices-title': 'Ankara Zangu',
            'invoice-title': 'Ankara ya Sasa Inayobaki',
            'generated-on': 'Imetengenezwa tarehe',
            'download-invoice': 'Pakua Ankara ya PDF',
            'payment-breakdown': 'Maelezo ya Malipo',
            'invoiced': 'Imeandikwa',
            'paid': 'Imelipwa',
            'overdue': 'Imechelewa',
            'deadline': 'Tarehe ya Mwisho',
            'balance-due': 'Salio Linalobaki',
            'payment-summary': 'Muhtasari wa Malipo',
            'total-invoiced': 'Jumla Iliyoandikwa',
            'total-paid': 'Jumla Iliyolipwa',
            'total-amount-due': 'Jumla ya Kiasi Kinachobaki',
            'payment-instructions': 'Tafadhali fanya malipo kwa kutumia nambari za akaunti zilizo hapa chini. Rejea mwanafunzi'
        }
    };

    Object.assign(translations.en, invoicesTranslations.en);
    Object.assign(translations.sw, invoicesTranslations.sw);
</script>
@endsection
