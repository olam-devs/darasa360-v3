@extends('layouts.parent')

@section('title')
<span data-translate="dashboard-title">Dashboard</span>
@endsection

@section('content')
<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <!-- Total Fees -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
        <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
        <div class="relative z-10">
            <p class="text-sm text-gray-500 font-medium uppercase tracking-wider mb-1" data-translate="total-fees">Total Fees</p>
            <h3 class="text-3xl font-bold text-gray-800">TSh {{ number_format($totalFees) }}</h3>
            <div class="mt-4 flex items-center text-xs text-gray-400">
                <i class="fas fa-file-invoice-dollar mr-1"></i> <span data-translate="academic-year">Academic Year {{ $currentAcademicYear ? $currentAcademicYear->name : date('Y') }}</span>
            </div>
        </div>
    </div>

    <!-- Total Paid -->
    <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 relative overflow-hidden group hover:shadow-md transition">
        <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-bl-full -mr-4 -mt-4 transition-transform group-hover:scale-110"></div>
        <div class="relative z-10">
            <p class="text-sm text-gray-500 font-medium uppercase tracking-wider mb-1" data-translate="total-paid">Total Paid</p>
            <h3 class="text-3xl font-bold text-green-600">TSh {{ number_format($totalPaid) }}</h3>
            <div class="mt-4 flex items-center text-xs text-gray-400">
                <i class="fas fa-check-circle mr-1 text-green-400"></i> <span data-translate="lifetime-payments">Lifetime payments</span>
            </div>
        </div>
    </div>

    <!-- Outstanding Balance -->
    <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-6 shadow-lg text-white relative overflow-hidden group">
        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full blur-2xl -mr-10 -mt-10"></div>
        <div class="relative z-10">
            <p class="text-blue-200 font-medium uppercase tracking-wider mb-1" data-translate="outstanding-balance">Outstanding Balance</p>
            <h3 class="text-4xl font-bold mb-2">TSh {{ number_format($balance) }}</h3>
            
            @if($balance > 0)
                <div class="inline-flex items-center gap-2 bg-white/20 px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-red-400 animate-pulse"></span> <span data-translate="payment-due">Payment Due</span>
                </div>
            @else
                <div class="inline-flex items-center gap-2 bg-white/20 px-3 py-1 rounded-full text-xs font-semibold backdrop-blur-sm">
                    <span class="w-2 h-2 rounded-full bg-green-400"></span> <span data-translate="fully-paid">Fully Paid</span>
                </div>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Quick Actions -->
    <div class="lg:col-span-1 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fas fa-bolt text-yellow-500"></i> <span data-translate="quick-actions">Quick Actions</span>
            </h3>
            <div class="space-y-3">
                <a href="{{ route('parent.invoices') }}" class="block w-full text-center bg-blue-50 hover:bg-blue-100 text-blue-700 font-semibold py-3 px-4 rounded-xl transition">
                    <span data-translate="view-invoice">View Current Invoice</span>
                </a>
                <a href="{{ route('parent.fees') }}" class="block w-full text-center bg-gray-50 hover:bg-gray-100 text-gray-700 font-semibold py-3 px-4 rounded-xl transition">
                    <span data-translate="full-statement">Full Fee Statement</span>
                </a>
                <a href="{{ route('parent.download-statement') }}" class="block w-full text-center bg-green-50 hover:bg-green-100 text-green-700 font-semibold py-3 px-4 rounded-xl transition">
                    <i class="fas fa-download mr-2"></i><span data-translate="download-statement">Download Statement</span>
                </a>
            </div>
        </div>

        <div class="bg-blue-900 rounded-2xl shadow-lg p-6 text-white text-center">
            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center mx-auto mb-4 backdrop-blur-sm">
                <i class="fas fa-headset text-2xl"></i>
            </div>
            <h3 class="font-bold text-lg mb-2" data-translate="need-help">Need Help?</h3>
            <p class="text-blue-200 text-sm mb-4" data-translate="contact-admin">Contact school administration regarding fee discrepancies.</p>
            @if($school->phone)
                <a href="tel:{{ $school->phone }}" class="inline-block bg-white text-blue-900 font-bold py-2 px-6 rounded-full hover:bg-blue-50 transition mb-2">
                    <i class="fas fa-phone mr-2"></i>{{ $school->phone }}
                </a>
            @endif
            @if($school->email)
                <a href="mailto:{{ $school->email }}" class="block text-blue-200 hover:text-white text-sm mt-2">
                    <i class="fas fa-envelope mr-1"></i>{{ $school->email }}
                </a>
            @endif
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h3 class="font-bold text-gray-800 mb-6 flex items-center gap-2">
                <i class="fas fa-history text-gray-400"></i> <span data-translate="recent-transactions">Recent Transactions</span>
            </h3>
            
            <div class="space-y-6 relative before:absolute before:left-4 before:top-2 before:bottom-2 before:w-0.5 before:bg-gray-100">
                @forelse($recentTransactions as $transaction)
                    <div class="relative pl-10">
                        <div class="absolute left-1.5 top-1.5 w-5 h-5 rounded-full border-4 border-white shadow-sm {{ $transaction->credit > 0 ? 'bg-green-500' : 'bg-red-500' }}"></div>
                        <div class="bg-gray-50 rounded-xl p-4 hover:bg-gray-100 transition">
                            <div class="flex justify-between items-start mb-1">
                                <h4 class="font-semibold text-gray-800">
                                    <span data-translate="{{ $transaction->credit > 0 ? 'payment-received' : 'invoice-generated' }}">
                                        {{ $transaction->credit > 0 ? 'Payment Received' : 'Invoice Generated' }}
                                    </span>
                                </h4>
                                <span class="text-xs font-medium text-gray-500">
                                    {{ \Carbon\Carbon::parse($transaction->date)->format('M d, Y') }}
                                </span>
                            </div>
                            <div class="flex justify-between items-end">
                                <p class="text-sm text-gray-600">
                                    {{ $transaction->particular->name ?? 'General' }}
                                    @if($transaction->ref_no)
                                        <span class="text-xs text-gray-400 block">Ref: {{ $transaction->ref_no }}</span>
                                    @endif
                                </p>
                                <span class="font-bold {{ $transaction->credit > 0 ? 'text-green-600' : 'text-gray-800' }}">
                                    @if($transaction->credit > 0)
                                        + TSh {{ number_format($transaction->credit) }}
                                    @else
                                        TSh {{ number_format($transaction->debit) }}
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-gray-400">
                        <span data-translate="no-transactions">No recent transactions found.</span>
                    </div>
                @endforelse
            </div>
            
            @if(count($recentTransactions) > 0)
                <div class="mt-6 text-center">
                    <a href="{{ route('parent.fees') }}" class="text-blue-600 font-medium hover:text-blue-800 text-sm">
                        <span data-translate="view-all-history">View All History</span> &rarr;
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    // Add dashboard-specific translations
    const dashboardTranslations = {
        en: {
            'dashboard-title': 'Dashboard',
            'total-fees': 'Total Fees',
            'total-paid': 'Total Paid',
            'outstanding-balance': 'Outstanding Balance',
            'payment-due': 'Payment Due',
            'fully-paid': 'Fully Paid',
            'academic-year': 'Academic Year 2024',
            'lifetime-payments': 'Lifetime payments',
            'quick-actions': 'Quick Actions',
            'view-invoice': 'View Current Invoice',
            'full-statement': 'Full Fee Statement',
            'download-statement': 'Download Statement',
            'need-help': 'Need Help?',
            'contact-admin': 'Contact school administration regarding fee discrepancies.',
            'call-now': 'Call Now',
            'recent-transactions': 'Recent Transactions',
            'payment-received': 'Payment Received',
            'invoice-generated': 'Invoice Generated',
            'no-transactions': 'No recent transactions found.',
            'view-all-history': 'View All History'
        },
        sw: {
            'dashboard-title': 'Dashibodi',
            'total-fees': 'Jumla ya Ada',
            'total-paid': 'Jumla Iliyolipwa',
            'outstanding-balance': 'Salio Linalobaki',
            'payment-due': 'Malipo Yanahitajika',
            'fully-paid': 'Imelipwa Kamili',
            'academic-year': 'Mwaka wa Masomo 2024',
            'lifetime-payments': 'Malipo yote',
            'quick-actions': 'Vitendo vya Haraka',
            'view-invoice': 'Angalia Ankara ya Sasa',
            'full-statement': 'Taarifa Kamili ya Ada',
            'download-statement': 'Pakua Taarifa',
            'need-help': 'Unahitaji Msaada?',
            'contact-admin': 'Wasiliana na utawala wa shule kuhusu tofauti za ada.',
            'call-now': 'Piga Simu Sasa',
            'recent-transactions': 'Miamala ya Hivi Karibuni',
            'payment-received': 'Malipo Yamepokelewa',
            'invoice-generated': 'Ankara Imetengenezwa',
            'no-transactions': 'Hakuna miamala ya hivi karibuni.',
            'view-all-history': 'Angalia Historia Yote'
        }
    };

    // Merge with main translations
    Object.assign(translations.en, dashboardTranslations.en);
    Object.assign(translations.sw, dashboardTranslations.sw);
</script>
@endsection
