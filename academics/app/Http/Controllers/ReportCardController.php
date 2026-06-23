<?php

namespace App\Http\Controllers;

use App\Models\ReportCard;
use App\Models\ReportCardConfiguration;
use App\Models\ReportCardExamConfig;
use App\Models\ReportCardComment;
use App\Models\CommentTemplate;
use App\Models\ReportCardTemplate;
use App\Models\ParentLetter;
use App\Models\Student;
use App\Models\Exam;
use App\Models\ExamMark;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use ZipArchive;

class ReportCardController extends Controller
{
    /**
     * Display a listing of report card configurations
     */
    public function index()
    {
        $this->switchToTenant();

        $configurations = ReportCardConfiguration::with(['class', 'examConfigs.exam'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('content.dashboard.academic.report_cards.index', compact('configurations'));
    }

    /**
     * Show the form for creating a new report card configuration
     */
    public function create()
    {
        $this->switchToTenant();

        $classes = DB::connection('tenant')->table('classes')->get();
        $exams = Exam::all();
        $templates = ReportCardTemplate::all();

        return view('content.dashboard.academic.report_cards.create', compact('classes', 'exams', 'templates'));
    }

    /**
     * Store a newly created report card configuration
     */
    public function store(Request $request)
    {
        $this->switchToTenant();

        $request->validate([
            'name' => 'required|string|max:255',
            'class_id' => 'required|exists:tenant.classes,id',
            'term' => 'required|string',
            'academic_year' => 'required|string',
            'description' => 'nullable|string',
            'status' => 'required|in:draft,active',
            'exams' => 'required|array|min:1',
            'exams.*.exam_id' => 'required|exists:tenant.exams,id',
            'exams.*.percentage_weight' => 'required|numeric|min:0|max:100',
        ]);

        // Validate that percentages add up to 100
        $totalPercentage = collect($request->exams)->sum('percentage_weight');
        if (abs($totalPercentage - 100) > 0.01) {
            return back()->withErrors(['exams' => 'Exam percentages must add up to 100%. Current total: ' . $totalPercentage . '%'])->withInput();
        }

        DB::connection('tenant')->beginTransaction();
        try {
            // Create configuration
            $configuration = ReportCardConfiguration::create([
                'name' => $request->name,
                'class_id' => $request->class_id,
                'term' => $request->term,
                'academic_year' => $request->academic_year,
                'status' => $request->status ?? 'draft',
                'description' => $request->description,
            ]);

            // Create exam configurations
            $order = 1;
            foreach ($request->exams as $examData) {
                ReportCardExamConfig::create([
                    'report_card_config_id' => $configuration->id,
                    'exam_id' => $examData['exam_id'],
                    'percentage_weight' => $examData['percentage_weight'],
                    'order' => $order,
                ]);
                $order++;
            }

            DB::connection('tenant')->commit();
            return redirect()->route('report-cards.show', $configuration->id)
                ->with('success', 'Report card configuration created successfully!');

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('Failed to create report card configuration: ' . $e->getMessage());
            return back()->with('error', 'Failed to create configuration: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified report card configuration
     */
    public function show($id)
    {
        $this->switchToTenant();

        $configuration = ReportCardConfiguration::with(['class', 'examConfigs.exam'])
            ->findOrFail($id);

        $students = Student::where('class_id', $configuration->class_id)->get();
        $reportCards = ReportCard::where('report_card_config_id', $id)
            ->with('student')
            ->get()
            ->keyBy('student_id');

        return view('content.dashboard.academic.report_cards.show', compact('configuration', 'students', 'reportCards'));
    }

    /**
     * Show form to edit/create parent letter
     */
    public function editParentLetter($configId)
    {
        $this->switchToTenant();

        $configuration = ReportCardConfiguration::with(['class', 'examConfigs.exam'])
            ->findOrFail($configId);

        $parentLetter = ParentLetter::where('report_card_config_id', $configId)->first();

        return view('content.dashboard.academic.report_cards.parent_letter', compact('configuration', 'parentLetter'));
    }

    /**
     * Store parent letter
     */
    public function storeParentLetter(Request $request, $configId)
    {
        $this->switchToTenant();

        $validated = $request->validate([
            'letter_title' => 'required|string|max:255',
            'closing_semester_message' => 'required|string',
            'upcoming_semester_message' => 'required|string',
            'fee_payment_notice' => 'nullable|string',
            'special_announcements' => 'nullable|string',
            'safari_trips' => 'nullable|string',
            'other_information' => 'nullable|string',
            'signature_name' => 'required|string|max:255',
            'signature_title' => 'required|string|max:255',
        ]);

        try {
            ParentLetter::updateOrCreate(
                ['report_card_config_id' => $configId],
                array_merge($validated, ['created_by' => Auth::id()])
            );

            // Update configuration to mark that it has a parent letter
            ReportCardConfiguration::where('id', $configId)
                ->update(['has_parent_letter' => true]);

            return redirect()->route('report-cards.show', $configId)
                ->with('success', 'Parent letter saved successfully! You can now generate reports.');

        } catch (\Exception $e) {
            Log::error('Failed to save parent letter: ' . $e->getMessage());
            return back()->with('error', 'Failed to save parent letter: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Generate report cards for all students in the configuration
     */
    public function generateReportCards($configId)
    {
        $this->switchToTenant();

        $configuration = ReportCardConfiguration::with(['class', 'examConfigs.exam'])
            ->findOrFail($configId);

        $students = Student::where('class_id', $configuration->class_id)->get();

        DB::connection('tenant')->beginTransaction();
        try {
            foreach ($students as $student) {
                $this->generateSingleReportCard($configuration, $student);
            }

            $configuration->status = 'active';
            $configuration->save();

            DB::connection('tenant')->commit();
            return back()->with('success', 'Report cards generated successfully for all students!');

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('Failed to generate report cards: ' . $e->getMessage());
            return back()->with('error', 'Failed to generate report cards: ' . $e->getMessage());
        }
    }

    /**
     * Generate a single report card for a student
     */
    private function generateSingleReportCard($configuration, $student)
    {
        // Get all exam marks for this student from configured exams
        $examIds = $configuration->examConfigs->pluck('exam_id');

        // Get exam configurations with weights
        $examConfigs = $configuration->examConfigs->keyBy('exam_id');

        // Fetch all marks for the student in these exams
        $marks = ExamMark::where('student_id', $student->id)
            ->whereIn('exam_id', $examIds)
            ->with(['exam', 'subject'])
            ->get();

        // Group marks by subject
        $subjectMarks = $marks->groupBy('subject_id');

        $subjectPerformance = [];
        $totalWeightedMarks = 0;
        $totalSubjects = 0;

        foreach ($subjectMarks as $subjectId => $subjectExamMarks) {
            $subjectName = $subjectExamMarks->first()->subject->name ?? 'Unknown';
            $examBreakdown = [];
            $subjectWeightedTotal = 0;

            foreach ($subjectExamMarks as $mark) {
                $examConfig = $examConfigs->get($mark->exam_id);
                if ($examConfig) {
                    $weight = $examConfig->percentage_weight / 100;
                    $weightedMark = $mark->marks * $weight;
                    $subjectWeightedTotal += $weightedMark;

                    $examBreakdown[] = [
                        'exam_name' => $mark->exam->name,
                        'marks' => $mark->marks,
                        'weight' => $examConfig->percentage_weight,
                        'weighted_marks' => round($weightedMark, 2),
                    ];
                }
            }

            $subjectPerformance[] = [
                'subject_id' => $subjectId,
                'subject_name' => $subjectName,
                'exams' => $examBreakdown,
                'total_weighted_marks' => round($subjectWeightedTotal, 2),
                'grade' => $this->calculateGrade($subjectWeightedTotal),
            ];

            $totalWeightedMarks += $subjectWeightedTotal;
            $totalSubjects++;
        }

        $averageMarks = $totalSubjects > 0 ? $totalWeightedMarks / $totalSubjects : 0;
        $overallGrade = $this->calculateGrade($averageMarks);

        // Calculate class position
        $classPosition = $this->calculateClassPosition($configuration, $student->id, $averageMarks);
        $totalStudents = Student::where('class_id', $configuration->class_id)->count();

        // Create or update report card
        $reportCard = ReportCard::updateOrCreate(
            [
                'report_card_config_id' => $configuration->id,
                'student_id' => $student->id,
            ],
            [
                'template_id' => ReportCardTemplate::where('is_default', true)->first()?->id,
                'total_marks' => round($totalWeightedMarks, 2),
                'average_marks' => round($averageMarks, 2),
                'overall_grade' => $overallGrade,
                'class_position' => $classPosition,
                'total_students_in_class' => $totalStudents,
                'subject_performance' => $subjectPerformance,
                'status' => 'draft',
            ]
        );

        // Combine and store teacher comments
        $this->combineAndStoreTeacherComments($configuration, $student, $marks);

        // Pull and store class teacher's general comment from student record
        if (!empty($student->comments)) {
            ReportCardComment::updateOrCreate(
                [
                    'report_card_config_id' => $configuration->id,
                    'student_id' => $student->id,
                    'stakeholder_type' => 'class_teacher',
                    'subject_id' => null,
                ],
                [
                    'comment' => $student->comments,
                    'commented_by' => null,
                ]
            );
        }

        return $reportCard;
    }

    /**
     * Calculate grade based on marks
     */
    private function calculateGrade($marks)
    {
        if ($marks >= 80) return 'A';
        if ($marks >= 70) return 'B';
        if ($marks >= 60) return 'C';
        if ($marks >= 50) return 'D';
        if ($marks >= 40) return 'E';
        return 'F';
    }

    /**
     * Calculate class position
     */
    private function calculateClassPosition($configuration, $studentId, $averageMarks)
    {
        $students = Student::where('class_id', $configuration->class_id)->get();
        $positions = [];

        foreach ($students as $student) {
            $reportCard = ReportCard::where('report_card_config_id', $configuration->id)
                ->where('student_id', $student->id)
                ->first();

            if ($reportCard) {
                $positions[$student->id] = $reportCard->average_marks ?? 0;
            } else {
                // Calculate on the fly if not yet generated
                $positions[$student->id] = 0;
            }
        }

        // Update current student
        $positions[$studentId] = $averageMarks;

        // Sort in descending order
        arsort($positions);

        // Find position
        $position = 1;
        foreach ($positions as $sid => $avg) {
            if ($sid == $studentId) {
                return $position;
            }
            $position++;
        }

        return $position;
    }

    /**
     * Manage comments for a student
     */
    public function manageComments($configId, $studentId)
    {
        $this->switchToTenant();

        $configuration = ReportCardConfiguration::findOrFail($configId);
        $student = Student::findOrFail($studentId);

        // Get existing comments
        $comments = ReportCardComment::where('report_card_config_id', $configId)
            ->where('student_id', $studentId)
            ->with(['subject', 'commentedBy'])
            ->get();

        // Organize existing comments by stakeholder type
        $existingComments = [
            'accountant' => $comments->where('stakeholder_type', 'accountant')->first(),
            'discipline' => $comments->where('stakeholder_type', 'discipline')->first(),
            'class_teacher' => $comments->where('stakeholder_type', 'class_teacher')->first(),
            'academic' => $comments->where('stakeholder_type', 'academic')->first(),
            'subject_teacher' => $comments->where('stakeholder_type', 'subject_teacher')->keyBy('subject_id'),
        ];

        // Get comment templates
        $commentTemplates = CommentTemplate::all();

        // Get subjects for the class
        $subjects = DB::connection('tenant')
            ->table('subjects')
            ->join('classroom_subject', 'subjects.id', '=', 'classroom_subject.subject_id')
            ->where('classroom_subject.classroom_id', $configuration->class_id)
            ->select('subjects.*')
            ->get();

        return view('content.dashboard.academic.report_cards.comments', compact('configuration', 'student', 'existingComments', 'commentTemplates', 'subjects'));
    }

    /**
     * Store or update a comment
     */
    public function storeComment(Request $request)
    {
        $this->switchToTenant();

        $request->validate([
            'report_card_config_id' => 'required|exists:tenant.report_card_configurations,id',
            'student_id' => 'required|exists:tenant.students,id',
            'stakeholder_type' => 'required|in:accountant,discipline,class_teacher,subject_teacher,academic',
            'subject_id' => 'nullable|exists:tenant.subjects,id',
            'comment' => 'required|string',
        ]);

        try {
            $commentData = [
                'report_card_config_id' => $request->report_card_config_id,
                'student_id' => $request->student_id,
                'stakeholder_type' => $request->stakeholder_type,
                'subject_id' => $request->subject_id,
                'comment' => $request->comment,
                'commented_by' => Auth::id(),
            ];

            // For subject teachers, allow multiple comments
            if ($request->stakeholder_type === 'subject_teacher') {
                ReportCardComment::create($commentData);
            } else {
                // For others, update or create
                ReportCardComment::updateOrCreate(
                    [
                        'report_card_config_id' => $request->report_card_config_id,
                        'student_id' => $request->student_id,
                        'stakeholder_type' => $request->stakeholder_type,
                    ],
                    $commentData
                );
            }

            return back()->with('success', 'Comment saved successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to save comment: ' . $e->getMessage());
            return back()->with('error', 'Failed to save comment: ' . $e->getMessage());
        }
    }

    /**
     * Apply default template to all students
     */
    public function applyDefaultTemplates($configId)
    {
        $this->switchToTenant();

        $configuration = ReportCardConfiguration::findOrFail($configId);
        $students = Student::where('class_id', $configuration->class_id)->get();

        $stakeholderTypes = ['accountant', 'discipline', 'class_teacher', 'academic'];

        DB::connection('tenant')->beginTransaction();
        try {
            foreach ($students as $student) {
                foreach ($stakeholderTypes as $type) {
                    $template = CommentTemplate::getDefaultTemplate($type);

                    if ($template) {
                        ReportCardComment::updateOrCreate(
                            [
                                'report_card_config_id' => $configId,
                                'student_id' => $student->id,
                                'stakeholder_type' => $type,
                            ],
                            [
                                'comment' => $template->content,
                                'commented_by' => Auth::id(),
                            ]
                        );
                    }
                }
            }

            DB::connection('tenant')->commit();
            return back()->with('success', 'Default templates applied to all students!');

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('Failed to apply templates: ' . $e->getMessage());
            return back()->with('error', 'Failed to apply templates: ' . $e->getMessage());
        }
    }

    /**
     * Export report card as PDF - Ultra Modern Design
     */
    public function exportPDF($reportCardId)
    {
        Log::info('=== PDF Export Started ===', ['reportCardId' => $reportCardId]);

        $this->switchToTenant();
        Log::info('Switched to tenant database');

        $reportCard = ReportCard::with([
            'configuration.class',
            'configuration.examConfigs.exam',
            'student',
            'template'
        ])->findOrFail($reportCardId);

        Log::info('Report card loaded', [
            'student' => $reportCard->student->first_name ?? 'Unknown',
            'class' => $reportCard->configuration->class->name ?? 'Unknown'
        ]);

        $comments = ReportCardComment::getCommentsForStudent(
            $reportCard->report_card_config_id,
            $reportCard->student_id
        );
        Log::info('Comments loaded', ['count' => is_array($comments) ? count($comments) : 0]);

        $schoolInfo = $this->getSchoolInfo();
        Log::info('School info loaded', ['school' => $schoolInfo['name'] ?? 'Unknown']);

        // Get parent letter if exists
        $parentLetter = ParentLetter::where('report_card_config_id', $reportCard->report_card_config_id)->first();
        Log::info('Parent letter loaded', ['exists' => $parentLetter ? 'yes' : 'no']);

        $data = [
            'reportCard' => $reportCard,
            'student' => $reportCard->student,
            'configuration' => $reportCard->configuration,
            'comments' => $comments,
            'schoolInfo' => $schoolInfo,
            'parentLetter' => $parentLetter,
        ];

        try {
            Log::info('Attempting to load PDF view: content.dashboard.academic.report_cards.pdf_modern_simple');

            $pdf = PDF::loadView('content.dashboard.academic.report_cards.pdf_modern_simple', $data)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                ]);

            Log::info('PDF object created successfully');

            $regNo = $reportCard->student->registration_no ?? 'Student';
            $term = str_replace(' ', '_', $reportCard->configuration->term ?? 'Report');
            $filename = 'ReportCard_' . $regNo . '_' . $term . '.pdf';

            Log::info('PDF download starting', ['filename' => $filename]);

            return $pdf->download($filename);
        } catch (\Exception $e) {
            Log::error('PDF Export Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    /**
     * Bulk export report cards for a class - generates individual PDFs in a ZIP file
     */
    public function bulkExportPDF($configId)
    {
        $this->switchToTenant();

        $configuration = ReportCardConfiguration::with(['class', 'examConfigs.exam'])
            ->findOrFail($configId);

        $reportCards = ReportCard::where('report_card_config_id', $configId)
            ->where('status', '!=', 'draft')
            ->with(['student', 'configuration.class', 'configuration.examConfigs.exam', 'template'])
            ->get();

        if ($reportCards->isEmpty()) {
            return back()->with('error', 'No finalized report cards to export.');
        }

        $schoolInfo = $this->getSchoolInfo();

        // Get parent letter if exists
        $parentLetter = ParentLetter::where('report_card_config_id', $configId)->first();

        // Create temporary directory for PDFs
        $tempDir = storage_path('app/temp/report-cards-' . time());
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        try {
            $generatedCount = 0;

            // Generate individual PDFs for each student
            foreach ($reportCards as $reportCard) {
                try {
                    $comments = ReportCardComment::getCommentsForStudent(
                        $reportCard->report_card_config_id,
                        $reportCard->student_id
                    );

                    $data = [
                        'reportCard' => $reportCard,
                        'student' => $reportCard->student,
                        'configuration' => $reportCard->configuration,
                        'comments' => $comments,
                        'schoolInfo' => $schoolInfo,
                        'parentLetter' => $parentLetter,
                    ];

                    $pdf = PDF::loadView('content.dashboard.academic.report_cards.pdf_modern_simple', $data)
                        ->setPaper('a4', 'portrait')
                        ->setOptions([
                            'isHtml5ParserEnabled' => true,
                            'isRemoteEnabled' => true,
                            'defaultFont' => 'DejaVu Sans',
                        ]);

                    // Generate safe filename
                    $regNo = $reportCard->student->registration_no ?? 'Student';
                    $firstName = $reportCard->student->first_name ?? 'Unknown';
                    $lastName = $reportCard->student->last_name ?? '';
                    $term = str_replace(' ', '_', $configuration->term ?? 'Report');

                    $filename = $this->sanitizeFilename(
                        $regNo . '-' . $firstName . '-' . $lastName . '-' . $term
                    ) . '.pdf';

                    $pdf->save($tempDir . '/' . $filename);
                    $generatedCount++;
                } catch (\Exception $e) {
                    Log::error("Failed to generate PDF for student {$reportCard->student_id}: " . $e->getMessage());
                    continue;
                }
            }

            if ($generatedCount === 0) {
                throw new \Exception('No PDFs were generated successfully.');
            }

            // Create ZIP file
            $zipFilename = 'ReportCards_' . $this->sanitizeFilename($configuration->class->name) . '_' .
                          str_replace(' ', '_', $configuration->term) . '_' . date('Ymd_His') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFilename);

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                // Add all PDFs to ZIP
                $files = glob($tempDir . '/*.pdf');
                foreach ($files as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();

                // Clean up temporary PDF files
                foreach ($files as $file) {
                    unlink($file);
                }
                rmdir($tempDir);

                // Return ZIP file for download
                return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);
            } else {
                throw new \Exception('Failed to create ZIP file');
            }

        } catch (\Exception $e) {
            // Clean up on error
            if (file_exists($tempDir)) {
                $files = glob($tempDir . '/*.pdf');
                if ($files) {
                    array_map('unlink', $files);
                }
                @rmdir($tempDir);
            }

            Log::error('Failed to bulk export report cards: ' . $e->getMessage());
            return back()->with('error', 'Failed to export report cards: ' . $e->getMessage());
        }
    }

    /**
     * Sanitize filename for safe file system use
     */
    private function sanitizeFilename($filename)
    {
        // Remove special characters and replace spaces with underscores
        $filename = preg_replace('/[^A-Za-z0-9\-_]/', '_', $filename);
        $filename = preg_replace('/_+/', '_', $filename);
        return trim($filename, '_');
    }

    /**
     * Publish report cards
     */
    public function publish($configId)
    {
        $this->switchToTenant();

        try {
            // Update report cards status
            ReportCard::where('report_card_config_id', $configId)
                ->update([
                    'status' => 'published',
                    'published_at' => now(),
                ]);

            // Also update configuration status
            ReportCardConfiguration::where('id', $configId)
                ->update([
                    'status' => 'published',
                ]);

            return back()->with('success', 'Report cards published successfully!');

        } catch (\Exception $e) {
            Log::error('Failed to publish report cards: ' . $e->getMessage());
            return back()->with('error', 'Failed to publish report cards: ' . $e->getMessage());
        }
    }

    /**
     * Get school information
     */
    private function getSchoolInfo()
    {
        $tenantDb = session('school_db');
        config(['database.connections.tenant.database' => $tenantDb]);
        DB::purge('tenant');
        DB::reconnect('tenant');

        $schoolInfo = School::where('school_code', session('school_code'))->first();
        $schoolArray = $schoolInfo ? $schoolInfo->toArray() : [];

        $reportDetail = DB::connection('tenant')->table('report_details')->first();

        if ($reportDetail) {
            $schoolArray['report_phone'] = $reportDetail->phone ?? null;
            $schoolArray['report_email'] = $reportDetail->email ?? null;
            $schoolArray['report_address'] = $reportDetail->address ?? null;
        }

        return $schoolArray;
    }

    /**
     * Switch to tenant database
     */
    /**
     * Delete a report card configuration
     */
    public function destroy($id)
    {
        $this->switchToTenant();

        try {
            $configuration = ReportCardConfiguration::findOrFail($id);

            // Delete related records
            DB::connection('tenant')->beginTransaction();

            // Delete comments
            ReportCardComment::where('report_card_config_id', $id)->delete();

            // Delete report cards
            ReportCard::where('report_card_config_id', $id)->delete();

            // Delete exam configs
            ReportCardExamConfig::where('report_card_config_id', $id)->delete();

            // Delete configuration
            $configuration->delete();

            DB::connection('tenant')->commit();

            return redirect()->route('report-cards.index')
                ->with('success', 'Report card configuration deleted successfully!');

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('Failed to delete report card configuration: ' . $e->getMessage());
            return back()->with('error', 'Failed to delete configuration: ' . $e->getMessage());
        }
    }

    /**
     * Combine teacher comments from multiple exams for each subject
     */
    private function combineAndStoreTeacherComments($configuration, $student, $marks)
    {
        // Group marks by subject
        $subjectMarks = $marks->groupBy('subject_id');

        foreach ($subjectMarks as $subjectId => $subjectExamMarks) {
            $comments = [];

            // Collect all non-empty teacher comments for this subject
            foreach ($subjectExamMarks as $mark) {
                if (!empty($mark->teacher_comment)) {
                    $examName = $mark->exam->name ?? 'Exam';
                    $comments[] = "({$examName}) {$mark->teacher_comment}";
                }
            }

            // If there are comments, combine and store them
            if (!empty($comments)) {
                $combinedComment = implode('. ', $comments);

                // Store in report_card_comments table
                ReportCardComment::updateOrCreate(
                    [
                        'report_card_config_id' => $configuration->id,
                        'student_id' => $student->id,
                        'stakeholder_type' => 'subject_teacher',
                        'subject_id' => $subjectId,
                    ],
                    [
                        'comment' => $combinedComment,
                        'commented_by' => null, // Multiple teachers, so leave null
                    ]
                );
            }
        }
    }

    private function switchToTenant()
    {
        $tenantDb = session('school_db');

        // Middleware should have already set this, but double-check
        if ($tenantDb) {
            config(['database.connections.tenant.database' => $tenantDb]);
            DB::purge('tenant');
            DB::reconnect('tenant');
        }
        // Note: If tenantDb is not set, middleware will handle redirection
    }

    /**
     * Mass distribute report cards via SMS and Email
     */
    public function massDistribute(Request $request, $configId)
    {
        $this->switchToTenant();

        $validated = $request->validate([
            'distribution_type' => 'required|in:sms,email,both',
            'class_id' => 'nullable|exists:classes,id',
        ]);

        $configuration = ReportCardConfiguration::with(['class', 'examConfigs.exam'])->findOrFail($configId);

        // Get students
        $studentsQuery = Student::query();
        if ($validated['class_id']) {
            $studentsQuery->where('class_id', $validated['class_id']);
        } else {
            $studentsQuery->where('class_id', $configuration->class_id);
        }

        $students = $studentsQuery->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'No students found for distribution.');
        }

        $schoolInfo = $this->getSchoolInfo();
        $parentLetter = ParentLetter::where('report_card_config_id', $configId)->first();

        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($students as $student) {
            try {
                // Get report card
                $reportCard = ReportCard::where('report_card_config_id', $configId)
                    ->where('student_id', $student->id)
                    ->with(['configuration.class', 'configuration.examConfigs.exam', 'student'])
                    ->first();

                if (!$reportCard) {
                    $errorCount++;
                    $errors[] = "No report card found for {$student->first_name} {$student->last_name}";
                    continue;
                }

                // Send Email with PDF attachment
                if (in_array($validated['distribution_type'], ['email', 'both'])) {
                    $email = $student->parent_email ?? $student->email;
                    if ($email) {
                        $this->sendReportCardEmail($reportCard, $student, $configuration, $schoolInfo, $parentLetter, $email);
                    }
                }

                // Send SMS
                if (in_array($validated['distribution_type'], ['sms', 'both'])) {
                    $phone = $student->parent_phone ?? $student->phone_number;
                    if ($phone) {
                        $smsMessage = $this->prepareReportSMS($student, $reportCard, $configuration);

                        \App\Models\NotificationQueue::create([
                            'type' => 'sms',
                            'recipient_phone' => $phone,
                            'recipient_email' => null,
                            'recipient_name' => $student->parent_name ?? $student->first_name . ' ' . $student->last_name,
                            'subject' => "Report Card - {$student->first_name} {$student->last_name}",
                            'message' => $smsMessage,
                            'status' => 'pending',
                            'priority' => 'high',
                            'notification_category' => 'report_card',
                            'related_id' => $configId,
                            'related_type' => 'report_card',
                            'scheduled_at' => now(),
                        ]);
                    }
                }

                $successCount++;

            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = "Error for {$student->first_name} {$student->last_name}: " . $e->getMessage();
                Log::error("Failed to distribute report card for student {$student->id}: " . $e->getMessage());
            }
        }

        if ($errorCount > 0) {
            $errorMessage = "Report cards distributed to {$successCount} parents/students. {$errorCount} failed. Errors: " . implode('; ', array_slice($errors, 0, 3));
            return back()->with('warning', $errorMessage);
        }

        return back()->with('success', "Report cards successfully distributed to {$successCount} parents/students!");
    }

    /**
     * Send report card via email with PDF attachment
     */
    private function sendReportCardEmail($reportCard, $student, $configuration, $schoolInfo, $parentLetter, $recipientEmail)
    {
        // Generate PDF
        $comments = ReportCardComment::getCommentsForStudent(
            $reportCard->report_card_config_id,
            $reportCard->student_id
        );

        $data = [
            'reportCard' => $reportCard,
            'student' => $student,
            'configuration' => $configuration,
            'comments' => $comments,
            'schoolInfo' => $schoolInfo,
            'parentLetter' => $parentLetter,
        ];

        $pdf = PDF::loadView('content.dashboard.academic.report_cards.pdf', $data);

        // Save PDF temporarily
        $tempDir = storage_path('app/temp/report-emails');
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        $pdfFilename = 'report-card-' . $student->registration_no . '-' . time() . '.pdf';
        $pdfPath = $tempDir . '/' . $pdfFilename;
        $pdf->save($pdfPath);

        // Send email
        $emailData = [
            'studentName' => $student->first_name . ' ' . $student->last_name,
            'className' => $configuration->class->name,
            'term' => $configuration->term,
            'academicYear' => $configuration->academic_year,
            'schoolName' => $schoolInfo['name'] ?? 'School',
            'pdfPath' => $pdfPath,
            'averageMarks' => $reportCard->average_marks,
            'position' => $reportCard->class_position,
            'totalStudents' => $reportCard->total_students_in_class,
            'grade' => $reportCard->overall_grade,
        ];

        \Illuminate\Support\Facades\Mail::to($recipientEmail)
            ->send(new \App\Mail\ReportCardMail($emailData));

        // Clean up PDF after sending
        if (file_exists($pdfPath)) {
            unlink($pdfPath);
        }
    }

    /**
     * Distribute all report cards for a configuration
     */
    public function distributeAll(Request $request)
    {
        $this->switchToTenant();

        $validated = $request->validate([
            'configuration_id' => 'required|exists:report_card_configurations,id',
            'distribution_type' => 'required|in:sms,email,both',
        ]);

        return $this->massDistribute($request, $validated['configuration_id']);
    }

    /**
     * Prepare SMS message for report card
     */
    private function prepareReportSMS($student, $reportCard, $configuration)
    {
        $studentName = $student->first_name . ' ' . $student->last_name;
        $message = "REPORT CARD - {$studentName}\n\n";
        $message .= "Class: " . ($student->class->name ?? 'N/A') . "\n";
        $message .= "Term: {$configuration->term}\n";
        $message .= "Academic Year: {$configuration->academic_year}\n\n";

        // Add summary
        $message .= "Average Marks: {$reportCard->average_marks}%\n";
        $message .= "Position: {$reportCard->class_position} of {$reportCard->total_students_in_class}\n";
        $message .= "Grade: {$reportCard->overall_grade}\n\n";

        // Add subject summary (top 3 from subject_performance)
        $subjectPerformance = $reportCard->subject_performance;
        if ($subjectPerformance && is_array($subjectPerformance)) {
            // Sort by total_weighted_marks descending
            usort($subjectPerformance, function($a, $b) {
                return ($b['total_weighted_marks'] ?? 0) <=> ($a['total_weighted_marks'] ?? 0);
            });

            $message .= "TOP SUBJECTS:\n";
            $count = 0;
            foreach ($subjectPerformance as $subject) {
                if ($count >= 3) break;
                $subjectName = $subject['subject_name'] ?? 'N/A';
                $marks = $subject['total_weighted_marks'] ?? 0;
                $grade = $subject['grade'] ?? 'N/A';
                $message .= "- {$subjectName}: {$marks}% ({$grade})\n";
                $count++;
            }
        }

        $message .= "\nLogin to the portal to view full report.\n";
        $message .= "Thank you.";

        return $message;
    }

    /**
     * Check distribution status
     */
    public function distributionStatus($configId)
    {
        $this->switchToTenant();

        $configuration = ReportCardConfiguration::findOrFail($configId);

        // Get notification statistics for this configuration
        $stats = \App\Models\NotificationQueue::where('notification_category', 'report_card')
            ->where('related_id', $configId)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return response()->json([
            'configuration_id' => $configId,
            'total_queued' => array_sum($stats),
            'sent' => $stats['sent'] ?? 0,
            'pending' => $stats['pending'] ?? 0,
            'failed' => $stats['failed'] ?? 0,
        ]);
    }

    /**
     * Class teacher view - List all report configurations for their class
     */
    public function classTeacherIndex()
    {
        $this->switchToTenant();

        $user = Auth::user();

        // Get teacher's assigned class (assuming teacher has a class_id or assigned_class_id)
        $teacher = DB::connection('tenant')->table('teachers')->where('user_id', $user->id)->first();

        if (!$teacher || !$teacher->class_id) {
            return view('content.dashboard.classTeacher.report_cards.index')
                ->with('error', 'You are not assigned to any class.')
                ->with('configurations', collect([]));
        }

        $configurations = ReportCardConfiguration::with(['class', 'examConfigs.exam'])
            ->where('class_id', $teacher->class_id)
            ->where('status', 'active')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('content.dashboard.classTeacher.report_cards.index', compact('configurations'));
    }

    /**
     * Class teacher view - View specific report configuration
     */
    public function classTeacherShow($id)
    {
        $this->switchToTenant();

        $user = Auth::user();
        $teacher = DB::connection('tenant')->table('teachers')->where('user_id', $user->id)->first();

        if (!$teacher || !$teacher->class_id) {
            abort(403, 'You are not assigned to any class.');
        }

        $configuration = ReportCardConfiguration::with(['class', 'examConfigs.exam'])
            ->where('id', $id)
            ->where('class_id', $teacher->class_id)
            ->firstOrFail();

        $students = Student::where('class_id', $configuration->class_id)->get();
        $reportCards = ReportCard::where('report_card_config_id', $id)
            ->where('status', '!=', 'draft')
            ->with('student')
            ->get()
            ->keyBy('student_id');

        return view('content.dashboard.classTeacher.report_cards.show', compact('configuration', 'students', 'reportCards'));
    }

    /**
     * Class teacher view - View individual student report card
     */
    public function classTeacherViewReport($reportCardId)
    {
        $this->switchToTenant();

        $user = Auth::user();
        $teacher = DB::connection('tenant')->table('teachers')->where('user_id', $user->id)->first();

        if (!$teacher || !$teacher->class_id) {
            abort(403, 'You are not assigned to any class.');
        }

        $reportCard = ReportCard::with([
            'configuration.class',
            'configuration.examConfigs.exam',
            'student',
            'template'
        ])->findOrFail($reportCardId);

        // Verify teacher has access to this student's report (same class)
        if ($reportCard->student->class_id != $teacher->class_id) {
            abort(403, 'You do not have permission to view this report card.');
        }

        $comments = ReportCardComment::getCommentsForStudent(
            $reportCard->report_card_config_id,
            $reportCard->student_id
        );

        $schoolInfo = $this->getSchoolInfo();
        $parentLetter = ParentLetter::where('report_card_config_id', $reportCard->report_card_config_id)->first();

        return view('content.dashboard.classTeacher.report_cards.view', compact('reportCard', 'comments', 'schoolInfo', 'parentLetter'));
    }

    /**
     * Class teacher download report card PDF
     */
    public function classTeacherDownloadPDF($reportCardId)
    {
        $this->switchToTenant();

        $user = Auth::user();
        $teacher = DB::connection('tenant')->table('teachers')->where('user_id', $user->id)->first();

        if (!$teacher || !$teacher->class_id) {
            abort(403, 'You are not assigned to any class.');
        }

        $reportCard = ReportCard::with([
            'configuration.class',
            'configuration.examConfigs.exam',
            'student',
            'template'
        ])->findOrFail($reportCardId);

        // Verify teacher has access to this student's report
        if ($reportCard->student->class_id != $teacher->class_id) {
            abort(403, 'You do not have permission to download this report card.');
        }

        $comments = ReportCardComment::getCommentsForStudent(
            $reportCard->report_card_config_id,
            $reportCard->student_id
        );

        $schoolInfo = $this->getSchoolInfo();
        $parentLetter = ParentLetter::where('report_card_config_id', $reportCard->report_card_config_id)->first();

        $data = [
            'reportCard' => $reportCard,
            'student' => $reportCard->student,
            'configuration' => $reportCard->configuration,
            'comments' => $comments,
            'schoolInfo' => $schoolInfo,
            'parentLetter' => $parentLetter,
        ];

        $pdf = PDF::loadView('content.dashboard.academic.report_cards.pdf', $data);

        $filename = 'report-card-' . $reportCard->student->registration_no . '-' . $reportCard->configuration->term . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Enhanced PDF Export with QR Code and Modern Design
     */
    public function exportPDFEnhanced($reportCardId)
    {
        $this->switchToTenant();

        $reportCard = ReportCard::with([
            'configuration.class',
            'configuration.examConfigs.exam',
            'student',
            'template'
        ])->findOrFail($reportCardId);

        $comments = ReportCardComment::getCommentsForStudent(
            $reportCard->report_card_config_id,
            $reportCard->student_id
        );

        $schoolInfo = $this->getSchoolInfo();
        $parentLetter = ParentLetter::where('report_card_config_id', $reportCard->report_card_config_id)->first();

        // Generate QR Code
        $qrCode = $this->generateQRCode($reportCard);

        // Get school logo path
        $schoolInfo['logo_path'] = $this->getSchoolLogoPath($schoolInfo);

        $data = [
            'reportCard' => $reportCard,
            'student' => $reportCard->student,
            'configuration' => $reportCard->configuration,
            'comments' => $comments,
            'schoolInfo' => $schoolInfo,
            'parentLetter' => $parentLetter,
            'qrCode' => $qrCode,
        ];

        $pdf = PDF::loadView('content.dashboard.academic.report_cards.pdf_enhanced', $data)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
            ]);

        $filename = 'report-card-' . $reportCard->student->registration_no . '-' . $reportCard->configuration->term . '.pdf';

        // Save PDF path to database
        $pdfPath = 'reports/' . $filename;
        $reportCard->update([
            'pdf_generated' => true,
            'pdf_path' => $pdfPath,
        ]);

        return $pdf->download($filename);
    }

    /**
     * Generate QR Code for report card verification
     */
    private function generateQRCode($reportCard)
    {
        try {
            $hash = hash('sha256', $reportCard->id . $reportCard->student_id . config('app.key'));

            $verificationUrl = route('report-cards.verify', [
                'id' => $reportCard->id,
                'hash' => $hash
            ]);

            $qrCode = QrCode::size(100)
                ->margin(0)
                ->style('round')
                ->eye('circle')
                ->format('svg')
                ->generate($verificationUrl);

            return $qrCode;
        } catch (\Exception $e) {
            Log::error('QR Code generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Public verification route for QR code
     */
    public function verify(Request $request, $id)
    {
        try {
            $hash = $request->query('hash');

            // Find report card
            $reportCard = ReportCard::with(['student', 'configuration'])->findOrFail($id);

            // Verify hash
            $expectedHash = hash('sha256', $reportCard->id . $reportCard->student_id . config('app.key'));

            if ($hash !== $expectedHash) {
                return view('content.dashboard.academic.report_cards.verify', [
                    'valid' => false,
                    'message' => 'Invalid verification code. This report card may be forged.'
                ]);
            }

            return view('content.dashboard.academic.report_cards.verify', [
                'valid' => true,
                'reportCard' => $reportCard,
                'student' => $reportCard->student,
                'configuration' => $reportCard->configuration,
            ]);

        } catch (\Exception $e) {
            return view('content.dashboard.academic.report_cards.verify', [
                'valid' => false,
                'message' => 'Report card not found or verification failed.'
            ]);
        }
    }

    /**
     * Get school logo path
     */
    private function getSchoolLogoPath($schoolInfo)
    {
        if (isset($schoolInfo['logo']) && !empty($schoolInfo['logo'])) {
            $logoPath = storage_path('app/public/schools/logos/' . $schoolInfo['logo']);
            if (file_exists($logoPath)) {
                return $logoPath;
            }
        }

        $defaultLogo = public_path('assets/img/default-school-logo.png');
        if (file_exists($defaultLogo)) {
            return $defaultLogo;
        }

        return null;
    }

    /**
     * Enhanced Bulk Export with QR codes
     */
    public function bulkExportPDFEnhanced($configId)
    {
        $this->switchToTenant();

        $configuration = ReportCardConfiguration::with(['class', 'examConfigs.exam'])
            ->findOrFail($configId);

        $reportCards = ReportCard::where('report_card_config_id', $configId)
            ->where('status', '!=', 'draft')
            ->with(['student', 'configuration.class', 'configuration.examConfigs.exam', 'template'])
            ->get();

        if ($reportCards->isEmpty()) {
            return back()->with('error', 'No finalized report cards to export.');
        }

        $schoolInfo = $this->getSchoolInfo();
        $schoolInfo['logo_path'] = $this->getSchoolLogoPath($schoolInfo);
        $parentLetter = ParentLetter::where('report_card_config_id', $configId)->first();

        $tempDir = storage_path('app/temp/report-cards-' . time());
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0777, true);
        }

        try {
            $generatedCount = 0;

            foreach ($reportCards as $reportCard) {
                try {
                    $comments = ReportCardComment::getCommentsForStudent(
                        $reportCard->report_card_config_id,
                        $reportCard->student_id
                    );

                    $qrCode = $this->generateQRCode($reportCard);

                    $data = [
                        'reportCard' => $reportCard,
                        'student' => $reportCard->student,
                        'configuration' => $reportCard->configuration,
                        'comments' => $comments,
                        'schoolInfo' => $schoolInfo,
                        'parentLetter' => $parentLetter,
                        'qrCode' => $qrCode,
                    ];

                    $pdf = PDF::loadView('content.dashboard.academic.report_cards.pdf_enhanced', $data)
                        ->setPaper('a4', 'portrait')
                        ->setOptions([
                            'isHtml5ParserEnabled' => true,
                            'isRemoteEnabled' => true,
                            'defaultFont' => 'DejaVu Sans',
                        ]);

                    $filename = $this->sanitizeFilename(
                        $reportCard->student->registration_no . '-' .
                        $reportCard->student->first_name . '-' .
                        $reportCard->student->last_name . '-' .
                        $configuration->term
                    ) . '.pdf';

                    $pdf->save($tempDir . '/' . $filename);
                    $generatedCount++;

                } catch (\Exception $e) {
                    Log::error("Failed to generate PDF for student {$reportCard->student_id}: " . $e->getMessage());
                    continue;
                }
            }

            if ($generatedCount === 0) {
                throw new \Exception('No PDFs were generated successfully.');
            }

            $zipFilename = 'report-cards-' . $this->sanitizeFilename($configuration->class->name) . '-' .
                          $configuration->term . '-' . date('Y-m-d-His') . '.zip';
            $zipPath = storage_path('app/temp/' . $zipFilename);

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                $files = glob($tempDir . '/*.pdf');
                foreach ($files as $file) {
                    $zip->addFile($file, basename($file));
                }
                $zip->close();

                foreach ($files as $file) {
                    unlink($file);
                }
                rmdir($tempDir);

                return response()->download($zipPath, $zipFilename)->deleteFileAfterSend(true);
            } else {
                throw new \Exception('Failed to create ZIP file');
            }

        } catch (\Exception $e) {
            if (file_exists($tempDir)) {
                $files = glob($tempDir . '/*.pdf');
                if ($files) {
                    array_map('unlink', $files);
                }
                @rmdir($tempDir);
            }

            Log::error('Failed to bulk export report cards: ' . $e->getMessage());
            return back()->with('error', 'Failed to export report cards: ' . $e->getMessage());
        }
    }

    /**
     * Send report cards via SMS to students/parents for a specific class
     */
    public function sendReportSMS(Request $request, $configId)
    {
        $this->switchToTenant();

        $request->validate([
            'class_id' => 'required|exists:classes,id',
        ]);

        $configuration = ReportCardConfiguration::findOrFail($configId);
        $classId = $request->input('class_id');

        // Get all students in the class
        $students = DB::connection('tenant')
            ->table('students')
            ->join('schoolUsers', 'students.user_id', '=', 'schoolUsers.id')
            ->where('students.class_id', $classId)
            ->select('students.*', 'schoolUsers.username', 'schoolUsers.registration_no', 'students.parent_phone')
            ->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'No students found in this class.');
        }

        $messages = [];
        $successCount = 0;
        $failedCount = 0;

        foreach ($students as $student) {
            // Get report card for this student
            $reportCard = ReportCard::where('report_card_config_id', $configId)
                ->where('student_id', $student->id)
                ->first();

            if (!$reportCard) {
                $failedCount++;
                continue;
            }

            // Get exam marks for this student
            $examIds = $configuration->examConfigs->pluck('exam_id')->toArray();

            $marks = DB::connection('tenant')
                ->table('exams_marks')
                ->join('subjects', 'exams_marks.subject_id', '=', 'subjects.id')
                ->whereIn('exams_marks.exam_id', $examIds)
                ->where('exams_marks.student_id', $student->id)
                ->select('subjects.name as subject_name', 'exams_marks.marks', 'exams_marks.teacher_comment')
                ->get();

            // Get class position/rank
            $position = $reportCard->class_position ?? 'N/A';
            $average = $reportCard->average_marks ?? 0;

            // Get teacher's comment
            $comment = ReportCardComment::where('report_card_config_id', $configId)
                ->where('student_id', $student->id)
                ->where('stakeholder_type', 'class_teacher')
                ->first();
            $teacherComment = $comment ? $comment->comment : 'Keep up the good work!';

            // Format SMS message
            $smsText = $this->formatReportSMS($student, $marks, $average, $position, $teacherComment, $configuration);

            // Determine phone number (parent phone or student phone)
            $phone = $student->parent_phone ?? $student->phone_number;

            if ($phone) {
                $messages[] = [
                    'to' => $phone,
                    'text' => $smsText,
                ];
                $successCount++;
            } else {
                $failedCount++;
            }
        }

        // Send bulk SMS
        if (!empty($messages)) {
            $result = \App\Helpers\SmsHelper::sendNextSmsBulk($messages);

            if ($result['success']) {
                return back()->with('success', "Report cards sent via SMS to {$successCount} students. {$failedCount} failed (no phone number).");
            } else {
                return back()->with('error', 'Failed to send SMS: ' . ($result['error'] ?? 'Unknown error'));
            }
        }

        return back()->with('error', 'No valid phone numbers found for students.');
    }

    /**
     * Format report data into SMS text
     */
    private function formatReportSMS($student, $marks, $average, $position, $comment, $configuration)
    {
        $schoolName = session('school_name', 'School');

        // Start with header
        $sms = "📊 {$schoolName}\n";
        $sms .= "ACADEMIC REPORT\n\n";

        // Student details
        $sms .= "Name: {$student->username}\n";
        $sms .= "Reg: {$student->registration_no}\n\n";

        // Subjects and marks (limit to top subjects to keep SMS short)
        $sms .= "MARKS:\n";
        $subjectCount = 0;
        $totalMarks = 0;

        foreach ($marks as $mark) {
            if ($subjectCount >= 6) break; // Limit to 6 subjects for SMS length
            $sms .= "• {$mark->subject_name}: {$mark->marks}\n";
            $totalMarks += $mark->marks;
            $subjectCount++;
        }

        if ($marks->count() > 6) {
            $sms .= "• ...+" . ($marks->count() - 6) . " more\n";
        }

        // Average and position
        $sms .= "\nAverage: " . number_format($average, 1) . "%\n";
        $sms .= "Position: {$position}\n\n";

        // Comment (truncate if too long)
        $shortComment = strlen($comment) > 80 ? substr($comment, 0, 77) . '...' : $comment;
        $sms .= "Comment: {$shortComment}\n\n";

        // Footer
        $sms .= "Keep working hard! 📚";

        return $sms;
    }
}
