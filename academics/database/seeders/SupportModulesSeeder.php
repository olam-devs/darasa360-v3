<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SupportModulesSeeder extends Seeder
{
    /**
     * Run the database seeder for support modules (tenant-specific)
     */
    public function run(): void
    {
        $modules = [
            [
                'name' => 'Student Management',
                'slug' => 'student-management',
                'description' => 'Issues related to student records, enrollment, and profile management',
                'icon' => 'bx bx-user',
                'color' => '#00A8E8',
                'is_active' => true,
                'ticket_count' => 0,
            ],
            [
                'name' => 'Academic Records',
                'slug' => 'academic-records',
                'description' => 'Problems with grades, report cards, transcripts, and academic history',
                'icon' => 'bx bx-book-open',
                'color' => '#667eea',
                'is_active' => true,
                'ticket_count' => 0,
            ],
            [
                'name' => 'Attendance Tracking',
                'slug' => 'attendance-tracking',
                'description' => 'Issues with attendance records, marking, and reports',
                'icon' => 'bx bx-calendar-check',
                'color' => '#f5576c',
                'is_active' => true,
                'ticket_count' => 0,
            ],
            [
                'name' => 'Fees & Payments',
                'slug' => 'fees-payments',
                'description' => 'Payment issues, invoices, receipts, and financial records',
                'icon' => 'bx bx-dollar-circle',
                'color' => '#ffa94d',
                'is_active' => true,
                'ticket_count' => 0,
            ],
            [
                'name' => 'Staff Management',
                'slug' => 'staff-management',
                'description' => 'Teacher accounts, permissions, and staff-related issues',
                'icon' => 'bx bx-group',
                'color' => '#4facfe',
                'is_active' => true,
                'ticket_count' => 0,
            ],
            [
                'name' => 'System & Technical',
                'slug' => 'system-technical',
                'description' => 'Technical problems, login issues, system errors, and bugs',
                'icon' => 'bx bx-error-circle',
                'color' => '#ff6b6b',
                'is_active' => true,
                'ticket_count' => 0,
            ],
            [
                'name' => 'Timetables & Scheduling',
                'slug' => 'timetables-scheduling',
                'description' => 'Problems with class schedules, exam timetables, and calendar',
                'icon' => 'bx bx-time-five',
                'color' => '#20bf6b',
                'is_active' => true,
                'ticket_count' => 0,
            ],
            [
                'name' => 'Communication',
                'slug' => 'communication',
                'description' => 'Issues with SMS, emails, notifications, and announcements',
                'icon' => 'bx bx-message-dots',
                'color' => '#a29bfe',
                'is_active' => true,
                'ticket_count' => 0,
            ],
            [
                'name' => 'Reports & Analytics',
                'slug' => 'reports-analytics',
                'description' => 'Problems generating reports, statistics, and data exports',
                'icon' => 'bx bx-bar-chart-alt-2',
                'color' => '#fd79a8',
                'is_active' => true,
                'ticket_count' => 0,
            ],
            [
                'name' => 'Other',
                'slug' => 'other',
                'description' => 'General inquiries and issues not covered by other categories',
                'icon' => 'bx bx-help-circle',
                'color' => '#95a5a6',
                'is_active' => true,
                'ticket_count' => 0,
            ],
        ];

        DB::connection('tenant')->table('support_modules')->insert($modules);

        $this->command->info('Support modules seeded successfully for tenant database!');
    }
}
