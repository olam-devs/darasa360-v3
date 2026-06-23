<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\SubjectPerformance;
use Illuminate\Support\Facades\DB;

class PopulateSubjectPerformance extends Command
{
    protected $signature = 'performance:populate {--exam_id=}';
    protected $description = 'Populate subject performance table from exam marks';

    public function handle()
    {
        $this->info('Populating subject performance data...');

        // Get exam ID if specified, otherwise process all exams
        $examId = $this->option('exam_id');

        if ($examId) {
            $exams = Exam::where('id', $examId)->get();
        } else {
            $exams = Exam::all();
        }

        if ($exams->isEmpty()) {
            $this->error('No exams found!');
            return 1;
        }

        $this->info("Processing {$exams->count()} exam(s)...");

        foreach ($exams as $exam) {
            $this->info("Processing Exam: {$exam->name} (ID: {$exam->id})");

            // Get all subjects
            $subjects = Subject::all();

            foreach ($subjects as $subject) {
                // Get all classes
                $classes = ClassModel::all();

                foreach ($classes as $class) {
                    $this->processSubjectPerformance($exam->id, $subject->id, $class->id);
                }
            }
        }

        $this->info('Subject performance data populated successfully!');
        return 0;
    }

    private function processSubjectPerformance($examId, $subjectId, $classId)
    {
        // Get all marks for this exam, subject, and class
        $marks = ExamMark::where('exam_id', $examId)
            ->where('subject_id', $subjectId)
            ->whereHas('student', function($query) use ($classId) {
                $query->where('class_id', $classId);
            })
            ->get();

        // Skip if no marks found
        if ($marks->isEmpty()) {
            return;
        }

        $totalStudents = $marks->count();
        $totalMarks = $marks->sum('marks');
        $averageScore = $totalMarks / $totalStudents;

        $studentsPassed = $marks->where('marks', '>=', 50)->count();
        $studentsFailed = $marks->where('marks', '<', 50)->count();
        $passRate = ($studentsPassed / $totalStudents) * 100;

        $highestScore = $marks->max('marks');
        $lowestScore = $marks->min('marks');

        // Grade distribution
        $gradeDistribution = [
            'A' => $marks->where('marks', '>=', 80)->count(),
            'B' => $marks->where('marks', '>=', 60)->where('marks', '<', 80)->count(),
            'C' => $marks->where('marks', '>=', 50)->where('marks', '<', 60)->count(),
            'D' => $marks->where('marks', '>=', 40)->where('marks', '<', 50)->count(),
            'F' => $marks->where('marks', '<', 40)->count(),
        ];

        // Create or update subject performance record
        SubjectPerformance::updateOrCreate(
            [
                'exam_id' => $examId,
                'subject_id' => $subjectId,
                'class_id' => $classId,
            ],
            [
                'average_score' => round($averageScore, 2),
                'total_students' => $totalStudents,
                'students_passed' => $studentsPassed,
                'students_failed' => $studentsFailed,
                'pass_rate' => round($passRate, 2),
                'highest_score' => $highestScore,
                'lowest_score' => $lowestScore,
                'grade_distribution' => $gradeDistribution,
            ]
        );

        $subject = Subject::find($subjectId);
        $class = ClassModel::find($classId);
        $this->line("  ✓ {$class->name} - {$subject->name}: Avg {$averageScore}%, Pass Rate {$passRate}%");
    }
}
