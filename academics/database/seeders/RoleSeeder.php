<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    // Disable foreign key checks temporarily
    DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=0');

    // Truncate the table
    DB::connection('tenant')->table('school_roles')->truncate();

    // Enable foreign key checks again
    DB::connection('tenant')->statement('SET FOREIGN_KEY_CHECKS=1');

    // Seed roles with fresh auto-increment starting at 1
    DB::connection('tenant')->table('school_roles')->insert([
      ['name' => 'Admin'],
      ['name' => 'Owner'],
      ['name' => 'Headmaster'],
      ['name' => 'Accountant'],
      ['name' => 'ClassTeacher'],
      ['name' => 'Teacher'],
      ['name' => 'Student'],
      ['name' => 'Academic'],
    ]);
  }
}
