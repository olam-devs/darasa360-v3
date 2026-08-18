<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!DB::connection('tenant')->getSchemaBuilder()->hasTable('expense_items')) {
            return;
        }

        $items = [
            ['name' => 'Mchele',          'unit_type' => 'kg'],
            ['name' => 'Tambi',           'unit_type' => 'kg'],
            ['name' => 'Nyama',           'unit_type' => 'kg'],
            ['name' => 'Maharage',        'unit_type' => 'kg'],
            ['name' => 'Makande',         'unit_type' => 'kg'],
            ['name' => 'Dona',            'unit_type' => 'kg'],
            ['name' => 'Sukari',          'unit_type' => 'kg'],
            ['name' => 'Mafuta',          'unit_type' => 'litre'],
            ['name' => 'Nyanya',          'unit_type' => null],
            ['name' => 'Viungo',          'unit_type' => null],
            ['name' => 'Mkaa',            'unit_type' => null],
            ['name' => 'Maji',            'unit_type' => null],
            ['name' => 'Ngano',           'unit_type' => 'kg'],
            ['name' => 'Sembe',           'unit_type' => 'kg'],
            ['name' => 'Matunda',         'unit_type' => null],
            ['name' => 'Sabuni ya unga',  'unit_type' => null],
            ['name' => 'Mikate',          'unit_type' => null],
            ['name' => 'Nauli sokoni',    'unit_type' => null],
            ['name' => 'Maziwa',          'unit_type' => 'box'],
            ['name' => 'Hiace',           'unit_type' => null],
            ['name' => 'Noah',            'unit_type' => null],
            ['name' => 'Blue band',       'unit_type' => null],
            ['name' => 'Mboga za majani', 'unit_type' => null],
            ['name' => 'Disel',           'unit_type' => null],
            ['name' => 'Dagaa',           'unit_type' => null],
            ['name' => 'Rosella',         'unit_type' => null],
            ['name' => 'Ubuyu',           'unit_type' => null],
            ['name' => 'Ndizi',           'unit_type' => null],
            ['name' => 'Tikiti',          'unit_type' => null],
            ['name' => 'Boga',            'unit_type' => null],
            ['name' => 'Carrot',          'unit_type' => null],
            ['name' => 'Hoho',            'unit_type' => null],
            ['name' => 'V/maji',          'unit_type' => null],
            ['name' => 'V/swaum',         'unit_type' => null],
            ['name' => 'V/pilau',         'unit_type' => null],
            ['name' => 'Vitafunwa',       'unit_type' => null],
            ['name' => 'Sabuni',          'unit_type' => null],
            ['name' => 'Mayai',           'unit_type' => null],
            ['name' => 'Mchuzi mix',      'unit_type' => null],
            ['name' => 'Nyanya chungu',   'unit_type' => null],
            ['name' => 'Viungo vya chai', 'unit_type' => null],
            ['name' => 'Nauli',           'unit_type' => null],
            ['name' => 'Kutolea',         'unit_type' => null],
            ['name' => 'School bus',      'unit_type' => null],
        ];

        $now = now();
        foreach ($items as $item) {
            $exists = DB::connection('tenant')
                ->table('expense_items')
                ->whereRaw('LOWER(name) = ?', [strtolower($item['name'])])
                ->exists();
            if (!$exists) {
                DB::connection('tenant')->table('expense_items')->insert([
                    'name'       => $item['name'],
                    'unit_type'  => $item['unit_type'],
                    'created_by' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void {}
};
