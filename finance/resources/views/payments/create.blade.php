<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Record Payment') }}
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
                    @if($invoice)
                        <!-- Invoice Information -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Invoice Details</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Invoice Number</p>
                                    <p class="font-semibold text-gray-900">{{ $invoice->invoice_number }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Student</p>
                                    <p class="font-semibold text-gray-900">{{ $invoice->student->name }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Amount</p>
                                    <p class="font-semibold text-gray-900">KES {{ number_format($invoice->total_amount, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Balance Due</p>
                                    <p class="font-bold text-red-600 text-lg">KES {{ number_format($invoice->balance, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <form action="{{ route('payments.store') }}" method="POST">
                        @csrf

                        <!-- Invoice Selection -->
                        <div class="mb-6">
                            <label for="invoice_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                Select Invoice <span class="text-red-500">*</span>
                            </label>
                            @if($invoice)
                                <input type="hidden" name="invoice_id" value="{{ $invoice->id }}">
                                <input type="text" readonly value="{{ $invoice->invoice_number }} - {{ $invoice->student->name }}"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-3 bg-gray-50">
                            @else
                                <select name="invoice_id" id="invoice_id" required onchange="loadInvoiceBalance()"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                    <option value="">-- Select an invoice --</option>
                                    @foreach(\App\Models\Invoice::whereIn('status', ['sent', 'partial', 'overdue'])->where('balance', '>', 0)->with('student')->get() as $inv)
                                        <option value="{{ $inv->id }}" data-balance="{{ $inv->balance }}">
                                            {{ $inv->invoice_number }} - {{ $inv->student->name }} (Balance: KES {{ number_format($inv->balance, 2) }})
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                            @error('invoice_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Payment Date -->
                            <div>
                                <label for="payment_date" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Payment Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="payment_date" id="payment_date" required value="{{ date('Y-m-d') }}"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                @error('payment_date')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Payment Amount -->
                            <div>
                                <label for="amount" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Payment Amount <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">KES</span>
                                    <input type="number" name="amount" id="amount" step="0.01" required
                                        value="{{ $invoice ? $invoice->balance : '' }}"
                                        max="{{ $invoice ? $invoice->balance : '' }}"
                                        class="w-full border border-gray-300 rounded-lg pl-16 pr-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
                                @error('amount')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                                <p class="text-xs text-gray-500 mt-1" id="balance-hint"></p>
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-6">
                            <label for="payment_method" class="block text-sm font-semibold text-gray-700 mb-2">
                                Payment Method <span class="text-red-500">*</span>
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                                <label class="relative">
                                    <input type="radio" name="payment_method" value="cash" required class="peer sr-only">
                                    <div class="border-2 border-gray-300 rounded-lg p-4 text-center cursor-pointer peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-300">
                                        <div class="text-2xl mb-1">💵</div>
                                        <div class="text-sm font-medium">Cash</div>
                                    </div>
                                </label>
                                <label class="relative">
                                    <input type="radio" name="payment_method" value="bank_transfer" required class="peer sr-only">
                                    <div class="border-2 border-gray-300 rounded-lg p-4 text-center cursor-pointer peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-300">
                                        <div class="text-2xl mb-1">🏦</div>
                                        <div class="text-sm font-medium">Bank Transfer</div>
                                    </div>
                                </label>
                                <label class="relative">
                                    <input type="radio" name="payment_method" value="mobile_money" required class="peer sr-only">
                                    <div class="border-2 border-gray-300 rounded-lg p-4 text-center cursor-pointer peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-300">
                                        <div class="text-2xl mb-1">📱</div>
                                        <div class="text-sm font-medium">Mobile Money</div>
                                    </div>
                                </label>
                                <label class="relative">
                                    <input type="radio" name="payment_method" value="cheque" required class="peer sr-only">
                                    <div class="border-2 border-gray-300 rounded-lg p-4 text-center cursor-pointer peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-300">
                                        <div class="text-2xl mb-1">📝</div>
                                        <div class="text-sm font-medium">Cheque</div>
                                    </div>
                                </label>
                                <label class="relative">
                                    <input type="radio" name="payment_method" value="card" required class="peer sr-only">
                                    <div class="border-2 border-gray-300 rounded-lg p-4 text-center cursor-pointer peer-checked:border-green-500 peer-checked:bg-green-50 hover:border-green-300">
                                        <div class="text-2xl mb-1">💳</div>
                                        <div class="text-sm font-medium">Card</div>
                                    </div>
                                </label>
                            </div>
                            @error('payment_method')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Reference Number -->
                        <div class="mb-6">
                            <label for="reference_number" class="block text-sm font-semibold text-gray-700 mb-2">
                                Reference Number
                            </label>
                            <input type="text" name="reference_number" id="reference_number"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                placeholder="e.g., Transaction ID, Cheque Number, Receipt Number">
                            @error('reference_number')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Notes -->
                        <div class="mb-6">
                            <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">
                                Additional Notes
                            </label>
                            <textarea name="notes" id="notes" rows="3"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                placeholder="Enter any additional notes..."></textarea>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('payments.index') }}"
                                class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium border border-gray-300">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-8 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium shadow-lg">
                                💰 Record Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadInvoiceBalance() {
            const select = document.getElementById('invoice_id');
            const selectedOption = select.options[select.selectedIndex];
            const balance = selectedOption.dataset.balance;

            if (balance) {
                document.getElementById('amount').value = balance;
                document.getElementById('amount').max = balance;
                document.getElementById('balance-hint').textContent = `Maximum amount: KES ${parseFloat(balance).toFixed(2)}`;
            }
        }
    </script>
</x-app-layout>
