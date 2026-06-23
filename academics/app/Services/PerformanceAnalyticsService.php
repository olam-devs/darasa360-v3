<?php

namespace App\Services;

use App\Models\SubjectPerformance;
use App\Models\ExamMark;
use App\Models\Exam;
use App\Models\Subject;
use App\Models\ClassModel;
use App\Models\Student;
use Illuminate\Support\Facades\DB;

class PerformanceAnalyticsService
{
    /**
     * Calculate and store subject performance for an exam
     */
    public function calculateSubjectPerformance($examId, $subjectId, $classId)
    {
        $marks = ExamMark::where('exam_id', $examId)
            ->where('subject_id', $subjectId)
            ->whereHas('student', function ($q) use ($classId) {
                $q->where('class_id', $classId);
            })
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        $totalStudents = $marks->count();
        $totalMarks = $marks->sum('mark');
        $averageScore = $totalMarks / $totalStudents;

        // Pass/Fail (assuming 50% is pass mark)
        $passed = $marks->where('mark', '>=', 50)->count();
        $failed = $totalStudents - $passed;
        $passRate = ($passed / $totalStudents) * 100;

        // Grade distribution
        $gradeDistribution = $this->calculateGradeDistribution($marks);

        // Create or update performance record
        $performance = SubjectPerformance::updateOrCreate(
            [
                'subject_id' => $subjectId,
                'class_id' => $classId,
                'exam_id' => $examId,
            ],
            [
                'average_score' => round($averageScore, 2),
                'total_students' => $totalStudents,
                'students_passed' => $passed,
                'students_failed' => $failed,
                'pass_rate' => round($passRate, 2),
                'highest_score' => $marks->max('mark'),
                'lowest_score' => $marks->min('mark'),
                'grade_distribution' => json_encode($gradeDistribution),
            ]
        );

        // Calculate rankings
        $this->calculateRankings($examId);

        return $performance;
    }

    /**
     * Calculate grade distribution
     */
    private function calculateGradeDistribution($marks)
    {
        $distribution = [
            'A' => 0,
            'B' => 0,
            'C' => 0,
            'D' => 0,
            'F' => 0,
        ];

        foreach ($marks as $mark) {
            $score = $mark->mark;
            if ($score >= 80) $distribution['A']++;
            elseif ($score >= 65) $distribution['B']++;
            elseif ($score >= 50) $distribution['C']++;
            elseif ($score >= 40) $distribution['D']++;
            else $distribution['F']++;
        }

        return $distribution;
    }

    /**
     * Calculate class-wise and school-wise rankings
     */
    private function calculateRankings($examId)
    {
        // Get all performance records for this exam
        $performances = SubjectPerformance::where('exam_id', $examId)->get();

        // Group by class for class rankings
        $byClass = $performances->groupBy('class_id');

        foreach ($byClass as $classId => $classPerformances) {
            $sorted = $classPerformances->sortByDesc('average_score')->values();

            foreach ($sorted as $index => $performance) {
                $performance->update(['class_rank' => $index + 1]);
            }
        }

        // School-wide ranking
        $sorted = $performances->sortByDesc('average_score')->values();

        foreach ($sorted as $index => $performance) {
            $performance->update(['school_rank' => $index + 1]);
        }
    }

    /**
     * Get teacher dashboard data
     */
    public function getTeacherDashboard($teacherId)
    {
        // Get teacher subjects and classes
        $teacher = \App\Models\Teacher::with(['subjectTeachers.subject', 'subjectTeachers.class'])
            ->findOrFail($teacherId);

        $dashboardData = [];

        foreach ($teacher->subjectTeachers as $assignment) {
            $subject = $assignment->subject;
            $class = $assignment->class;

            // Get latest performance
            $latestPerformance = SubjectPerformance::where('subject_id', $subject->id)
                ->where('class_id', $class->id)
                ->latest()
                ->first();

            $dashboardData[] = [
                'subject' => $subject->name,
                'class' => $class->name,
                'current_performance' => $latestPerformance,
                'students_count' => Student::where('class_id', $class->id)->count(),
            ];
        }

        return $dashboardData;
    }

    /**
     * Get academic dashboard data
     */
    public function getAcademicDashboard()
    {
        $latestExam = Exam::latest()->first();

        if (!$latestExam) {
            return [];
        }

        $allPerformances = SubjectPerformance::where('exam_id', $latestExam->id)
            ->with(['subject', 'classModel'])
            ->get();

        // Overall statistics
        $stats = [
            'total_subjects' => $allPerformances->unique('subject_id')->count(),
            'average_pass_rate' => $allPerformances->avg('pass_rate'),
            'total_students_examined' => $allPerformances->sum('total_students'),
            'overall_performance' => $allPerformances->avg('average_score'),
        ];

        return [
            'stats' => $stats,
            'top_subjects' => $allPerformances->sortByDesc('average_score')->take(5),
            'latest_exam' => $latestExam,
        ];
    }
}
