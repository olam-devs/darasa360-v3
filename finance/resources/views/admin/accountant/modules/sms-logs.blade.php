@extends('layouts.accountant')

@section('title', 'SMS logs — Darasa Finance')
@section('page_title', 'SMS logs')

@section('content')
<div class="w-full p-6">
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-lg font-bold mb-4 text-gray-700"> Filter Logs</h2>
                <form method="GET" action="{{ route('accountant.sms-logs') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                        <select name="status" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                            <option value="">All Statuses</option>
                            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                            <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                        <input type="date" name="from_date" value="{{ request('from_date') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                        <input type="date" name="to_date" value="{{ request('to_date') }}" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                            Apply Filters
                        </button>
                    </div>
                </form>
            </div>

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                @php
                    $totalSms = $logs->count();
                    $sentCount = $logs->where('status', 'sent')->count();
                    $deliveredCount = $logs->where('status', 'delivered')->count();
                    $failedCount = $logs->where('status', 'failed')->count();
                    $totalSmsCount = $logs->sum('sms_count');
                @endphp

                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                    <div class="text-sm text-gray-600">Total Messages</div>
                    <div class="text-2xl font-bold text-blue-600">{{ $totalSms }}</div>
                    <div class="text-xs text-gray-500">{{ $totalSmsCount }} SMS parts</div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                    <div class="text-sm text-gray-600">Delivered</div>
                    <div class="text-2xl font-bold text-green-600">{{ $deliveredCount }}</div>
                    <div class="text-xs text-gray-500">{{ $totalSms > 0 ? round(($deliveredCount/$totalSms)*100) : 0 }}% success rate</div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
                    <div class="text-sm text-gray-600">Sent/Pending</div>
                    <div class="text-2xl font-bold text-yellow-600">{{ $sentCount }}</div>
                </div>

                <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
                    <div class="text-sm text-gray-600">Failed</div>
                    <div class="text-2xl font-bold text-red-600">{{ $failedCount }}</div>
                </div>
            </div>

            <!-- Logs Table -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date/Time</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Message</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">SMS Count</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sent By</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($logs as $log)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <div>{{ $log->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->created_at->format('H:i:s') }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    <div class="font-medium">{{ $log->student->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $log->student->student_reg_no }} | {{ $log->student->class }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $log->recipient_phone }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-700">
                                    <div class="max-w-md truncate" title="{{ $log->message }}">
                                        {{ Str::limit($log->message, 50) }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($log->status === 'delivered')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                             Delivered
                                        </span>
                                        @if($log->delivered_at)
                                            <div class="text-xs text-gray-500 mt-1">{{ $log->delivered_at->format('H:i') }}</div>
                                        @endif
                                    @elseif($log->status === 'sent')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                             Sent
                                        </span>
                                        @if($log->sent_at)
                                            <div class="text-xs text-gray-500 mt-1">{{ $log->sent_at->format('H:i') }}</div>
                                        @endif
                                    @elseif($log->status === 'pending')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            ⏳ Pending
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                             Failed
                                        </span>
                                        @if($log->status_description)
                                            <div class="text-xs text-red-600 mt-1" title="{{ $log->status_description }}">
                                                {{ Str::limit($log->status_description, 30) }}
                                            </div>
                                        @endif
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-center">
                                    <span class="px-2 py-1 bg-gray-100 rounded">{{ $log->sms_count }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $log->sentBy?->name ?? 'System' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    @if($log->status === 'failed')
                                        <button type="button"
                                            onclick="openResendModal({{ $log->id }}, @js($log->recipient_phone))"
                                            class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-50 text-red-700 border border-red-200 hover:bg-red-100">
                                            Resend
                                        </button>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <div class="text-4xl mb-2"></div>
                                    <p>No SMS logs found</p>
                                    <p class="text-sm mt-2">Try adjusting your filters or send some messages first</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Resend modal -->
        <div id="resendModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Resend SMS</h3>
                <label class="block text-sm font-medium text-gray-700 mb-2">Phone number</label>
                <input type="text" id="resendPhone" class="w-full border-gray-300 rounded-lg shadow-sm focus:border-blue-500 focus:ring-blue-500 p-2 border mb-1">
                <p class="text-xs text-gray-500 mb-4">Edit the number to send to a different contact, or leave as-is to retry the same one.</p>
                <p id="resendError" class="text-sm text-red-600 mb-3 hidden"></p>
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeResendModal()" class="px-4 py-2 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</button>
                    <button type="button" id="resendConfirmBtn" onclick="confirmResend()" class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Resend</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let resendLogId = null;

        function openResendModal(logId, phone) {
            resendLogId = logId;
            document.getElementById('resendPhone').value = phone || '';
            document.getElementById('resendError').classList.add('hidden');
            document.getElementById('resendModal').classList.remove('hidden');
        }

        function closeResendModal() {
            resendLogId = null;
            document.getElementById('resendModal').classList.add('hidden');
        }

        async function confirmResend() {
            if (!resendLogId) return;
            const btn = document.getElementById('resendConfirmBtn');
            const errorEl = document.getElementById('resendError');
            errorEl.classList.add('hidden');
            btn.disabled = true;
            btn.textContent = 'Sending...';

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const response = await fetch(`/sms/logs/${resendLogId}/resend`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ phone: document.getElementById('resendPhone').value.trim() }),
                });
                const data = await response.json();

                if (data.success) {
                    closeResendModal();
                    window.location.reload();
                } else {
                    errorEl.textContent = data.message || 'Resend failed';
                    errorEl.classList.remove('hidden');
                }
            } catch (e) {
                errorEl.textContent = 'Resend failed: ' + e.message;
                errorEl.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Resend';
            }
        }
    </script>
@endsection
