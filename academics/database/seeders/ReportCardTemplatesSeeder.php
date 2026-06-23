<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportCardTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Standard Report Card',
                'header_content' => '
<div style="text-align: center; margin-bottom: 30px;">
    <h1 style="margin: 0; font-size: 28px; color: #2c3e50;">{school_name}</h1>
    <p style="margin: 5px 0; color: #7f8c8d;">{school_address}</p>
    <p style="margin: 5px 0; color: #7f8c8d;">Tel: {school_phone} | Email: {school_email}</p>
    <hr style="border: 2px solid #3498db; margin: 20px 0;">
    <h2 style="margin: 10px 0; font-size: 22px; color: #2c3e50;">STUDENT REPORT CARD</h2>
    <p style="margin: 5px 0; font-size: 16px;">{term} - {academic_year}</p>
</div>

<div style="background: #ecf0f1; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
    <table style="width: 100%; border: none;">
        <tr>
            <td style="border: none; padding: 5px;"><strong>Student Name:</strong> {student_name}</td>
            <td style="border: none; padding: 5px;"><strong>Registration No:</strong> {student_reg_no}</td>
        </tr>
        <tr>
            <td style="border: none; padding: 5px;"><strong>Class:</strong> {class_name}</td>
            <td style="border: none; padding: 5px;"><strong>Date:</strong> {date}</td>
        </tr>
    </table>
</div>
                ',
                'footer_content' => '
<div style="margin-top: 40px; padding-top: 20px; border-top: 2px solid #bdc3c7;">
    <table style="width: 100%; border: none;">
        <tr>
            <td style="border: none; padding: 10px; text-align: center;">
                <p>_____________________</p>
                <p><strong>Class Teacher</strong></p>
            </td>
            <td style="border: none; padding: 10px; text-align: center;">
                <p>_____________________</p>
                <p><strong>Head Teacher</strong></p>
            </td>
            <td style="border: none; padding: 10px; text-align: center;">
                <p>_____________________</p>
                <p><strong>Parent/Guardian</strong></p>
            </td>
        </tr>
    </table>
    <p style="text-align: center; margin-top: 30px; font-size: 12px; color: #95a5a6;">
        This is an official report card generated on {date}
    </p>
</div>
                ',
                'layout_settings' => json_encode([
                    'show_position' => true,
                    'show_comments' => true,
                    'show_grading_scale' => true,
                    'show_attendance' => false,
                ]),
                'is_default' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($templates as $template) {
            DB::connection('tenant')->table('report_card_templates')->insert($template);
        }
    }
}
