@extends('layouts.parent')

@section('title')
<span data-translate="notifications-title">Notifications</span>
@endsection

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="font-bold text-gray-800 flex items-center gap-2 text-xl">
            <i class="fas fa-bell text-blue-600"></i> 
            <span data-translate="notifications-header">Notifications & Reminders</span>
        </h3>
        <div class="text-sm text-gray-500">
            <span data-translate="total-notifications">Total:</span> {{ count($notifications) }}
        </div>
    </div>

    <div class="space-y-4">
        @forelse($notifications as $notification)
            <div class="border-l-4 rounded-lg p-5 transition hover:shadow-md
                {{ $notification['color'] == 'red' ? 'border-red-500 bg-red-50' : 
                   ($notification['color'] == 'green' ? 'border-green-500 bg-green-50' : 'border-blue-500 bg-blue-50') }}">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0
                        {{ $notification['color'] == 'red' ? 'bg-red-100' : 
                           ($notification['color'] == 'green' ? 'bg-green-100' : 'bg-blue-100') }}">
                        <i class="fas fa-{{ $notification['icon'] }} text-xl
                            {{ $notification['color'] == 'red' ? 'text-red-600' : 
                               ($notification['color'] == 'green' ? 'text-green-600' : 'text-blue-600') }}"></i>
                    </div>
                    
                    <div class="flex-1">
                        <div class="flex justify-between items-start mb-2">
                            <h4 class="font-bold text-gray-800">
                                <span data-translate="notif-{{ strtolower(str_replace(' ', '-', $notification['type'])) }}">
                                    {{ $notification['title'] }}
                                </span>
                            </h4>
                            <span class="text-xs text-gray-500 whitespace-nowrap ml-4">
                                {{ \Carbon\Carbon::parse($notification['date'])->diffForHumans() }}
                            </span>
                        </div>
                        <p class="text-gray-700 leading-relaxed">{{ $notification['message'] }}</p>
                        <p class="text-xs text-gray-500 mt-2">
                            {{ \Carbon\Carbon::parse($notification['date'])->format('M d, Y - h:i A') }}
                        </p>
                    </div>
                </div>
            </div>
        @empty
            <div class="text-center py-12">
                <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bell-slash text-gray-400 text-3xl"></i>
                </div>
                <p class="text-gray-500 font-medium" data-translate="no-notifications">No notifications</p>
                <p class="text-gray-400 text-sm mt-1" data-translate="no-notifications-desc">You're all caught up! Notifications will appear here.</p>
            </div>
        @endforelse
    </div>

    <!-- Payment Tracking Summary -->
    @if(count($notifications) > 0)
        <div class="mt-8 pt-6 border-t border-gray-200">
            <h4 class="font-bold text-gray-800 mb-4" data-translate="payment-summary">Payment Summary</h4>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-xl p-4 border border-green-200">
                    <p class="text-sm text-green-700 font-medium mb-1" data-translate="payments-made">Payments Made</p>
                    <p class="text-2xl font-bold text-green-800">
                        {{ collect($notifications)->where('type', 'payment')->count() }}
                    </p>
                </div>
                <div class="bg-gradient-to-br from-red-50 to-rose-50 rounded-xl p-4 border border-red-200">
                    <p class="text-sm text-red-700 font-medium mb-1" data-translate="overdue-items">Overdue Items</p>
                    <p class="text-2xl font-bold text-red-800">
                        {{ collect($notifications)->where('type', 'overdue')->count() }}
                    </p>
                </div>
                <div class="bg-gradient-to-br from-blue-50 to-blue-50 rounded-xl p-4 border border-blue-200">
                    <p class="text-sm text-blue-700 font-medium mb-1" data-translate="total-notifications-count">Total Notifications</p>
                    <p class="text-2xl font-bold text-blue-800">{{ count($notifications) }}</p>
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    const notificationsTranslations = {
        en: {
            'notifications-title': 'Notifications',
            'notifications-header': 'Notifications & Reminders',
            'total-notifications': 'Total',
            'notif-overdue': 'Overdue Payment',
            'notif-payment': 'Payment Received',
            'no-notifications': 'No notifications',
            'no-notifications-desc': 'You\'re all caught up! Notifications will appear here.',
            'payment-summary': 'Payment Summary',
            'payments-made': 'Payments Made',
            'overdue-items': 'Overdue Items',
            'total-notifications-count': 'Total Notifications'
        },
        sw: {
            'notifications-title': 'Arifa',
            'notifications-header': 'Arifa na Vikumbusho',
            'total-notifications': 'Jumla',
            'notif-overdue': 'Malipo Yaliyochelewa',
            'notif-payment': 'Malipo Yamepokelewa',
            'no-notifications': 'Hakuna arifa',
            'no-notifications-desc': 'Umekamilisha yote! Arifa zitaonekana hapa.',
            'payment-summary': 'Muhtasari wa Malipo',
            'payments-made': 'Malipo Yaliyofanywa',
            'overdue-items': 'Vitu Vilivyochelewa',
            'total-notifications-count': 'Jumla ya Arifa'
        }
    };

    Object.assign(translations.en, notificationsTranslations.en);
    Object.assign(translations.sw, notificationsTranslations.sw);
</script>
@endsection
