<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SMS Logs - Darasa Finance</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header with Breadcrumb -->
        <nav class="bg-gradient-to-r from-blue-600 to-sky-600 text-white p-4 shadow-lg">
            <div class="container mx-auto">
                <!-- Breadcrumb Navigation -->
                <div class="mb-2 text-sm">
                    <a href="{{ url('/accountant-dashboard') }}" class="hover:text-blue-200 transition">🏠 Home</a>
                    <span class="mx-2">›</span>
                    <a href="{{ route('sms.index') }}" class="hover:text-blue-200 transition">SMS Notification</a>
                    <span class="mx-2">›</span>
                    <span class="text-blue-200">SMS Logs</span>
                </div>

                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-4">
                        <h1 class="text-2xl font-bold">📋 SMS Logs & History</h1>
                    </div>
                    <div class="flex gap-3">
                        <a href="{{ route('sms.index') }}" class="bg-blue-500 hover:bg-blue-600 px-4 py-2 rounded transition">
                            📤 Send SMS
                        </a>
                        <a href="{{ route('sms.manage-phones') }}" class="bg-green-500 hover:bg-green-600 px-4 py-2 rounded transition">
                            📞 Manage Phones
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="container mx-auto p-6">
            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h2 class="text-lg font-bold mb-4 text-gray-700">🔍 Filter Logs</h2>
                <form method="GET" action="{{ route('sms.logs') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
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
                                            ✅ Delivered
                                        </span>
                                        @if($log->delivered_at)
                                            <div class="text-xs text-gray-500 mt-1">{{ $log->delivered_at->format('H:i') }}</div>
                                        @endif
                                    @elseif($log->status === 'sent')
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                            📤 Sent
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
                                            ❌ Failed
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
                                    {{ $log->sentBy->name }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    <div class="text-4xl mb-2">📭</div>
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
    </div>
</body>
</html>
