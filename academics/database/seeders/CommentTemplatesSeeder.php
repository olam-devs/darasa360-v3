<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CommentTemplatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $templates = [
            // Accountant Templates
            [
                'stakeholder_type' => 'accountant',
                'name' => 'Fees Paid in Full',
                'content' => 'All school fees for this term have been paid in full. The student\'s account is in good standing.',
                'is_default' => true,
            ],
            [
                'stakeholder_type' => 'accountant',
                'name' => 'Partial Payment',
                'content' => 'Some fees are still outstanding. Please contact the accounts office to clear the balance.',
                'is_default' => false,
            ],
            [
                'stakeholder_type' => 'accountant',
                'name' => 'Outstanding Balance',
                'content' => 'There is an outstanding balance on the student\'s account. Please settle the fees to avoid any interruption in services.',
                'is_default' => false,
            ],

            // Discipline Templates
            [
                'stakeholder_type' => 'discipline',
                'name' => 'Excellent Behavior',
                'content' => 'Excellent discipline record. The student demonstrates respect, responsibility, and follows all school rules consistently.',
                'is_default' => true,
            ],
            [
                'stakeholder_type' => 'discipline',
                'name' => 'Good Behavior',
                'content' => 'Good discipline record. The student generally follows school rules and demonstrates positive behavior.',
                'is_default' => false,
            ],
            [
                'stakeholder_type' => 'discipline',
                'name' => 'Needs Improvement',
                'content' => 'The student needs to improve their discipline. Please encourage better behavior and adherence to school rules.',
                'is_default' => false,
            ],

            // Class Teacher Templates
            [
                'stakeholder_type' => 'class_teacher',
                'name' => 'Excellent Performance',
                'content' => 'Excellent overall performance this term. The student shows great dedication, participates actively in class, and maintains good relationships with peers. Keep up the excellent work!',
                'is_default' => true,
            ],
            [
                'stakeholder_type' => 'class_teacher',
                'name' => 'Good Progress',
                'content' => 'Good progress this term. The student is working well and showing improvement. Continue to encourage consistent effort.',
                'is_default' => false,
            ],
            [
                'stakeholder_type' => 'class_teacher',
                'name' => 'Needs More Effort',
                'content' => 'The student needs to put in more effort. Please encourage regular study, completion of assignments, and active class participation.',
                'is_default' => false,
            ],

            // Subject Teacher Templates
            [
                'stakeholder_type' => 'subject_teacher',
                'name' => 'Strong Performance',
                'content' => 'Strong performance in this subject. The student demonstrates good understanding and consistent effort.',
                'is_default' => true,
            ],
            [
                'stakeholder_type' => 'subject_teacher',
                'name' => 'Satisfactory',
                'content' => 'Satisfactory performance. The student is making steady progress and should continue with regular practice.',
                'is_default' => false,
            ],
            [
                'stakeholder_type' => 'subject_teacher',
                'name' => 'Needs Improvement',
                'content' => 'The student needs to improve in this subject. Extra practice and focused study are recommended.',
                'is_default' => false,
            ],

            // Academic Templates
            [
                'stakeholder_type' => 'academic',
                'name' => 'Outstanding Achievement',
                'content' => 'Outstanding academic achievement this term. The student demonstrates excellent understanding across all subjects and maintains high standards. Congratulations!',
                'is_default' => true,
            ],
            [
                'stakeholder_type' => 'academic',
                'name' => 'Good Academic Standing',
                'content' => 'Good academic performance. The student is progressing well and should continue to work consistently.',
                'is_default' => false,
            ],
            [
                'stakeholder_type' => 'academic',
                'name' => 'Academic Support Needed',
                'content' => 'The student requires additional academic support. Please arrange for extra tuition or study sessions to improve performance.',
                'is_default' => false,
            ],
        ];

        foreach ($templates as $template) {
            $template['created_at'] = now();
            $template['updated_at'] = now();
            DB::connection('tenant')->table('comment_templates')->insert($template);
        }
    }
}
