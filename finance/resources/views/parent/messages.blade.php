@extends('layouts.parent')

@section('title')
<span data-translate="messages-title">Messages</span>
@endsection

@section('content')
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex justify-between items-center mb-6">
        <h3 class="font-bold text-gray-800 flex items-center gap-2 text-xl">
            <i class="fas fa-envelope text-blue-600"></i> 
            <span data-translate="sms-messages">SMS Messages</span>
        </h3>
        <div class="text-sm text-gray-500">
            <span data-translate="total-messages">Total:</span> {{ $messages->total() }}
        </div>
    </div>

    <div class="space-y-4">
        @forelse($messages as $message)
            <div class="border border-gray-200 rounded-xl p-5 hover:border-blue-300 hover:shadow-md transition">
                <div class="flex justify-between items-start mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-sms text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-gray-800" data-translate="school-message">School Message</p>
                            <p class="text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($message->sent_at)->format('M d, Y - h:i A') }}
                            </p>
                        </div>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold 
                        {{ $message->status == 'delivered' ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-700' }}">
                        <i class="fas fa-{{ $message->status == 'delivered' ? 'check-double' : 'clock' }} mr-1"></i>
                        <span data-translate="status-{{ $message->status }}">{{ ucfirst($message->status) }}</span>
                    </span>
                </div>
                
                <div class="bg-gray-50 rounded-lg p-4 border-l-4 border-blue-500">
                    <p class="text-gray-700 leading-relaxed">{{ $message->message }}</p>
                </div>

                @if($message->reference)
                    <div class="mt-3 text-xs text-gray-500">
                        <span data-translate="reference">Reference:</span> {{ $message->reference }}
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center py-12">
                <div class="w-20 h-20 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-inbox text-gray-400 text-3xl"></i>
                </div>
                <p class="text-gray-500 font-medium" data-translate="no-messages">No messages yet</p>
                <p class="text-gray-400 text-sm mt-1" data-translate="no-messages-desc">You'll see SMS messages from the school here</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    @if($messages->hasPages())
        <div class="mt-6">
            {{ $messages->links() }}
        </div>
    @endif
</div>

<script>
    const messagesTranslations = {
        en: {
            'messages-title': 'Messages',
            'sms-messages': 'SMS Messages',
            'total-messages': 'Total',
            'school-message': 'School Message',
            'status-delivered': 'Delivered',
            'status-sent': 'Sent',
            'status-pending': 'Pending',
            'reference': 'Reference',
            'no-messages': 'No messages yet',
            'no-messages-desc': 'You\'ll see SMS messages from the school here'
        },
        sw: {
            'messages-title': 'Ujumbe',
            'sms-messages': 'Ujumbe wa SMS',
            'total-messages': 'Jumla',
            'school-message': 'Ujumbe wa Shule',
            'status-delivered': 'Umefikishwa',
            'status-sent': 'Umetumwa',
            'status-pending': 'Unasubiri',
            'reference': 'Rejea',
            'no-messages': 'Hakuna ujumbe bado',
            'no-messages-desc': 'Utaona ujumbe wa SMS kutoka shule hapa'
        }
    };

    Object.assign(translations.en, messagesTranslations.en);
    Object.assign(translations.sw, messagesTranslations.sw);
</script>
@endsection
