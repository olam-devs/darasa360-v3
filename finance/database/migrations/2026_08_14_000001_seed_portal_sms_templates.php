<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'tenant';

    public function up(): void
    {
        $templates = [
            [
                'name' => 'Parent Portal Login — English',
                'message_en' => "Dear parent of {student_name},\n\nYour child's parent portal login credentials:\nEmail: {portal_email}\nPassword: {portal_password}\nLogin: {portal_login_link}\n\nPlease change the password after first login.",
                'message_sw' => "Mpendwa mzazi wa {student_name},\n\nBarua pepe: {portal_email}\nNenosiri: {portal_password}\nIngia: {portal_login_link}\n\nTafadhali badilisha nenosiri baada ya kuingia mara ya kwanza.",
                'type' => 'portal_credentials',
                'is_active' => 1,
                'created_by' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($templates as $tpl) {
            $exists = DB::connection('tenant')
                ->table('sms_templates')
                ->where('type', $tpl['type'])
                ->where('name', $tpl['name'])
                ->exists();

            if (! $exists) {
                DB::connection('tenant')->table('sms_templates')->insert($tpl);
            }
        }
    }

    public function down(): void
    {
        DB::connection('tenant')
            ->table('sms_templates')
            ->where('type', 'portal_credentials')
            ->delete();
    }
};
