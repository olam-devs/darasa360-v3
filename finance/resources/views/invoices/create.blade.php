<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Create New Invoice') }}
            </h2>
            <a href="{{ route('invoices.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg border border-gray-300">
                ← Back to Invoices
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8">
                    <form action="{{ route('invoices.store') }}" method="POST" id="invoiceForm">
                        @csrf

                        <!-- Student Selection -->
                        <div class="mb-6">
                            <label for="student_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                Select Student <span class="text-red-500">*</span>
                            </label>
                            <select name="student_id" id="student_id" required
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="">-- Choose Student --</option>
                                @foreach($students as $student)
                                    <option value="{{ $student->id }}">
                                        {{ $student->name }} ({{ $student->student_reg_no }}) - {{ $student->class }}
                                    </option>
                                @endforeach
                            </select>
                            @error('student_id')
                                <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <!-- Invoice Date -->
                            <div>
                                <label for="invoice_date" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Invoice Date
                                </label>
                                <input type="date" name="invoice_date" id="invoice_date" value="{{ date('Y-m-d') }}"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>

                            <!-- Due Date -->
                            <div>
                                <label for="due_date" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Due Date <span class="text-red-500">*</span>
                                </label>
                                <input type="date" name="due_date" id="due_date" required
                                    value="{{ date('Y-m-d', strtotime('+30 days')) }}"
                                    class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                @error('due_date')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Invoice Items -->
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-4">
                                <label class="block text-sm font-semibold text-gray-700">
                                    Invoice Items <span class="text-red-500">*</span>
                                </label>
                                <button type="button" onclick="addInvoiceItem()"
                                    class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                    + Add Item
                                </button>
                            </div>

                            <div class="bg-gray-50 rounded-lg p-4">
                                <div id="invoice-items">
                                    <!-- Items will be added here -->
                                </div>
                            </div>
                        </div>

                        <!-- Totals -->
                        <div class="bg-blue-50 rounded-lg p-6 mb-6">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Subtotal</label>
                                    <div class="text-2xl font-bold text-gray-800">
                                        KES <span id="subtotal">0.00</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Tax (0%)</label>
                                    <input type="hidden" name="tax" value="0">
                                    <div class="text-2xl font-bold text-gray-800">
                                        KES <span id="tax">0.00</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Total Amount</label>
                                    <div class="text-3xl font-bold text-blue-600">
                                        KES <span id="total">0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-6">
                            <label for="notes" class="block text-sm font-semibold text-gray-700 mb-2">
                                Additional Notes
                            </label>
                            <textarea name="notes" id="notes" rows="3"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                placeholder="Enter any additional notes or instructions..."></textarea>
                        </div>

                        <!-- Status -->
                        <div class="mb-6">
                            <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">
                                Invoice Status
                            </label>
                            <select name="status" id="status"
                                class="w-full border border-gray-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <option value="draft">Draft (Save for later)</option>
                                <option value="sent" selected>Send to Parent</option>
                            </select>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-4">
                            <a href="{{ route('invoices.index') }}"
                                class="px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-medium border border-gray-300">
                                Cancel
                            </a>
                            <button type="submit"
                                class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium shadow-lg">
                                Create Invoice
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let itemIndex = 0;
        const feeItems = @json($feeItems);

        function addInvoiceItem() {
            const container = document.getElementById('invoice-items');
            const itemHtml = `
                <div class="invoice-item bg-white rounded-lg p-4 mb-3 border border-gray-200" data-index="${itemIndex}">
                    <div class="grid grid-cols-12 gap-4 items-start">
                        <div class="col-span-5">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Fee Item</label>
                            <select name="items[${itemIndex}][fee_item_id]" required onchange="updateItemPrice(${itemIndex})"
                                class="fee-item-select w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="">Select fee item</option>
                                ${feeItems.map(item => `<option value="${item.id}" data-price="${item.amount}">${item.item_name} - KES ${parseFloat(item.amount).toLocaleString()}</option>`).join('')}
                            </select>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Quantity</label>
                            <input type="number" name="items[${itemIndex}][quantity]" value="1" min="1" required
                                onchange="updateItemTotal(${itemIndex})"
                                class="item-quantity w-full border border-gray-300 rounded px-3 py-2 text-sm">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Unit Price</label>
                            <input type="number" name="items[${itemIndex}][unit_price]" step="0.01" required readonly
                                class="item-unit-price w-full border border-gray-300 rounded px-3 py-2 text-sm bg-gray-50">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Total</label>
                            <input type="text" readonly
                                class="item-total w-full border border-gray-300 rounded px-3 py-2 text-sm bg-gray-50 font-semibold">
                        </div>
                        <div class="col-span-1 flex items-end">
                            <button type="button" onclick="removeItem(${itemIndex})"
                                class="text-red-500 hover:text-red-700 p-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', itemHtml);
            itemIndex++;
            calculateTotals();
        }

        function updateItemPrice(index) {
            const item = document.querySelector(`[data-index="${index}"]`);
            const select = item.querySelector('.fee-item-select');
            const selectedOption = select.options[select.selectedIndex];
            const price = selectedOption.dataset.price || 0;

            item.querySelector('.item-unit-price').value = price;
            updateItemTotal(index);
        }

        function updateItemTotal(index) {
            const item = document.querySelector(`[data-index="${index}"]`);
            const quantity = parseFloat(item.querySelector('.item-quantity').value) || 0;
            const unitPrice = parseFloat(item.querySelector('.item-unit-price').value) || 0;
            const total = quantity * unitPrice;

            item.querySelector('.item-total').value = total.toFixed(2);
            calculateTotals();
        }

        function removeItem(index) {
            const item = document.querySelector(`[data-index="${index}"]`);
            item.remove();
            calculateTotals();
        }

        function calculateTotals() {
            let subtotal = 0;
            document.querySelectorAll('.item-total').forEach(input => {
                subtotal += parseFloat(input.value) || 0;
            });

            const tax = 0;
            const total = subtotal + tax;

            document.getElementById('subtotal').textContent = subtotal.toFixed(2);
            document.getElementById('tax').textContent = tax.toFixed(2);
            document.getElementById('total').textContent = total.toFixed(2);
        }

        // Add first item on load
        document.addEventListener('DOMContentLoaded', function() {
            addInvoiceItem();
        });
    </script>
</x-app-layout>
