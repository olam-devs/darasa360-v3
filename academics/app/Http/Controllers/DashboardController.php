<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\PerformanceAnalyticsService;
use App\Models\SchoolEvent;
use App\Models\SchoolUser;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\Exam;
use App\Models\Announcement;
use App\Models\SubjectPerformance;

class DashboardController extends Controller
{
    protected $analyticsService;

    public function __construct(PerformanceAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Main dashboard - routes based on user role
     */
    public function index()
    {
        $user = auth()->user();
        $role = $user->role->name ?? null;

        // Debug logging
        \Log::info('Dashboard accessed', [
            'user_id' => $user->id,
            'role' => $role,
            'role_id' => $user->role_id ?? 'none'
        ]);

        switch ($role) {
            case 'Headmaster':
            case 'headmaster':
            case 'HeadMaster':
                return $this->headmasterDashboard();
            case 'Academic':
            case 'academic':
            case 'ACADEMIC':
                return $this->academicDashboard();
            case 'Teacher':
            case 'teacher':
            case 'subject_teacher':
            case 'SubjectTeacher':
            case 'TEACHER':
                return $this->teacherDashboard();
            case 'ClassTeacher':
            case 'class_teacher':
            case 'Class Teacher':
            case 'CLASS_TEACHER':
                return $this->classTeacherDashboard();
            case 'Accountant':
            case 'accountant':
            case 'ACCOUNTANT':
                return $this->accountantDashboard();
            case 'Owner':
            case 'owner':
            case 'OWNER':
                return $this->ownerDashboard();
            case 'Student':
            case 'student':
            case 'STUDENT':
                // Redirect students to their portal
                return redirect('/students');
            case 'Admin':
            case 'admin':
            case 'ADMIN':
                // Redirect admins to admin panel
                return redirect('/admins/dashboard');
            default:
                // Unknown role - show error with role info
                \Log::error('Unknown role accessing dashboard', ['role' => $role, 'user_id' => $user->id]);
                abort(403, "Your role '{$role}' does not have a dashboard configured. Please contact support.");
        }
    }

    /**
     * Subject Teacher Dashboard
     * Shows: subjects taught, performance graphs, rankings, upcoming exams
     */
    public function teacherDashboard()
    {
        // Get tenant database from session
        $tenantDb = session('school_db');

        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        // Get user from session (works with tenant database)
        $user = session('user');

        // Fallback to auth if session doesn't have user
        if (!$user) {
            $user = auth()->user();
        }

        // If still no user, try to get from session ID
        if (!$user && session('user_id')) {
            $user = \App\Models\SchoolUser::find(session('user_id'));
        }

        // Check if user exists
        if (!$user) {
            \Log::error('No user found in session or auth for teacher dashboard');
            return redirect('/login')->with('error', 'Please login to continue');
        }

        // Get teacher using direct DB query
        try {
            $teacher = \DB::connection('tenant')->table('teachers')
                ->where('user_id', $user->id)
                ->first();
        } catch (\Exception $e) {
            $teacher = null;
        }

        // Auto-create teacher record if missing
        if (!$teacher) {
            try {
                $teacherId = \DB::connection('tenant')->table('teachers')->insertGetId([
                    'user_id' => $user->id,
                    'name' => $user->username ?? 'Teacher',
                    'phone' => $user->phone ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $teacher = \DB::connection('tenant')->table('teachers')
                    ->where('id', $teacherId)
                    ->first();

                \Log::info('Auto-created teacher record for user', ['user_id' => $user->id, 'teacher_id' => $teacherId]);
            } catch (\Exception $e) {
                \Log::error('Failed to create teacher record', ['user_id' => $user->id, 'error' => $e->getMessage()]);

                // If creation fails, return empty dashboard
                $dashboardData = [];
                $upcomingEvents = collect();
                $upcomingExams = collect();
                $announcements = collect();
                $totalStudents = 0;
                $overallAverage = 0;
                $subjectsCount = 0;

                // Create a mock teacher object to prevent null errors
                $teacher = new \stdClass();
                $teacher->id = null;

                return view('dashboard.teacher', compact(
                    'dashboardData',
                    'upcomingEvents',
                    'upcomingExams',
                    'announcements',
                    'teacher',
                    'totalStudents',
                    'overallAverage',
                    'subjectsCount'
                ));
            }
        }

        // Get subjects taught by this teacher
        try {
            $subjectsData = \DB::connection('tenant')
                ->table('subject_teacher')
                ->join('subjects', 'subject_teacher.subject_id', '=', 'subjects.id')
                ->join('classes', 'subject_teacher.class_id', '=', 'classes.id')
                ->where('subject_teacher.teacher_id', $teacher->id)
                ->select('subjects.name as subject_name', 'subjects.id as subject_id',
                         'classes.name as class_name', 'classes.id as class_id')
                ->get();
        } catch (\Exception $e) {
            $subjectsData = collect();
        }

        // Get performance data for each subject
        $dashboardData = [];
        foreach ($subjectsData as $subject) {
            try {
                // Get latest exam for this subject and class
                $latestExam = Exam::latest()->first();

                if ($latestExam) {
                    // Get all students' marks for this subject
                    $marks = \App\Models\ExamMark::where('exam_id', $latestExam->id)
                        ->where('subject_id', $subject->subject_id)
                        ->whereHas('student', function($q) use ($subject) {
                            $q->where('class_id', $subject->class_id);
                        })
                        ->get();

                    $totalMarks = $marks->sum('marks');
                    $studentCount = $marks->count();
                    $average = $studentCount > 0 ? round($totalMarks / $studentCount, 2) : 0;
                    $passed = $marks->where('marks', '>=', 50)->count();
                    $failed = $marks->where('marks', '<', 50)->count();

                    // Grade distribution
                    $gradeA = $marks->where('marks', '>=', 80)->count();
                    $gradeB = $marks->where('marks', '>=', 60)->where('marks', '<', 80)->count();
                    $gradeC = $marks->where('marks', '>=', 50)->where('marks', '<', 60)->count();
                    $gradeD = $marks->where('marks', '>=', 40)->where('marks', '<', 50)->count();
                    $gradeF = $marks->where('marks', '<', 40)->count();

                    $dashboardData[] = [
                        'subject' => $subject->subject_name,
                        'subject_id' => $subject->subject_id,
                        'class' => $subject->class_name,
                        'class_id' => $subject->class_id,
                        'students_count' => $studentCount,
                        'average_score' => $average,
                        'pass_rate' => $studentCount > 0 ? round(($passed / $studentCount) * 100, 2) : 0,
                        'passed' => $passed,
                        'failed' => $failed,
                        'highest' => $marks->max('marks') ?? 0,
                        'lowest' => $marks->min('marks') ?? 0,
                        'grades' => [
                            'A' => $gradeA,
                            'B' => $gradeB,
                            'C' => $gradeC,
                            'D' => $gradeD,
                            'F' => $gradeF,
                        ]
                    ];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Get upcoming exams
        try {
            $upcomingExams = \DB::connection('tenant')->table('exams')
                ->where('start_date', '>=', now())
                ->orderBy('start_date', 'asc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $upcomingExams = collect();
        }

        // Get upcoming events
        try {
            $upcomingEvents = \DB::connection('tenant')->table('school_events')
                ->where('start_date', '>=', now())
                ->where(function ($q) {
                    $q->where('notify_staff', true)
                      ->orWhere('event_type', 'exam');
                })
                ->orderBy('start_date', 'asc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $upcomingEvents = collect();
        }

        // Get recent announcements
        try {
            $announcements = \DB::connection('tenant')->table('announcements')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
        } catch (\Exception $e) {
            $announcements = collect();
        }

        // Overall stats
        $totalStudents = collect($dashboardData)->sum('students_count');
        $overallAverage = collect($dashboardData)->avg('average_score') ?? 0;
        $subjectsCount = count($dashboardData);

        return view('dashboard.teacher', compact(
            'dashboardData',
            'upcomingEvents',
            'upcomingExams',
            'announcements',
            'teacher',
            'totalStudents',
            'overallAverage',
            'subjectsCount'
        ));
    }

    /**
     * Academic Dashboard
     * Shows: overall academic trends, all subjects performance, exam scheduling
     */
    public function academicDashboard()
    {
        // Get tenant database from session
        $tenantDb = session('school_db');

        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        try {
            // Get accurate counts
            $totalStudents = \DB::connection('tenant')->table('students')->count();
            $totalClasses = \DB::connection('tenant')->table('classes')->count();
            $totalSubjects = \DB::connection('tenant')->table('subjects')->count();
            $totalTeachers = \DB::connection('tenant')->table('schoolUsers')
                ->leftJoin('school_roles', 'schoolUsers.role_id', '=', 'school_roles.id')
                ->whereIn('school_roles.name', ['teacher', 'classteacher', 'subject_teacher', 'class_teacher'])
                ->count();

            $dashboardData = [
                'total_students' => $totalStudents,
                'total_classes' => $totalClasses,
                'total_subjects' => $totalSubjects,
                'total_teachers' => $totalTeachers,
            ];
        } catch (\Exception $e) {
            \Log::error('Academic dashboard data error: ' . $e->getMessage());
            $dashboardData = [
                'total_students' => 0,
                'total_classes' => 0,
                'total_subjects' => 0,
                'total_teachers' => 0,
            ];
        }

        // Upcoming exams that need marking
        try {
            $upcomingExams = \DB::connection('tenant')->table('exams')
                ->where('end_date', '>=', now())
                ->get();
        } catch (\Exception $e) {
            $upcomingExams = collect();
        }

        // Recent exams needing verification
        try {
            $pendingExams = \DB::connection('tenant')->table('exams')
                ->where('end_date', '<', now())
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $pendingExams = collect();
        }

        // Get performance data for charts
        $performanceData = $this->getAcademicPerformanceChartData();

        return view('dashboard.academic', compact(
            'dashboardData',
            'upcomingExams',
            'pendingExams',
            'performanceData'
        ));
    }

    /**
     * Get performance data for academic dashboard charts
     */
    private function getAcademicPerformanceChartData()
    {
        try {
            $latestExam = Exam::latest()->first();

            if (!$latestExam) {
                return [
                    'classPerformance' => [],
                    'subjectPerformance' => [],
                    'gradeDistribution' => [],
                    'passRateTrend' => []
                ];
            }

            // Auto-generate performance data if empty
            $performanceCount = SubjectPerformance::where('exam_id', $latestExam->id)->count();
            if ($performanceCount === 0) {
                $this->generateSubjectPerformance($latestExam->id);
            }

            // Class-wise performance
            $classPerformance = SubjectPerformance::where('exam_id', $latestExam->id)
                ->select('class_id', \DB::raw('AVG(average_score) as avg_score'))
                ->groupBy('class_id')
                ->with('classModel')
                ->get()
                ->map(function ($item) {
                    return [
                        'class' => $item->classModel->name ?? 'Unknown',
                        'average' => round($item->avg_score, 2)
                    ];
                });

            // Subject-wise performance across all classes
            $subjectPerformance = SubjectPerformance::where('exam_id', $latestExam->id)
                ->select('subject_id', \DB::raw('AVG(average_score) as avg_score'))
                ->groupBy('subject_id')
                ->with('subject')
                ->get()
                ->map(function ($item) {
                    return [
                        'subject' => $item->subject->name ?? 'Unknown',
                        'average' => round($item->avg_score, 2)
                    ];
                });

            // Overall grade distribution
            $gradeDistribution = SubjectPerformance::where('exam_id', $latestExam->id)
                ->get()
                ->reduce(function ($carry, $item) {
                    $dist = $item->grade_distribution ?? [];
                    $carry['A'] = ($carry['A'] ?? 0) + ($dist['A'] ?? 0);
                    $carry['B'] = ($carry['B'] ?? 0) + ($dist['B'] ?? 0);
                    $carry['C'] = ($carry['C'] ?? 0) + ($dist['C'] ?? 0);
                    $carry['D'] = ($carry['D'] ?? 0) + ($dist['D'] ?? 0);
                    $carry['F'] = ($carry['F'] ?? 0) + ($dist['F'] ?? 0);
                    return $carry;
                }, ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0]);

            // Pass rate trend (last 5 exams)
            $passRateTrend = Exam::latest()
                ->limit(5)
                ->get()
                ->reverse()
                ->map(function ($exam) {
                    $avgPassRate = SubjectPerformance::where('exam_id', $exam->id)
                        ->avg('pass_rate');

                    return [
                        'exam' => $exam->name ?? 'Exam',
                        'pass_rate' => round($avgPassRate ?? 0, 2)
                    ];
                });

            return [
                'classPerformance' => $classPerformance,
                'subjectPerformance' => $subjectPerformance,
                'gradeDistribution' => $gradeDistribution,
                'passRateTrend' => $passRateTrend
            ];

        } catch (\Exception $e) {
            \Log::error('Error getting academic performance data: ' . $e->getMessage());
            return [
                'classPerformance' => [],
                'subjectPerformance' => [],
                'gradeDistribution' => [],
                'passRateTrend' => []
            ];
        }
    }

    /**
     * Headmaster Dashboard
     * Shows: school-wide statistics, staff overview, student enrollment
     */
    public function headmasterDashboard()
    {
        // Get tenant database from session
        $tenantDb = session('school_db');

        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        try {
            // Use direct DB queries for accurate counts
            $totalStudents = \DB::connection('tenant')->table('students')->count();
            $totalStaff = \DB::connection('tenant')->table('schoolUsers')->count();
            $totalClasses = \DB::connection('tenant')->table('classes')->count();
            $upcomingEventsCount = \DB::connection('tenant')->table('school_events')
                ->where('start_date', '>=', now())
                ->count();

            $stats = [
                'total_students' => $totalStudents,
                'total_staff' => $totalStaff,
                'total_classes' => $totalClasses,
                'upcoming_events' => $upcomingEventsCount,
            ];
        } catch (\Exception $e) {
            \Log::error('Headmaster dashboard stats error: ' . $e->getMessage());
            $stats = [
                'total_students' => 0,
                'total_staff' => 0,
                'total_classes' => 0,
                'upcoming_events' => 0,
            ];
        }

        // Get academic overview
        try {
            $academicData = $this->analyticsService->getAcademicDashboard();
        } catch (\Exception $e) {
            $academicData = [];
        }

        // Recent announcements
        try {
            $announcements = \DB::connection('tenant')->table('announcements')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $announcements = collect();
        }

        // Upcoming events
        try {
            $upcomingEvents = \DB::connection('tenant')->table('school_events')
                ->where('start_date', '>=', now())
                ->orderBy('start_date', 'asc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $upcomingEvents = collect();
        }

        // Cross-system jump availability check
        $canJumpToFinance = false;
        $schoolCode = session('school_code');
        $regNo = session('registration_no');
        if ($schoolCode && $regNo) {
            try {
                $ps = \DB::connection('platform')->table('platform_schools')
                    ->where('code', str_pad((int) $schoolCode, 3, '0', STR_PAD_LEFT))
                    ->first();
                if ($ps && $ps->has_finance && $ps->has_academics && $ps->cross_jump_enabled) {
                    $canJumpToFinance = \DB::connection('platform')->table('platform_cross_access')
                        ->where('school_id', $ps->id)
                        ->where('user_ref', $regNo)
                        ->where('is_active', 1)
                        ->exists();
                }
            } catch (\Throwable $e) {
                // platform not reachable — button stays hidden
            }
        }

        return view('dashboard.headmaster', compact(
            'stats',
            'academicData',
            'announcements',
            'upcomingEvents',
            'canJumpToFinance'
        ));
    }

    /**
     * Class Teacher Dashboard
     * Shows: class performance, attendance, student progress, class ranking
     */
    public function classTeacherDashboard()
    {
        // Get tenant database from session
        $tenantDb = session('school_db');

        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        // Get user from session (works with tenant database)
        $user = session('user');

        // Fallback to auth if session doesn't have user
        if (!$user) {
            $user = auth()->user();
        }

        // If still no user, try to get from session ID
        if (!$user && session('user_id')) {
            $user = \App\Models\SchoolUser::find(session('user_id'));
        }

        // Check if user exists
        if (!$user) {
            \Log::error('No user found in session for class teacher dashboard');
            return redirect('/login')->with('error', 'Please login to continue');
        }

        // Get teacher using direct DB query
        try {
            $teacher = \DB::connection('tenant')->table('teachers')
                ->where('user_id', $user->id)
                ->first();
        } catch (\Exception $e) {
            $teacher = null;
        }

        if (!$teacher) {
            return view('dashboard.class_teacher')->with('error', 'Teacher profile not found');
        }

        // Get class info using direct DB query
        try {
            $classTeacher = \DB::connection('tenant')->table('class_teacher')
                ->where('teacher_id', $teacher->id)
                ->first();
            $classId = $classTeacher->class_id ?? null;
            $class = $classId ? \DB::connection('tenant')->table('classes')->where('id', $classId)->first() : null;
        } catch (\Exception $e) {
            return view('dashboard.class_teacher')->with('error', 'Error loading class information');
        }

        if (!$class) {
            return view('dashboard.class_teacher')->with('error', 'No class assigned');
        }

        // Get students using direct DB query
        try {
            $students = \DB::connection('tenant')->table('students')
                ->where('class_id', $class->id)
                ->get();
            $totalStudents = $students->count();
        } catch (\Exception $e) {
            $students = collect();
            $totalStudents = 0;
        }

        // Get latest exam using direct DB query
        try {
            $latestExam = \DB::connection('tenant')->table('exams')
                ->orderBy('created_at', 'desc')
                ->first();
        } catch (\Exception $e) {
            $latestExam = null;
        }

        // Get class performance by subject
        $classPerformance = [];
        $overallAverage = 0;
        $studentsData = [];
        $classRanking = null;
        $subjectComparisons = [];

        if ($latestExam) {
            try {
                $classPerformance = \App\Models\SubjectPerformance::where('class_id', $class->id)
                    ->where('exam_id', $latestExam->id)
                    ->with('subject')
                    ->get();

                // If no subject performance data, auto-generate it
                if ($classPerformance->isEmpty()) {
                    $this->generateSubjectPerformance($latestExam->id, $class->id);
                    $classPerformance = \App\Models\SubjectPerformance::where('class_id', $class->id)
                        ->where('exam_id', $latestExam->id)
                        ->with('subject')
                        ->get();
                }

                $overallAverage = $classPerformance->avg('average_score') ?? 0;
            } catch (\Exception $e) {
                $classPerformance = collect();
            }

            // Calculate school-wide class ranking
            try {
                $allClassesPerformance = \App\Models\SubjectPerformance::where('exam_id', $latestExam->id)
                    ->select('class_id', \DB::raw('AVG(average_score) as class_average'))
                    ->groupBy('class_id')
                    ->orderBy('class_average', 'desc')
                    ->with('classModel')
                    ->get();

                $totalClasses = $allClassesPerformance->count();
                $myClassRank = $allClassesPerformance->search(function($item) use ($class) {
                    return $item->class_id == $class->id;
                });

                $classRanking = [
                    'rank' => $myClassRank !== false ? $myClassRank + 1 : 'N/A',
                    'total_classes' => $totalClasses,
                    'average' => round($overallAverage, 2),
                    'all_classes' => $allClassesPerformance
                ];
            } catch (\Exception $e) {
                $classRanking = [
                    'rank' => 'N/A',
                    'total_classes' => 0,
                    'average' => 0,
                    'all_classes' => collect()
                ];
            }

            // Get subject-wise comparison with other classes
            try {
                foreach ($classPerformance as $perf) {
                    $subjectId = $perf->subject_id;

                    // Get all classes' performance for this subject
                    $allSubjectPerformance = \App\Models\SubjectPerformance::where('exam_id', $latestExam->id)
                        ->where('subject_id', $subjectId)
                        ->orderBy('average_score', 'desc')
                        ->with('classModel')
                        ->get();

                    $subjectRank = $allSubjectPerformance->search(function($item) use ($class, $subjectId) {
                        return $item->class_id == $class->id && $item->subject_id == $subjectId;
                    });

                    $subjectComparisons[] = [
                        'subject' => $perf->subject->name ?? 'N/A',
                        'my_average' => round($perf->average_score ?? 0, 2),
                        'rank' => $subjectRank !== false ? $subjectRank + 1 : 'N/A',
                        'total_classes' => $allSubjectPerformance->count(),
                        'school_average' => round($allSubjectPerformance->avg('average_score'), 2),
                        'top_class_average' => round($allSubjectPerformance->first()->average_score ?? 0, 2)
                    ];
                }
            } catch (\Exception $e) {
                $subjectComparisons = [];
            }

            // Get individual student performance
            try {
                foreach ($students as $student) {
                    $marks = \App\Models\ExamMark::where('exam_id', $latestExam->id)
                        ->where('student_id', $student->id)
                        ->get();

                    $totalMarks = $marks->sum('marks');
                    $subjectCount = $marks->count();
                    $average = $subjectCount > 0 ? round($totalMarks / $subjectCount, 2) : 0;

                    $studentsData[] = [
                        'name' => $student->user->username ?? 'N/A',
                        'student_id' => $student->id,
                        'average' => $average,
                        'total_marks' => $totalMarks,
                        'subjects_count' => $subjectCount,
                        'status' => $average >= 50 ? 'Pass' : 'Needs Attention'
                    ];
                }

                // Sort by average descending
                usort($studentsData, function($a, $b) {
                    return $b['average'] <=> $a['average'];
                });
            } catch (\Exception $e) {
                $studentsData = [];
            }
        }

        // Get upcoming exams
        try {
            $upcomingExams = \DB::connection('tenant')->table('exams')
                ->where('start_date', '>=', now())
                ->orderBy('start_date', 'asc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $upcomingExams = collect();
        }

        // Upcoming events for the class
        try {
            $upcomingEvents = \DB::connection('tenant')->table('school_events')
                ->where('start_date', '>=', now())
                ->where(function ($q) {
                    $q->where('notify_students', true)
                      ->orWhere('notify_staff', true);
                })
                ->orderBy('start_date', 'asc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $upcomingEvents = collect();
        }

        // Get announcements
        try {
            $announcements = \DB::connection('tenant')->table('announcements')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
        } catch (\Exception $e) {
            $announcements = collect();
        }

        // Calculate stats
        $passCount = collect($studentsData)->where('status', 'Pass')->count();
        $failCount = collect($studentsData)->where('status', 'Needs Attention')->count();

        return view('dashboard.class_teacher', compact(
            'class',
            'students',
            'studentsData',
            'classPerformance',
            'upcomingEvents',
            'upcomingExams',
            'announcements',
            'totalStudents',
            'overallAverage',
            'passCount',
            'failCount',
            'classRanking',
            'subjectComparisons'
        ));
    }

    /**
     * Accountant Dashboard
     * Shows: fees collection, payments, financial overview
     */
    public function accountantDashboard()
    {
        // Get tenant database from session
        $tenantDb = session('school_db');

        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        }

        // Financial statistics
        try {
            $totalStudents = \DB::connection('tenant')->table('students')->count();

            // Try to get financial data if tables exist
            $totalFees = 0;
            $collected = 0;

            try {
                $totalFees = \DB::connection('tenant')->table('fees')->sum('amount') ?? 0;
            } catch (\Exception $e) {
                // Fees table might not exist
            }

            try {
                $collected = \DB::connection('tenant')->table('payments')->sum('amount') ?? 0;
            } catch (\Exception $e) {
                // Payments table might not exist
            }

            $stats = [
                'total_fees' => $totalFees,
                'collected' => $collected,
                'pending' => $totalFees - $collected,
                'total_students' => $totalStudents,
            ];
        } catch (\Exception $e) {
            \Log::error('Accountant dashboard stats error: ' . $e->getMessage());
            $stats = [
                'total_fees' => 0,
                'collected' => 0,
                'pending' => 0,
                'total_students' => 0,
            ];
        }

        // Recent payments
        try {
            $recentPayments = \DB::connection('tenant')->table('payments')
                ->leftJoin('students', 'payments.student_id', '=', 'students.id')
                ->select('payments.*', 'students.username as student_name')
                ->orderBy('payments.created_at', 'desc')
                ->limit(10)
                ->get();
        } catch (\Exception $e) {
            $recentPayments = collect();
        }

        return view('dashboard.accountant', compact('stats', 'recentPayments'));
    }

    /**
     * Owner Dashboard
     * Shows: high-level overview, financial summary, staff management
     */
    public function ownerDashboard()
    {
        // Check if user is logged in
        if (!session()->has('user_id') || !session()->has('school_db')) {
            return redirect('/login')->with('error', 'Please login to access the dashboard');
        }

        // Get tenant database from session
        $tenantDb = session('school_db');

        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            \DB::purge('tenant');
            \DB::reconnect('tenant');
        } else {
            return redirect('/login')->with('error', 'Session expired. Please login again.');
        }

        try {
            // Use direct DB queries for accurate data
            $totalStudents = \DB::connection('tenant')->table('students')->count();
            $totalStaff = \DB::connection('tenant')->table('schoolUsers')->count();
            $totalClasses = \DB::connection('tenant')->table('classes')->count();

            // Fetch financial data if tables exist
            $totalRevenue = 0;
            $totalFees = 0;

            try {
                $totalRevenue = \DB::connection('tenant')->table('payments')->sum('amount') ?? 0;
            } catch (\Exception $e) {
                // Payments table might not exist
            }

            try {
                $totalFees = \DB::connection('tenant')->table('fees')->sum('amount') ?? 0;
            } catch (\Exception $e) {
                // Fees table might not exist
            }

            $stats = [
                'total_students' => $totalStudents,
                'total_staff' => $totalStaff,
                'total_classes' => $totalClasses,
                'total_revenue' => $totalRevenue,
                'pending_fees' => $totalFees - $totalRevenue,
            ];
        } catch (\Exception $e) {
            \Log::error('Owner dashboard stats error: ' . $e->getMessage());
            $stats = [
                'total_students' => 0,
                'total_staff' => 0,
                'total_classes' => 0,
                'total_revenue' => 0,
                'pending_fees' => 0,
            ];
        }

        // Recent announcements
        try {
            $announcements = \DB::connection('tenant')->table('announcements')
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();
        } catch (\Exception $e) {
            $announcements = collect();
        }

        // Upcoming events
        try {
            $upcomingEvents = \DB::connection('tenant')->table('school_events')
                ->where('start_date', '>=', now())
                ->orderBy('start_date', 'asc')
                ->limit(5)
                ->get();
        } catch (\Exception $e) {
            $upcomingEvents = collect();
        }

        // Cross-system jump availability check
        $canJumpToFinance = false;
        $schoolCode = session('school_code');
        $regNo = session('registration_no');
        if ($schoolCode && $regNo) {
            try {
                $ps = \DB::connection('platform')->table('platform_schools')
                    ->where('code', str_pad((int) $schoolCode, 3, '0', STR_PAD_LEFT))
                    ->first();
                if ($ps && $ps->has_finance && $ps->has_academics && $ps->cross_jump_enabled) {
                    $canJumpToFinance = \DB::connection('platform')->table('platform_cross_access')
                        ->where('school_id', $ps->id)
                        ->where('user_ref', $regNo)
                        ->where('is_active', 1)
                        ->exists();
                }
            } catch (\Throwable $e) {
                // platform not reachable — button stays hidden
            }
        }

        return view('dashboard.owner', compact('stats', 'announcements', 'upcomingEvents', 'canJumpToFinance'));
    }

    /**
     * Generate subject performance data for a specific exam and class
     */
    private function generateSubjectPerformance($examId, $classId = null)
    {
        try {
            // Get all subjects
            $subjects = \App\Models\Subject::all();

            // Get classes to process
            if ($classId) {
                $classes = \App\Models\ClassModel::where('id', $classId)->get();
            } else {
                $classes = \App\Models\ClassModel::all();
            }

            foreach ($subjects as $subject) {
                foreach ($classes as $class) {
                    // Get all marks for this exam, subject, and class
                    $marks = \App\Models\ExamMark::where('exam_id', $examId)
                        ->where('subject_id', $subject->id)
                        ->whereHas('student', function($query) use ($class) {
                            $query->where('class_id', $class->id);
                        })
                        ->get();

                    // Skip if no marks found
                    if ($marks->isEmpty()) {
                        continue;
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
                    \App\Models\SubjectPerformance::updateOrCreate(
                        [
                            'exam_id' => $examId,
                            'subject_id' => $subject->id,
                            'class_id' => $class->id,
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
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error generating subject performance: ' . $e->getMessage());
        }
    }
}
