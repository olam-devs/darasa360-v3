<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Payment {{ $payment->payment_number }}
            </h2>
            <a href="{{ route('payments.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg border border-gray-300">
                ← Back to Payments
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 mb-2">Payment Receipt</h1>
                            <p class="text-gray-600">{{ $payment->payment_number }}</p>
                        </div>
                        <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold
                            @if($payment->status === 'completed') bg-green-100 text-green-800
                            @elseif($payment->status === 'failed') bg-red-100 text-red-800
                            @elseif($payment->status === 'refunded') bg-gray-100 text-gray-800
                            @else bg-yellow-100 text-yellow-800
                            @endif">
                            {{ ucfirst($payment->status) }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-600 mb-3">STUDENT INFORMATION</h3>
                            <div class="space-y-2">
                                <div>
                                    <p class="text-xs text-gray-500">Student Name</p>
                                    <p class="font-semibold text-gray-900">{{ $payment->student->name }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Registration Number</p>
                                    <p class="font-semibold text-gray-900">{{ $payment->student->student_reg_no }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Class</p>
                                    <p class="font-semibold text-gray-900">{{ $payment->student->class }}</p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h3 class="text-sm font-semibold text-gray-600 mb-3">PAYMENT DETAILS</h3>
                            <div class="space-y-2">
                                <div>
                                    <p class="text-xs text-gray-500">Payment Date</p>
                                    <p class="font-semibold text-gray-900">{{ $payment->payment_date->format('d M, Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Payment Method</p>
                                    <p class="font-semibold text-gray-900">
                                        @if($payment->payment_method === 'cash') 💵 Cash
                                        @elseif($payment->payment_method === 'bank_transfer') 🏦 Bank Transfer
                                        @elseif($payment->payment_method === 'mobile_money') 📱 Mobile Money
                                        @elseif($payment->payment_method === 'cheque') 📝 Cheque
                                        @else 💳 Card
                                        @endif
                                    </p>
                                </div>
                                @if($payment->reference_number)
                                    <div>
                                        <p class="text-xs text-gray-500">Reference Number</p>
                                        <p class="font-semibold text-gray-900">{{ $payment->reference_number }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-6 mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-gray-600">Invoice</span>
                            <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                {{ $payment->invoice->invoice_number }}
                            </a>
                        </div>
                        <div class="flex justify-between items-center py-4 border-t border-gray-200">
                            <span class="text-lg font-bold text-gray-900">Amount Paid</span>
                            <span class="text-2xl font-bold text-green-600">KES {{ number_format($payment->amount, 2) }}</span>
                        </div>
                    </div>

                    @if($payment->notes)
                        <div class="bg-blue-50 rounded-lg p-4 mb-6">
                            <p class="text-sm font-semibold text-gray-700 mb-1">Notes:</p>
                            <p class="text-sm text-gray-600">{{ $payment->notes }}</p>
                        </div>
                    @endif

                    <div class="bg-gray-50 rounded-lg p-4 mb-6">
                        <p class="text-xs text-gray-500">Received by</p>
                        <p class="font-medium text-gray-900">{{ $payment->receiver->name }}</p>
                        <p class="text-xs text-gray-500 mt-1">on {{ $payment->created_at->format('d M, Y H:i') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
