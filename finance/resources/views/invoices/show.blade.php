<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Invoice {{ $invoice->invoice_number }}
            </h2>
            <a href="{{ route('invoices.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg border border-gray-300">
                ← Back to Invoices
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <!-- Invoice Header -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-8">
                    <div class="flex justify-between items-start mb-6">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 mb-2">INVOICE</h1>
                            <p class="text-gray-600">{{ $invoice->invoice_number }}</p>
                        </div>
                        <div class="text-right">
                            <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold
                                @if($invoice->status === 'paid') bg-green-100 text-green-800
                                @elseif($invoice->status === 'overdue') bg-red-100 text-red-800
                                @elseif($invoice->status === 'partial') bg-yellow-100 text-yellow-800
                                @else bg-blue-100 text-blue-800
                                @endif">
                                {{ ucfirst($invoice->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-8 mb-8">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-600 mb-2">BILL TO:</h3>
                            <div class="text-gray-900">
                                <p class="font-semibold text-lg">{{ $invoice->student->name }}</p>
                                <p class="text-sm">Reg No: {{ $invoice->student->student_reg_no }}</p>
                                <p class="text-sm">Class: {{ $invoice->student->class }}</p>
                                @if($invoice->student->parent)
                                    <p class="text-sm mt-2 text-gray-600">Parent: {{ $invoice->student->parent->name }}</p>
                                    <p class="text-sm text-gray-600">{{ $invoice->student->parent->email }}</p>
                                @endif
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="mb-4">
                                <p class="text-sm text-gray-600">Invoice Date</p>
                                <p class="font-semibold">{{ $invoice->invoice_date->format('d M, Y') }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Due Date</p>
                                <p class="font-semibold {{ $invoice->due_date < now() && $invoice->balance > 0 ? 'text-red-600' : '' }}">
                                    {{ $invoice->due_date->format('d M, Y') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Invoice Items -->
                    <div class="border border-gray-200 rounded-lg overflow-hidden mb-6">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Unit Price</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($invoice->items as $item)
                                    <tr>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-gray-900">{{ $item->feeItem->item_name }}</div>
                                            @if($item->description)
                                                <div class="text-sm text-gray-500">{{ $item->description }}</div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-center text-sm text-gray-900">{{ $item->quantity }}</td>
                                        <td class="px-6 py-4 text-right text-sm text-gray-900">KES {{ number_format($item->unit_price, 2) }}</td>
                                        <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">KES {{ number_format($item->total_price, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- Totals -->
                    <div class="flex justify-end">
                        <div class="w-64">
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <span class="text-gray-600">Subtotal:</span>
                                <span class="font-semibold">KES {{ number_format($invoice->subtotal, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-200">
                                <span class="text-gray-600">Tax:</span>
                                <span class="font-semibold">KES {{ number_format($invoice->tax, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-3 bg-blue-50 px-4 rounded-lg mt-2">
                                <span class="text-lg font-bold text-gray-900">Total:</span>
                                <span class="text-lg font-bold text-blue-600">KES {{ number_format($invoice->total_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-2 mt-2">
                                <span class="text-gray-600">Paid:</span>
                                <span class="font-semibold text-green-600">KES {{ number_format($invoice->paid_amount, 2) }}</span>
                            </div>
                            <div class="flex justify-between py-3 bg-red-50 px-4 rounded-lg mt-2">
                                <span class="text-lg font-bold text-gray-900">Balance Due:</span>
                                <span class="text-lg font-bold text-red-600">KES {{ number_format($invoice->balance, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    @if($invoice->notes)
                        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                            <p class="text-sm font-semibold text-gray-600 mb-1">Notes:</p>
                            <p class="text-sm text-gray-700">{{ $invoice->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Payments History -->
            @if($invoice->payments->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment History</h3>
                        <div class="space-y-3">
                            @foreach($invoice->payments as $payment)
                                <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="font-medium text-gray-900">{{ $payment->payment_number }}</p>
                                        <p class="text-sm text-gray-600">{{ $payment->payment_date->format('d M, Y') }} • {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-bold text-green-600">KES {{ number_format($payment->amount, 2) }}</p>
                                        <span class="text-xs px-2 py-1 rounded-full
                                            @if($payment->status === 'completed') bg-green-100 text-green-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Actions -->
            @if(auth()->user()->role !== 'parent')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Actions</h3>
                        <div class="flex flex-wrap gap-3">
                            <a href="{{ route('invoices.pdf', $invoice) }}" target="_blank"
                                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium">
                                📄 Download PDF
                            </a>
                            @if($invoice->balance > 0)
                                <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}"
                                    class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg font-medium">
                                    💰 Record Payment
                                </a>
                            @endif
                            @if($invoice->status === 'draft')
                                <form action="{{ route('invoices.send', $invoice) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-6 py-2 rounded-lg font-medium">
                                        📧 Send to Parent
                                    </button>
                                </form>
                            @endif
                            @if(in_array($invoice->status, ['draft', 'sent']) && $invoice->paid_amount == 0)
                                <a href="{{ route('invoices.edit', $invoice) }}"
                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-6 py-2 rounded-lg font-medium">
                                    ✏️ Edit Invoice
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
