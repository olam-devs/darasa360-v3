@extends('layouts.superadmin')

@section('title', 'Create school — Super Admin')
@section('nav_title', 'Create school')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow p-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-6">Create New School</h1>

            <form method="POST" action="{{ route('superadmin.schools.store') }}" class="space-y-6">
                @csrf

                <!-- School Information -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4">School Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">School Name *</label>
                            <input type="text" name="name" value="{{ old('name') }}" required
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Slug (URL identifier)</label>
                            <input type="text" name="slug" value="{{ old('slug') }}" placeholder="auto-generated"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-sm text-gray-500 mt-1">Leave empty to auto-generate from school name</p>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4">Contact Information</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Contact Email *</label>
                            <input type="email" name="contact_email" value="{{ old('contact_email') }}" required
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Contact Phone</label>
                            <input type="text" name="contact_phone" value="{{ old('contact_phone') }}"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="block text-gray-700 font-medium mb-2">Address</label>
                        <textarea name="address" rows="3" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('address') }}</textarea>
                    </div>

                    <div class="mt-4">
                        <label class="block text-gray-700 font-medium mb-2">Custom Domain (optional)</label>
                        <input type="text" name="domain" value="{{ old('domain') }}" placeholder="school.example.com"
                            class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Database Settings -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4">Database Settings</h2>

                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="use_existing_database" id="use_existing_database" value="1"
                                {{ old('use_existing_database') ? 'checked' : '' }}
                                class="mr-2 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                onchange="toggleDatabaseFields()">
                            <span class="text-gray-700 font-medium">Use Existing Database</span>
                        </label>
                        <p class="text-sm text-gray-500 mt-1">Check this if you have already manually created the database with the required schema.</p>
                    </div>

                    <div id="new_database_info" class="p-4 bg-blue-50 border border-blue-200 rounded-lg mb-4">
                        <p class="text-blue-800 text-sm">
                            <strong>New Database:</strong> A new database will be automatically created with all required tables.
                            Database name will be auto-generated (e.g., darasa_school_002).
                        </p>
                    </div>

                    <div id="existing_database_fields" class="hidden">
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg mb-4">
                            <p class="text-yellow-800 text-sm">
                                <strong>Important:</strong> The existing database must already have the required tables
                                (users, students, books, vouchers, school_settings). You can create these by running
                                migrations on the database first.
                            </p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Existing Database Name *</label>
                            <input type="text" name="existing_database_name" id="existing_database_name"
                                value="{{ old('existing_database_name') }}"
                                placeholder="e.g., darasa_school_abc"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-sm text-gray-500 mt-1">Enter the exact name of the existing database.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Custom DB Host (optional)</label>
                            <input type="text" name="db_host" value="{{ old('db_host') }}" placeholder="Same as main DB if empty"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Custom DB Port (optional)</label>
                            <input type="text" name="db_port" value="{{ old('db_port') }}" placeholder="3306"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Custom DB Username (optional)</label>
                            <input type="text" name="db_username" value="{{ old('db_username') }}" placeholder="Same as main DB if empty"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Custom DB Password (optional)</label>
                            <input type="password" name="db_password" placeholder="Same as main DB if empty"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Subscription Settings -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4">Subscription Settings</h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Subscription Status *</label>
                            <select name="subscription_status" required class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="active" {{ old('subscription_status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="trial" {{ old('subscription_status') == 'trial' ? 'selected' : '' }}>Trial</option>
                                <option value="suspended" {{ old('subscription_status') == 'suspended' ? 'selected' : '' }}>Suspended</option>
                                <option value="cancelled" {{ old('subscription_status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Subscription Expires At</label>
                            <input type="date" name="subscription_expires_at" value="{{ old('subscription_expires_at') }}"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Max Students</label>
                            <input type="number" name="max_students" value="{{ old('max_students', 1000) }}" min="1"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Platform / Systems Configuration -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4">Systems Configuration</h2>
                    <p class="text-sm text-gray-500 mb-4">Select the systems this school will use. At least one must be enabled.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="flex items-center gap-3 p-4 border rounded-lg" id="finance_card">
                            <input type="checkbox" name="has_finance" id="has_finance" value="1"
                                {{ old('has_finance', '1') ? 'checked' : '' }} class="w-5 h-5 text-blue-600">
                            <div>
                                <label for="has_finance" class="font-semibold cursor-pointer">Darasa Finance</label>
                                <div class="text-xs text-gray-500">Fees, invoices, payroll, accountant portal</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 p-4 border rounded-lg">
                            <input type="checkbox" name="has_academics" id="has_academics" value="1"
                                {{ old('has_academics') ? 'checked' : '' }} class="w-5 h-5 text-blue-600">
                            <div>
                                <label for="has_academics" class="font-semibold cursor-pointer">Darasa Academics</label>
                                <div class="text-xs text-gray-500">Classes, exams, attendance, report cards</div>
                            </div>
                        </div>

                        <div id="academics_db_section" class="{{ old('has_academics') ? '' : 'hidden' }} md:col-span-2">
                            <label class="block text-gray-700 font-medium mb-2">Academics Tenant DB Name</label>
                            <input type="text" name="academics_db_name" value="{{ old('academics_db_name') }}"
                                placeholder="e.g. olam_school_001"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-sm text-gray-500 mt-1">Leave empty if the Academics tenant DB has not been provisioned yet — you can set it later.</p>
                        </div>
                    </div>
                </div>

                <!-- Accountant Information (only when Finance is enabled) -->
                <div class="border-b pb-6" id="accountant_section">
                    <h2 class="text-xl font-semibold mb-4">Default Accountant</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Accountant Name *</label>
                            <input type="text" name="accountant_name" value="{{ old('accountant_name', 'School Accountant') }}" required
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Accountant Email *</label>
                            <input type="email" name="accountant_email" value="{{ old('accountant_email') }}" required
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Accountant Password</label>
                            <input type="password" name="accountant_password" placeholder="Leave empty to auto-generate"
                                class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <p class="text-sm text-gray-500 mt-1">Minimum 8 characters. Auto-generated if empty.</p>
                        </div>
                    </div>
                </div>

                <!-- Submit Buttons -->
                <div class="flex justify-end gap-4">
                    <a href="{{ route('superadmin.schools.index') }}" class="bg-gray-300 text-gray-700 px-6 py-3 rounded-lg hover:bg-gray-400">
                        Cancel
                    </a>
                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700">
                        Create School
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleDatabaseFields() {
            const checkbox = document.getElementById('use_existing_database');
            const existingFields = document.getElementById('existing_database_fields');
            const newDbInfo = document.getElementById('new_database_info');
            const existingDbInput = document.getElementById('existing_database_name');

            if (checkbox.checked) {
                existingFields.classList.remove('hidden');
                newDbInfo.classList.add('hidden');
                existingDbInput.required = true;
            } else {
                existingFields.classList.add('hidden');
                newDbInfo.classList.remove('hidden');
                existingDbInput.required = false;
            }
        }

        function toggleFinanceSection() {
            const financeChecked = document.getElementById('has_finance').checked;
            document.getElementById('accountant_section').classList.toggle('hidden', !financeChecked);
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            toggleDatabaseFields();
            toggleFinanceSection();

            document.getElementById('has_finance').addEventListener('change', function () {
                toggleFinanceSection();
                // Ensure at least one system is checked
                if (!this.checked && !document.getElementById('has_academics').checked) {
                    document.getElementById('has_academics').checked = true;
                    document.getElementById('academics_db_section').classList.remove('hidden');
                }
            });

            document.getElementById('has_academics').addEventListener('change', function () {
                document.getElementById('academics_db_section').classList.toggle('hidden', !this.checked);
                // Ensure at least one system is checked
                if (!this.checked && !document.getElementById('has_finance').checked) {
                    document.getElementById('has_finance').checked = true;
                    toggleFinanceSection();
                }
            });
        });
    </script>
@endsection
