@extends('layouts.superadmin')

@section('title', 'School details — Super Admin')
@section('nav_title', 'School details')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- School Header -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">{{ $school->name }}</h1>
                    @if($tenantSettings && isset($tenantSettings['school_name']) && $tenantSettings['school_name'] !== $school->name)
                        <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded">
                            <p class="text-sm text-yellow-800">
                                <strong>Note:</strong> Tenant database uses name: <span class="font-bold">{{ $tenantSettings['school_name'] }}</span>
                            </p>
                            <div class="flex gap-2 mt-1">
                                <form method="POST" action="{{ route('superadmin.schools.sync-name-to-tenant', $school) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs bg-blue-600 text-white px-2 py-1 rounded hover:bg-blue-700">
                                        Sync to Tenant
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('superadmin.schools.sync-name', $school) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-xs bg-yellow-600 text-white px-2 py-1 rounded hover:bg-yellow-700">
                                        Sync from Tenant
                                    </button>
                                </form>
                            </div>
                        </div>
                    @elseif($tenantSettings && isset($tenantSettings['school_name']))
                        <p class="text-green-600 text-sm mt-1">Tenant Name: {{ $tenantSettings['school_name'] }}</p>
                    @endif
                    <p class="text-gray-600 mt-2">Slug: {{ $school->slug }}</p>
                    <div class="flex gap-2 mt-4">
                        <span class="px-3 py-1 text-sm rounded-full {{ $school->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $school->is_active ? 'Active' : 'Inactive' }}
                        </span>
                        <span class="px-3 py-1 text-sm rounded-full bg-blue-100 text-blue-800">
                            {{ ucfirst($school->subscription_status) }}
                        </span>
                    </div>
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('superadmin.schools.edit', $school) }}" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Edit School
                    </a>
                    <form method="POST" action="{{ route('superadmin.schools.toggle-status', $school) }}" class="inline">
                        @csrf
                        <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded-lg hover:bg-yellow-700">
                            {{ $school->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Total Students</p>
                <p class="text-3xl font-bold text-blue-600">{{ number_format($school->student_count ?? 0) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">SMS Assigned</p>
                <p class="text-3xl font-bold text-gray-800">{{ number_format($school->sms_credits_assigned ?? 0) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">SMS Used</p>
                <p class="text-3xl font-bold text-orange-600">{{ number_format($school->sms_credits_used ?? 0) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">SMS Remaining</p>
                <p class="text-3xl font-bold {{ ($school->sms_credits_remaining ?? 0) > 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ number_format($school->sms_credits_remaining ?? 0) }}
                </p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-500 text-sm">Accountants</p>
                <p class="text-3xl font-bold text-purple-600">{{ $school->accountants->count() }}</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- School Information -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Platform Systems Configuration -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-1">Platform Systems</h2>
                    <p class="text-sm text-gray-500 mb-4">School code: <span class="font-mono font-bold text-blue-700">{{ $school->code ?? 'Not assigned' }}</span></p>

                    @php
                        $flags = [
                            ['key' => 'has_academics',      'label' => 'Darasa Academics',        'desc' => 'Classes, exams, attendance, report cards'],
                            ['key' => 'cross_jump_enabled',  'label' => 'Cross-System Jump (SSO)', 'desc' => 'Allowed users jump between Finance & Academics without re-login'],
                            ['key' => 'parent_cross_access', 'label' => 'Parent Cross-Access',     'desc' => 'Parents can jump to Finance fee portal from Academics'],
                        ];
                    @endphp

                    <div class="space-y-3">
                        <!-- Finance always on -->
                        <div class="flex items-center justify-between p-3 rounded-lg bg-blue-50 border border-blue-200">
                            <div>
                                <div class="font-semibold text-blue-800">Darasa Finance</div>
                                <div class="text-xs text-gray-500">Fees, invoices, payroll</div>
                            </div>
                            <span class="px-3 py-1 bg-blue-600 text-white rounded-full text-sm font-semibold">Always On</span>
                        </div>

                        @foreach($flags as $flag)
                        <div class="flex items-center justify-between p-3 rounded-lg border {{ $school->{$flag['key']} ? 'bg-green-50 border-green-200' : 'bg-gray-50 border-gray-200' }}">
                            <div>
                                <div class="font-semibold {{ $school->{$flag['key']} ? 'text-green-800' : 'text-gray-700' }}">{{ $flag['label'] }}</div>
                                <div class="text-xs text-gray-500">{{ $flag['desc'] }}</div>
                            </div>
                            <form method="POST" action="{{ route('superadmin.schools.toggle-platform-flag', $school) }}" class="inline">
                                @csrf
                                <input type="hidden" name="flag" value="{{ $flag['key'] }}">
                                <button type="submit" class="px-3 py-1 rounded-full text-sm font-semibold {{ $school->{$flag['key']} ? 'bg-green-600 text-white hover:bg-green-700' : 'bg-gray-300 text-gray-700 hover:bg-gray-400' }}">
                                    {{ $school->{$flag['key']} ? 'Enabled' : 'Disabled' }}
                                </button>
                            </form>
                        </div>
                        @endforeach

                        @if($school->has_academics)
                        <div class="mt-2 p-3 bg-gray-50 border rounded-lg text-sm">
                            <span class="font-medium text-gray-600">Academics DB:</span>
                            <span class="font-mono text-gray-800">{{ $school->academics_db_name ?: 'Not set' }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Student & Class Registry Quick Links -->
                @if($school->platform_school_id)
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Central Registry</h2>
                    <div class="grid grid-cols-3 gap-4">
                        <a href="{{ route('superadmin.schools.classes', $school) }}"
                           class="flex items-center gap-3 p-4 border rounded-lg hover:bg-blue-50 hover:border-blue-300 transition">
                            <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-800">Manage Classes</div>
                                <div class="text-xs text-gray-500">Create &amp; sync class definitions</div>
                            </div>
                        </a>
                        <a href="{{ route('superadmin.schools.students', $school) }}"
                           class="flex items-center gap-3 p-4 border rounded-lg hover:bg-green-50 hover:border-green-300 transition">
                            <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-800">Manage Students</div>
                                <div class="text-xs text-gray-500">Add, import &amp; sync students</div>
                            </div>
                        </a>
                        <a href="{{ route('superadmin.schools.grants', $school) }}"
                           class="flex items-center gap-3 p-4 border rounded-lg hover:bg-purple-50 hover:border-purple-300 transition">
                            <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-800">Cross-Access Grants</div>
                                <div class="text-xs text-gray-500">Grant SSO jump to headmasters &amp; owners</div>
                            </div>
                        </a>
                    </div>
                </div>
                @endif

                <!-- SMS Credits Management -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">SMS Credits Management</h2>
                    <form method="POST" action="{{ route('superadmin.schools.sms-credits', $school) }}">
                        @csrf
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Action</label>
                                <select name="action" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="add">Add Credits</option>
                                    <option value="set">Set Total Credits</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Amount</label>
                                <input type="number" name="sms_credits" min="0" required
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                                    Update Credits
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- SMS Reallocation between Finance & Academics -->
                @if($school->platform_school_id)
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-1">Reallocate SMS Between Systems</h2>
                    <p class="text-sm text-gray-500 mb-4">Move unused credits between Finance and Academics for this school. Only available credits (assigned − used) can be moved.</p>

                    @php
                        $platSchool = \App\Models\Platform\PlatformSchool::find($school->platform_school_id);
                        $acRow = null;
                        if ($platSchool?->academics_db_name) {
                            try {
                                config(['database.connections.ac_tmp' => array_merge(config('database.connections.mysql'), ['database' => $platSchool->academics_db_name])]);
                                \Illuminate\Support\Facades\DB::purge('ac_tmp');
                                $acRow = \Illuminate\Support\Facades\DB::connection('ac_tmp')->table('sms_balances')->where('school_id', $school->id)->first();
                            } catch (\Exception $e) {}
                        }
                        $finAvail = ($school->sms_credits_assigned ?? 0) - ($school->sms_credits_used ?? 0);
                        $acAvail  = $acRow ? ($acRow->sms_allocated - $acRow->sms_used) : 0;
                    @endphp

                    <div class="grid grid-cols-2 gap-4 mb-4 text-center">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Finance Available</p>
                            <p class="text-2xl font-bold text-blue-700">{{ number_format($finAvail) }}</p>
                            <p class="text-xs text-gray-400">of {{ number_format($school->sms_credits_assigned ?? 0) }} assigned</p>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Academics Available</p>
                            <p class="text-2xl font-bold text-green-700">{{ number_format($acAvail) }}</p>
                            <p class="text-xs text-gray-400">of {{ number_format($acRow?->sms_allocated ?? 0) }} allocated</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('superadmin.schools.reallot-sms', $school) }}">
                        @csrf
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Direction</label>
                                <select name="direction" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                                    <option value="to_academics">Finance → Academics</option>
                                    <option value="to_finance">Academics → Finance</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Credits to Move</label>
                                <input type="number" name="amount" min="1" required
                                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500"
                                    placeholder="e.g. 500">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700">
                                    Reallocate
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                @endif

                <!-- Basic Info -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">School Information</h2>
                    <dl class="grid grid-cols-2 gap-4">
                        <div>
                            <dt class="text-gray-500 text-sm">Database Name</dt>
                            <dd class="font-medium">{{ $school->database_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-sm">URL</dt>
                            <dd class="font-medium">{{ $school->getUrl() }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-sm">Contact Email</dt>
                            <dd class="font-medium">{{ $school->contact_email }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-sm">Contact Phone</dt>
                            <dd class="font-medium">{{ $school->contact_phone ?? 'N/A' }}</dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-gray-500 text-sm">Address</dt>
                            <dd class="font-medium">{{ $school->address ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-sm">Max Students</dt>
                            <dd class="font-medium">{{ number_format($school->max_students) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 text-sm">Subscription Expires</dt>
                            <dd class="font-medium">{{ $school->subscription_expires_at ? $school->subscription_expires_at->format('M d, Y') : 'Never' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Impersonation -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Impersonation</h2>
                    <p class="text-gray-600 mb-4">Access this school's dashboard using your master password</p>
                    <form method="POST" action="{{ route('superadmin.impersonate', $school) }}">
                        @csrf
                        <div class="flex gap-4">
                            <input type="password" name="master_password" placeholder="Master Password" required
                                class="flex-1 px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <button type="submit" class="bg-purple-600 text-white px-6 py-2 rounded-lg hover:bg-purple-700">
                                Impersonate
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Recent Activities -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold mb-4">Recent Activities (School Logs)</h2>
                    <!-- Filter Buttons -->
                    <div class="flex gap-2 mb-4 flex-wrap">
                        <button onclick="filterLogs('all')" class="log-filter active px-3 py-1 rounded text-sm bg-gray-800 text-white" data-filter="all">All</button>
                        <button onclick="filterLogs('accountant')" class="log-filter px-3 py-1 rounded text-sm bg-gray-200 text-gray-700 hover:bg-blue-100" data-filter="accountant">Accountant</button>
                        <button onclick="filterLogs('headmaster')" class="log-filter px-3 py-1 rounded text-sm bg-gray-200 text-gray-700 hover:bg-green-100" data-filter="headmaster">Headmaster</button>
                        <button onclick="filterLogs('parent')" class="log-filter px-3 py-1 rounded text-sm bg-gray-200 text-gray-700 hover:bg-orange-100" data-filter="parent">Parent</button>
                        <button onclick="filterLogs('super_admin')" class="log-filter px-3 py-1 rounded text-sm bg-gray-200 text-gray-700 hover:bg-purple-100" data-filter="super_admin">SuperAdmin</button>
                    </div>
                    <div class="space-y-3 max-h-96 overflow-y-auto" id="activity-logs">
                        @forelse($recentActivities as $activity)
                            @php
                                $borderColor = match($activity->user_type) {
                                    'accountant' => 'border-blue-500',
                                    'headmaster' => 'border-green-500',
                                    'parent' => 'border-orange-500',
                                    'super_admin' => 'border-purple-500',
                                    default => 'border-gray-400',
                                };
                                $badgeColor = match($activity->user_type) {
                                    'accountant' => 'bg-blue-100 text-blue-800',
                                    'headmaster' => 'bg-green-100 text-green-800',
                                    'parent' => 'bg-orange-100 text-orange-800',
                                    'super_admin' => 'bg-purple-100 text-purple-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                                $userTypeLabel = match($activity->user_type) {
                                    'accountant' => 'Accountant',
                                    'headmaster' => 'Headmaster',
                                    'parent' => 'Parent',
                                    'super_admin' => 'SuperAdmin',
                                    default => ucfirst($activity->user_type),
                                };
                            @endphp
                            <div class="p-3 bg-gray-50 rounded border-l-4 {{ $borderColor }} log-entry" data-type="{{ $activity->user_type }}">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-sm font-medium">{{ $activity->action }}</p>
                                        <p class="text-xs text-gray-600">{{ $activity->description }}</p>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full {{ $badgeColor }}">
                                        {{ $userTypeLabel }}
                                    </span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <p class="text-xs text-gray-500">
                                        @if($activity->user_name)
                                            By: <span class="font-medium">{{ $activity->user_name }}</span>
                                        @else
                                            @php
                                                $user = $activity->user();
                                            @endphp
                                            By: <span class="font-medium">{{ $user ? $user->name : 'Unknown' }}</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400">{{ $activity->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500">No activities yet</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Accountants -->
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold">Accountants</h2>
                        <button onclick="showAddAccountantModal()" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">
                            + Add
                        </button>
                    </div>
                    <div class="space-y-3">
                        @forelse($school->accountants as $accountant)
                            <div class="p-3 bg-gray-50 rounded">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="font-medium">{{ $accountant->name }}</p>
                                        <p class="text-sm text-gray-600">{{ $accountant->email }}</p>
                                    </div>
                                    @if($accountant->is_primary)
                                        <span class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded-full">Primary</span>
                                    @endif
                                </div>
                                <div class="flex gap-2 items-center mt-2 flex-wrap">
                                    <span class="text-xs px-2 py-1 rounded-full {{ $accountant->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $accountant->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                    <button onclick="showPasswordReset({{$accountant->id}}, '{{$accountant->email}}')"
                                        class="text-xs bg-blue-500 hover:bg-blue-600 text-white px-2 py-1 rounded">
                                        Reset Password
                                    </button>
                                    <form method="POST" action="{{ route('superadmin.schools.accountants.toggle', [$school, $accountant]) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-xs {{ $accountant->is_active ? 'bg-yellow-500 hover:bg-yellow-600' : 'bg-green-500 hover:bg-green-600' }} text-white px-2 py-1 rounded">
                                            {{ $accountant->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                    @if($school->accountants->count() > 1)
                                        <form method="POST" action="{{ route('superadmin.schools.accountants.destroy', [$school, $accountant]) }}" class="inline"
                                            onsubmit="return confirm('Are you sure you want to delete this accountant?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500">No accountants assigned</p>
                        @endforelse
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="bg-white rounded-lg shadow p-6 border-2 border-red-200">
                    <h2 class="text-xl font-bold mb-4 text-red-600">Danger Zone</h2>
                    <p class="text-sm text-gray-600 mb-4">Deleting a school will permanently remove its database and all data. This action cannot be undone.</p>
                    <form method="POST" action="{{ route('superadmin.schools.destroy', $school) }}" onsubmit="return confirm('Are you absolutely sure? This will delete the school and all its data permanently!');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700">
                            Delete School
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Accountant Modal -->
    <div id="addAccountantModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold mb-4">Add New Accountant</h3>
            <form method="POST" action="{{ route('superadmin.schools.accountants.store', $school) }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                    <input type="text" name="name" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <input type="email" name="email" required
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input type="password" name="password" required minlength="8"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4 space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-3">
                    <p class="text-sm font-medium text-slate-800">Permissions</p>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="can_edit_history" value="0">
                        <input type="checkbox" name="can_edit_history" value="1" class="rounded">
                        <span>Edit history (reconciliation)</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="hidden" name="can_view_logs" value="0">
                        <input type="checkbox" name="can_view_logs" value="1" class="rounded">
                        <span>View activity logs</span>
                    </label>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Add Accountant
                    </button>
                    <button type="button" onclick="hideAddAccountantModal()"
                        class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Password Reset Modal -->
    <div id="passwordResetModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
            <h3 class="text-xl font-bold mb-4">Reset Accountant Password</h3>
            <form method="POST" action="{{ route('superadmin.schools.reset-password', $school) }}">
                @csrf
                <input type="hidden" name="accountant_id" id="accountant_id">
                <p class="text-sm text-gray-600 mb-4">Resetting password for: <strong id="accountant_email"></strong></p>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">New Password</label>
                    <input type="password" name="new_password" required minlength="8"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <input type="password" name="new_password_confirmation" required minlength="8"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        Reset Password
                    </button>
                    <button type="button" onclick="hidePasswordReset()"
                        class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
function showAddAccountantModal() {
        document.getElementById('addAccountantModal').classList.remove('hidden');
    }

    function hideAddAccountantModal() {
        document.getElementById('addAccountantModal').classList.add('hidden');
    }

    function showPasswordReset(accountantId, email) {
        document.getElementById('accountant_id').value = accountantId;
        document.getElementById('accountant_email').textContent = email;
        document.getElementById('passwordResetModal').classList.remove('hidden');
    }

    function hidePasswordReset() {
        document.getElementById('passwordResetModal').classList.add('hidden');
    }

    function filterLogs(type) {
        const entries = document.querySelectorAll('.log-entry');
        const buttons = document.querySelectorAll('.log-filter');

        // Update active button
        buttons.forEach(btn => {
            btn.classList.remove('bg-gray-800', 'text-white', 'active');
            btn.classList.add('bg-gray-200', 'text-gray-700');
        });
        const activeBtn = document.querySelector(`.log-filter[data-filter="${type}"]`);
        if (activeBtn) {
            activeBtn.classList.remove('bg-gray-200', 'text-gray-700');
            activeBtn.classList.add('bg-gray-800', 'text-white', 'active');
        }

        // Filter entries
        entries.forEach(entry => {
            if (type === 'all' || entry.dataset.type === type) {
                entry.style.display = 'block';
            } else {
                entry.style.display = 'none';
            }
        });
    }
    </script>
@endpush
