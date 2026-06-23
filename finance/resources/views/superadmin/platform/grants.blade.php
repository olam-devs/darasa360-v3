@extends('superadmin.layouts.app')

@section('title', "Cross-Access Grants — {$school->name}")

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('superadmin.schools.index') }}" class="hover:underline">Schools</a>
        / <a href="{{ route('superadmin.schools.show', $school) }}" class="hover:underline">{{ $school->name }}</a>
        / <span class="text-gray-800 font-medium">Cross-Access Grants</span>
    </nav>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Cross-System Access Grants</h1>
            <p class="text-sm text-gray-500 mt-1">
                Grant headmasters and owners the ability to jump between Finance and Academics portals without re-logging in.
                Requires both systems to be enabled and <em>Cross-Jump</em> to be active for this school.
            </p>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-300 text-green-800 rounded-lg p-4 mb-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-300 text-red-800 rounded-lg p-4 mb-6">{{ session('error') }}</div>
    @endif

    {{-- Prerequisites notice --}}
    @if(!$platformSchool->cross_jump_enabled)
    <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 rounded-lg p-4 mb-6">
        <strong>Note:</strong> Cross-Jump is currently <strong>disabled</strong> for this school.
        Grants will be saved but users won't see the jump button until Cross-Jump is enabled in the school's Platform Systems panel.
    </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- Add Grant Form --}}
        <div class="md:col-span-1">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5">
                <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider mb-4">Add Grant</h2>
                <form method="POST" action="{{ route('superadmin.schools.grants.store', $school) }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Registration Number</label>
                        <input type="text" name="user_ref" required
                               placeholder="e.g. S00120001"
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               value="{{ old('user_ref') }}">
                        <p class="text-xs text-gray-400 mt-1">The headmaster's or owner's registration number.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Role</label>
                        <select name="role" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="headmaster" {{ old('role') === 'headmaster' ? 'selected' : '' }}>Headmaster</option>
                            <option value="owner" {{ old('role') === 'owner' ? 'selected' : '' }}>Owner</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Access Level</label>
                        <select name="level" required class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="readonly" selected>Read-only</option>
                            <option value="full">Full access</option>
                        </select>
                    </div>
                    <button type="submit"
                            class="w-full bg-indigo-600 text-white text-sm px-4 py-2 rounded-lg hover:bg-indigo-700 font-medium">
                        Save Grant
                    </button>
                </form>
            </div>
        </div>

        {{-- Grants Table --}}
        <div class="md:col-span-2">
            <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-700 uppercase tracking-wider">Existing Grants</h2>
                    <span class="text-xs text-gray-400">{{ $grants->count() }} {{ Str::plural('grant', $grants->count()) }}</span>
                </div>

                @if($grants->isEmpty())
                    <div class="text-center py-12 text-gray-400">
                        <svg class="mx-auto mb-3 w-10 h-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <p class="text-sm">No grants yet. Add one using the form.</p>
                    </div>
                @else
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-xs text-gray-500 uppercase">
                            <tr>
                                <th class="px-4 py-3 text-left">Reg No.</th>
                                <th class="px-4 py-3 text-left">Role</th>
                                <th class="px-4 py-3 text-left">Level</th>
                                <th class="px-4 py-3 text-center">Status</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($grants as $grant)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono font-medium text-gray-800">{{ $grant->user_ref }}</td>
                                <td class="px-4 py-3 capitalize text-gray-600">{{ $grant->role }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ ucfirst($grant->level) }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if($grant->is_active)
                                        <span class="bg-green-100 text-green-700 text-xs px-2 py-0.5 rounded-full font-medium">Active</span>
                                    @else
                                        <span class="bg-gray-100 text-gray-500 text-xs px-2 py-0.5 rounded-full font-medium">Inactive</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <form method="POST"
                                              action="{{ route('superadmin.schools.grants.toggle', [$school, $grant]) }}">
                                            @csrf
                                            <button type="submit"
                                                    class="text-xs px-3 py-1 rounded border
                                                           {{ $grant->is_active
                                                              ? 'border-yellow-300 text-yellow-700 hover:bg-yellow-50'
                                                              : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                                                {{ $grant->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                        <form method="POST"
                                              action="{{ route('superadmin.schools.grants.destroy', [$school, $grant]) }}"
                                              onsubmit="return confirm('Remove this grant?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="text-xs px-3 py-1 rounded border border-red-200 text-red-600 hover:bg-red-50">
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
        </div>
    </div>

    {{-- Parent Cross-Access notice --}}
    <div class="mt-6 bg-blue-50 border border-blue-200 rounded-xl p-5">
        <h3 class="text-sm font-semibold text-blue-800 mb-1">Parent Access</h3>
        <p class="text-sm text-blue-700">
            Parent portal cross-jump is a school-wide toggle (not per-user).
            It is controlled via the <strong>Parent Cross-Access</strong> flag in the school's Platform Systems panel.
            When enabled, all parents can jump to Academics to view their child's attendance and marks.
        </p>
    </div>

</div>
@endsection
