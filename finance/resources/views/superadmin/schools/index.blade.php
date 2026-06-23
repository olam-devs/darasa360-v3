@extends('layouts.superadmin')

@section('title', 'Schools Management — Darasa Finance')
@section('nav_title', 'Schools')

@section('content')
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold text-gray-800">Schools</h1>
            <a href="{{ route('superadmin.schools.create') }}" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                + Create New School
            </a>
        </div>

        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <form method="GET" class="flex flex-wrap gap-4">
                <input type="text" name="search" placeholder="Search schools..." value="{{ request('search') }}"
                    class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                <select name="status" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all" {{ request('status') == 'all' ? 'selected' : '' }}>All Status</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>

                <select name="subscription_status" class="px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="all" {{ request('subscription_status') == 'all' ? 'selected' : '' }}>All Subscriptions</option>
                    <option value="trial" {{ request('subscription_status') == 'trial' ? 'selected' : '' }}>Trial</option>
                    <option value="active" {{ request('subscription_status') == 'active' ? 'selected' : '' }}>Active</option>
                    <option value="suspended" {{ request('subscription_status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                </select>

                <button type="submit" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700">Filter</button>
                <a href="{{ route('superadmin.schools.index') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400">Clear</a>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">School Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Students</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">SMS Credits</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Accountants</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($schools as $school)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $school->name }}</div>
                                <div class="text-sm text-gray-500">{{ $school->contact_email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-bold text-blue-600">{{ number_format($school->student_count ?? 0) }}</div>
                                <div class="text-xs text-gray-500">students</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm">
                                    <span class="font-bold {{ ($school->sms_credits_remaining ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ number_format($school->sms_credits_remaining ?? 0) }}
                                    </span>
                                    <span class="text-gray-500">/ {{ number_format($school->sms_credits_assigned ?? 0) }}</span>
                                </div>
                                <div class="text-xs text-gray-500">remaining / assigned</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $school->accountants->count() }}</div>
                                <div class="text-xs text-gray-500">
                                    {{ $school->accountants->where('is_active', true)->count() }} active
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $school->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $school->is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="ml-1 px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $school->subscription_status == 'active' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $school->subscription_status == 'trial' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $school->subscription_status == 'suspended' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                    {{ ucfirst($school->subscription_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                <a href="{{ route('superadmin.schools.show', $school) }}" class="text-blue-600 hover:text-blue-900">View</a>
                                <a href="{{ route('superadmin.schools.edit', $school) }}" class="text-blue-600 hover:text-blue-900">Edit</a>
                                <form method="POST" action="{{ route('superadmin.schools.toggle-status', $school) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                        {{ $school->is_active ? 'Deactivate' : 'Activate' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">No schools found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $schools->links() }}
        </div>
@endsection
