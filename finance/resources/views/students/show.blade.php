<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $student->name }}
            </h2>
            <a href="{{ route('students.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg border border-gray-300">
                ← Back to Students
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Student Information -->
                <div class="lg:col-span-1">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-center mb-4">
                                <div class="h-24 w-24 rounded-full bg-purple-100 flex items-center justify-center">
                                    <span class="text-purple-600 font-bold text-3xl">{{ substr($student->name, 0, 2) }}</span>
                                </div>
                            </div>
                            <div class="text-center mb-6">
                                <h3 class="text-xl font-bold text-gray-900">{{ $student->name }}</h3>
                                <p class="text-sm text-gray-600">{{ $student->student_reg_no }}</p>
                                <span class="inline-block mt-2 px-3 py-1 rounded-full text-xs font-semibold
                                    @if($student->status === 'active') bg-green-100 text-green-800
                                    @elseif($student->status === 'inactive') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($student->status) }}
                                </span>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Class</p>
                                    <p class="font-semibold text-gray-900">{{ $student->class }}</p>
                                </div>

                                @if($student->email)
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Email</p>
                                        <p class="font-semibold text-gray-900">{{ $student->email }}</p>
                                    </div>
                                @endif

                                @if($student->phone)
                                    <div>
                                        <p class="text-xs text-gray-500 uppercase">Phone</p>
                                        <p class="font-semibold text-gray-900">{{ $student->phone }}</p>
                                    </div>
                                @endif

                                <div>
                                    <p class="text-xs text-gray-500 uppercase">Admission Date</p>
                                    <p class="font-semibold text-gray-900">{{ $student->admission_date ? $student->admission_date->format('d M, Y') : 'N/A' }}</p>
                                </div>

                                @if($student->parent)
                                    <div class="border-t pt-4">
                                        <p class="text-xs text-gray-500 uppercase mb-2">Parent/Guardian</p>
                                        <div class="bg-gray-50 rounded-lg p-3">
                                            <p class="font-semibold text-gray-900">{{ $student->parent->name }}</p>
                                            <p class="text-sm text-gray-600">{{ $student->parent->email }}</p>
                                            @if($student->parent->phone)
                                                <p class="text-sm text-gray-600">{{ $student->parent->phone }}</p>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            @if(auth()->user()->role !== 'parent')
                                <div class="mt-6 pt-6 border-t">
                                    <a href="{{ route('students.edit', $student) }}" class="block w-full text-center bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium">
                                        Edit Student
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Financial Information -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Financial Summary -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Summary</h3>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div class="bg-blue-50 rounded-lg p-4">
                                    <p class="text-sm text-blue-600 font-medium">Total Invoiced</p>
                                    <p class="text-2xl font-bold text-blue-900">KES {{ number_format($student->invoices->sum('total_amount'), 2) }}</p>
                                </div>
                                <div class="bg-green-50 rounded-lg p-4">
                                    <p class="text-sm text-green-600 font-medium">Total Paid</p>
                                    <p class="text-2xl font-bold text-green-900">KES {{ number_format($student->payments->sum('amount'), 2) }}</p>
                                </div>
                                <div class="bg-red-50 rounded-lg p-4">
                                    <p class="text-sm text-red-600 font-medium">Outstanding Balance</p>
                                    <p class="text-2xl font-bold text-red-900">KES {{ number_format($student->invoices->sum('balance'), 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Invoices -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg font-semibold text-gray-900">Recent Invoices</h3>
                                @if(auth()->user()->role !== 'parent')
                                    <a href="{{ route('invoices.create', ['student_id' => $student->id]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Create Invoice
                                    </a>
                                @endif
                            </div>
                            @if($student->invoices->count() > 0)
                                <div class="space-y-3">
                                    @foreach($student->invoices->take(5) as $invoice)
                                        <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                                            <div>
                                                <a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-blue-600 hover:text-blue-800">
                                                    {{ $invoice->invoice_number }}
                                                </a>
                                                <p class="text-sm text-gray-600">{{ $invoice->invoice_date->format('d M, Y') }}</p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-bold text-gray-900">KES {{ number_format($invoice->total_amount, 2) }}</p>
                                                <span class="text-xs px-2 py-1 rounded-full
                                                    @if($invoice->status === 'paid') bg-green-100 text-green-800
                                                    @elseif($invoice->status === 'overdue') bg-red-100 text-red-800
                                                    @elseif($invoice->status === 'partial') bg-yellow-100 text-yellow-800
                                                    @else bg-blue-100 text-blue-800
                                                    @endif">
                                                    {{ ucfirst($invoice->status) }}
                                                </span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-8">
                                    <div class="text-gray-400 text-lg">📄</div>
                                    <p class="text-gray-500 mt-2">No invoices yet</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Recent Payments -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Payments</h3>
                            @if($student->payments->count() > 0)
                                <div class="space-y-3">
                                    @foreach($student->payments->take(5) as $payment)
                                        <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg hover:bg-gray-100">
                                            <div>
                                                <a href="{{ route('payments.show', $payment) }}" class="font-medium text-blue-600 hover:text-blue-800">
                                                    {{ $payment->payment_number }}
                                                </a>
                                                <p class="text-sm text-gray-600">
                                                    {{ $payment->payment_date->format('d M, Y') }} •
                                                    @if($payment->payment_method === 'cash') 💵
                                                    @elseif($payment->payment_method === 'bank_transfer') 🏦
                                                    @elseif($payment->payment_method === 'mobile_money') 📱
                                                    @elseif($payment->payment_method === 'cheque') 📝
                                                    @else 💳
                                                    @endif
                                                    {{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}
                                                </p>
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
                            @else
                                <div class="text-center py-8">
                                    <div class="text-gray-400 text-lg">💰</div>
                                    <p class="text-gray-500 mt-2">No payments yet</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
