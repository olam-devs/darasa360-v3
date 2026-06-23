@extends('superadmin.layouts.app')

@section('title', "Students — {$school->name}")

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('superadmin.schools.index') }}" class="hover:underline">Schools</a>
        / <a href="{{ route('superadmin.schools.show', $school) }}" class="hover:underline">{{ $school->name }}</a>
        / <a href="{{ route('superadmin.schools.classes', $school) }}" class="hover:underline">Classes</a>
        / <span class="text-gray-800 font-medium">Students</span>
    </nav>

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Central Student Registry</h1>
            <p class="text-sm text-gray-500 mt-1">
                School code: <span class="font-mono font-bold text-blue-700">{{ $platformSchool->code }}</span>
                &nbsp;|&nbsp; Total: <strong>{{ $students->total() }}</strong> students
            </p>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('superadmin.schools.students.sync-all', $school) }}">
                @csrf
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 text-sm">
                    Re-Sync All to Tenants
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-300 text-green-800 rounded-lg p-4 mb-6">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-red-50 border border-red-300 text-red-800 rounded-lg p-4 mb-6">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-4 gap-6">

        {{-- LEFT: Add + Import --}}
        <div class="space-y-6">

            {{-- Add Single Student --}}
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="text-base font-semibold mb-4">Add Student</h2>
                <form method="POST" action="{{ route('superadmin.schools.students.store', $school) }}" class="space-y-3">
                    @csrf
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-medium text-gray-600">First Name *</label>
                            <input type="text" name="first_name" value="{{ old('first_name') }}" required
                                class="w-full px-2 py-1.5 border rounded text-sm focus:ring-1 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Middle Name</label>
                            <input type="text" name="middle_name" value="{{ old('middle_name') }}"
                                class="w-full px-2 py-1.5 border rounded text-sm focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">Last Name *</label>
                        <input type="text" name="last_name" value="{{ old('last_name') }}" required
                            class="w-full px-2 py-1.5 border rounded text-sm focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="text-xs font-medium text-gray-600">Gender *</label>
                            <select name="gender" required class="w-full px-2 py-1.5 border rounded text-sm focus:ring-1 focus:ring-blue-500">
                                <option value="">—</option>
                                <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-medium text-gray-600">Date of Birth</label>
                            <input type="date" name="date_of_birth" value="{{ old('date_of_birth') }}"
                                class="w-full px-2 py-1.5 border rounded text-sm focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">Class</label>
                        <select name="class_id" class="w-full px-2 py-1.5 border rounded text-sm focus:ring-1 focus:ring-blue-500">
                            <option value="">— None —</option>
                            @foreach($classes as $cls)
                                <option value="{{ $cls->id }}" {{ old('class_id') == $cls->id ? 'selected' : '' }}>{{ $cls->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">Parent Name</label>
                        <input type="text" name="parent_name" value="{{ old('parent_name') }}"
                            class="w-full px-2 py-1.5 border rounded text-sm focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">Parent Phone</label>
                        <input type="text" name="parent_phone" value="{{ old('parent_phone') }}"
                            class="w-full px-2 py-1.5 border rounded text-sm focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600">Parent Email</label>
                        <input type="email" name="parent_email" value="{{ old('parent_email') }}"
                            class="w-full px-2 py-1.5 border rounded text-sm focus:ring-1 focus:ring-blue-500">
                    </div>
                    <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 font-medium text-sm">
                        Add Student
                    </button>
                </form>
            </div>

            {{-- CSV Import --}}
            <div class="bg-white rounded-xl shadow p-5">
                <h2 class="text-base font-semibold mb-2">Bulk Import (CSV)</h2>
                <p class="text-xs text-gray-500 mb-3">
                    Required columns: <code class="bg-gray-100 px-1">first_name, last_name, gender</code><br>
                    Optional: <code class="bg-gray-100 px-1">middle_name, dob, class_name, parent_name, parent_phone, parent_email</code>
                </p>
                <form method="POST" action="{{ route('superadmin.schools.students.import', $school) }}" enctype="multipart/form-data" class="space-y-3">
                    @csrf
                    <div>
                        <label class="text-xs font-medium text-gray-600">Default Class (optional)</label>
                        <select name="class_id" class="w-full px-2 py-1.5 border rounded text-sm">
                            <option value="">— Use class_name column —</option>
                            @foreach($classes as $cls)
                                <option value="{{ $cls->id }}">{{ $cls->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="file" name="csv_file" accept=".csv,.txt" required
                            class="w-full text-sm text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>
                    <button type="submit" class="w-full bg-green-600 text-white py-2 rounded-lg hover:bg-green-700 font-medium text-sm">
                        Import Students
                    </button>
                </form>
            </div>
        </div>

        {{-- RIGHT: Student Table --}}
        <div class="xl:col-span-3 bg-white rounded-xl shadow overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold">Students</h2>
            </div>

            @if($students->isEmpty())
                <div class="p-12 text-center text-gray-400">
                    No students yet. Add individually or import via CSV.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-gray-600 text-xs uppercase sticky top-0">
                            <tr>
                                <th class="px-4 py-3 text-left">Reg No</th>
                                <th class="px-4 py-3 text-left">Name</th>
                                <th class="px-4 py-3 text-left">Gender</th>
                                <th class="px-4 py-3 text-left">Class</th>
                                <th class="px-4 py-3 text-left">Parent</th>
                                <th class="px-4 py-3 text-center">Finance</th>
                                <th class="px-4 py-3 text-center">Academics</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($students as $student)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-mono text-xs text-blue-700 font-bold">{{ $student->student_reg_no }}</td>
                                <td class="px-4 py-3 font-medium">{{ $student->fullName() }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $student->gender }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $student->platformClass?->name ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="text-gray-700">{{ $student->parent_name ?: '—' }}</div>
                                    @if($student->parent_phone)
                                        <div class="text-xs text-gray-400">{{ $student->parent_phone }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($student->synced_finance)
                                        <span class="text-green-600 font-bold">✓</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($student->synced_academics)
                                        <span class="text-green-600 font-bold">✓</span>
                                    @else
                                        <span class="text-gray-300">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <form method="POST" action="{{ route('superadmin.schools.students.destroy', [$school, $student]) }}"
                                        onsubmit="return confirm('Remove {{ $student->fullName() }} from central registry?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-red-500 hover:text-red-700 text-xs">Remove</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-4 border-t">
                    {{ $students->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
