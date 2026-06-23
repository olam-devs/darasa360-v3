@extends('superadmin.layouts.app')

@section('title', "Classes — {$school->name}")

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('superadmin.schools.index') }}" class="hover:underline">Schools</a>
        / <a href="{{ route('superadmin.schools.show', $school) }}" class="hover:underline">{{ $school->name }}</a>
        / <span class="text-gray-800 font-medium">Classes</span>
    </nav>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Central Class Registry</h1>
            <p class="text-sm text-gray-500 mt-1">Classes defined here are synced to Finance (and Academics when enabled).</p>
        </div>
        <a href="{{ route('superadmin.schools.students', $school) }}"
           class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 text-sm">
            View Students
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-300 text-green-800 rounded-lg p-4 mb-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-300 text-red-800 rounded-lg p-4 mb-6">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Add Class Form --}}
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-4">Add Class</h2>
            <form method="POST" action="{{ route('superadmin.schools.classes.store', $school) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Class Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. Form One"
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                    @error('name')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Level</label>
                    <input type="text" name="level" value="{{ old('level') }}" placeholder="e.g. O-Level"
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stream / Section</label>
                    <input type="text" name="stream" value="{{ old('stream') }}" placeholder="e.g. A"
                        class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-medium">
                    Add &amp; Sync Class
                </button>
            </form>
        </div>

        {{-- Classes Table --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow overflow-hidden">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="text-lg font-semibold">Classes ({{ $classes->count() }})</h2>
            </div>

            @if($classes->isEmpty())
                <div class="p-12 text-center text-gray-400">
                    No classes yet. Add your first class.
                </div>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Name</th>
                            <th class="px-4 py-3 text-left">Level</th>
                            <th class="px-4 py-3 text-left">Stream</th>
                            <th class="px-4 py-3 text-center">Finance</th>
                            <th class="px-4 py-3 text-center">Academics</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($classes as $class)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ $class->name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $class->level ?: '—' }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ $class->stream ?: '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($class->synced_finance)
                                    <span class="text-green-600 font-bold">✓</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($class->synced_academics)
                                    <span class="text-green-600 font-bold">✓</span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST" action="{{ route('superadmin.schools.classes.destroy', [$school, $class]) }}"
                                    onsubmit="return confirm('Delete this class? Students will not be deleted.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 hover:text-red-700 text-xs">Remove</button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>
</div>
@endsection
