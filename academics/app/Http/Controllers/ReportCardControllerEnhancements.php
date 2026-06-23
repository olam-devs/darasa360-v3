<?php

/**
 * REPORT CARD CONTROLLER ENHANCEMENTS
 *
 * This file contains enhanced methods to be added to the existing ReportCardController.
 * Copy these methods into app/Http/Controllers/ReportCardController.php
 *
 * DEPENDENCIES REQUIRED:
 * - composer require simplesoftwareio/simple-qrcode
 * - composer require intervention/image (optional, for logo/photo processing)
 *
 * Date: January 10, 2026
 */

namespace App\Http\Controllers;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Enhanced PDF Export with QR Code and Modern Design
 *
 * Add this method to ReportCardController.php
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

    // Get parent letter if exists
    $parentLetter = ParentLetter::where('report_card_config_id', $reportCard->report_card_config_id)->first();

    // Generate QR Code for verification
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

    // Use enhanced PDF template
    $pdf = PDF::loadView('content.dashboard.academic.report_cards.pdf_enhanced', $data)
        ->setPaper('a4', 'portrait')
        ->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
            'enable_php' => true,
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
 *
 * @param ReportCard $reportCard
 * @return string SVG QR code
 */
private function generateQRCode($reportCard)
{
    try {
        // Create verification URL with hash
        $hash = hash('sha256', $reportCard->id . $reportCard->student_id . config('app.key'));

        $verificationUrl = route('report-cards.verify', [
            'id' => $reportCard->id,
            'hash' => $hash
        ]);

        // Generate QR code as SVG (better quality in PDF)
        $qrCode = QrCode::size(100)
            ->margin(0)
            ->style('round')
            ->eye('circle')
            ->format('svg')
            ->generate($verificationUrl);

        return $qrCode;
    } catch (\Exception $e) {
        \Log::error('QR Code generation failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * Public verification route for QR code
 *
 * @param Request $request
 * @param int $id
 * @return View
 */
public function verify(Request $request, $id)
{
    try {
        $hash = $request->query('hash');

        // Switch to appropriate tenant database
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
 *
 * @param array $schoolInfo
 * @return string|null
 */
private function getSchoolLogoPath($schoolInfo)
{
    if (isset($schoolInfo['logo']) && !empty($schoolInfo['logo'])) {
        $logoPath = storage_path('app/public/schools/logos/' . $schoolInfo['logo']);
        if (file_exists($logoPath)) {
            return $logoPath;
        }
    }

    // Return default logo if exists
    $defaultLogo = public_path('assets/img/default-school-logo.png');
    if (file_exists($defaultLogo)) {
        return $defaultLogo;
    }

    return null;
}

/**
 * Enhanced Bulk Export with individual PDFs and QR codes
 *
 * Replace the existing bulkExportPDF method with this enhanced version
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

                // Generate QR code
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

                // Use enhanced template
                $pdf = PDF::loadView('content.dashboard.academic.report_cards.pdf_enhanced', $data)
                    ->setPaper('a4', 'portrait')
                    ->setOptions([
                        'isHtml5ParserEnabled' => true,
                        'isRemoteEnabled' => true,
                        'defaultFont' => 'DejaVu Sans',
                    ]);

                // Generate safe filename
                $filename = $this->sanitizeFilename(
                    $reportCard->student->registration_no . '-' .
                    $reportCard->student->first_name . '-' .
                    $reportCard->student->last_name . '-' .
                    $configuration->term
                ) . '.pdf';

                $pdf->save($tempDir . '/' . $filename);
                $generatedCount++;

            } catch (\Exception $e) {
                \Log::error("Failed to generate PDF for student {$reportCard->student_id}: " . $e->getMessage());
                continue;
            }
        }

        if ($generatedCount === 0) {
            throw new \Exception('No PDFs were generated successfully.');
        }

        // Create ZIP file
        $zipFilename = 'report-cards-' . $this->sanitizeFilename($configuration->class->name) . '-' .
                      $configuration->term . '-' . date('Y-m-d-His') . '.zip';
        $zipPath = storage_path('app/temp/' . $zipFilename);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === true) {
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

        \Log::error('Failed to bulk export report cards: ' . $e->getMessage());
        return back()->with('error', 'Failed to export report cards: ' . $e->getMessage());
    }
}

/**
 * Generate performance chart as base64 image
 * Optional: Use this to add visual charts to PDFs
 *
 * @param ReportCard $reportCard
 * @return string|null
 */
private function generatePerformanceChart($reportCard)
{
    try {
        // This is a placeholder for chart generation
        // You can implement using ChartJS (with Puppeteer) or PHP charting libraries

        // Example using simple SVG generation:
        $subjectPerformance = $reportCard->subject_performance ?? [];

        if (empty($subjectPerformance)) {
            return null;
        }

        // Create simple bar chart SVG
        $width = 400;
        $height = 200;
        $barWidth = 40;
        $spacing = 10;
        $maxScore = 100;

        $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg">';

        $x = 20;
        foreach ($subjectPerformance as $index => $subject) {
            $score = $subject['total_weighted_marks'] ?? 0;
            $barHeight = ($score / $maxScore) * ($height - 40);
            $y = $height - $barHeight - 20;

            // Bar
            $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $barWidth . '" height="' . $barHeight . '" fill="#667eea" />';

            // Score label
            $svg .= '<text x="' . ($x + $barWidth/2) . '" y="' . ($y - 5) . '" text-anchor="middle" font-size="12">' . round($score, 1) . '%</text>';

            $x += $barWidth + $spacing;
        }

        $svg .= '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);

    } catch (\Exception $e) {
        \Log::error('Chart generation failed: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get report card statistics for dashboard
 *
 * @param int $configId
 * @return array
 */
public function getStatistics($configId)
{
    $this->switchToTenant();

    $configuration = ReportCardConfiguration::findOrFail($configId);

    $reportCards = ReportCard::where('report_card_config_id', $configId)->get();

    $statistics = [
        'total_students' => $reportCards->count(),
        'average_class_performance' => $reportCards->avg('average_marks'),
        'highest_score' => $reportCards->max('average_marks'),
        'lowest_score' => $reportCards->min('average_marks'),
        'grade_distribution' => $reportCards->groupBy('overall_grade')->map->count(),
        'pdf_generated_count' => $reportCards->where('pdf_generated', true)->count(),
        'published_count' => $reportCards->where('status', 'published')->count(),
    ];

    return response()->json($statistics);
}

/**
 * Preview report card before generation
 *
 * @param int $configId
 * @param int $studentId
 * @return View
 */
public function preview($configId, $studentId)
{
    $this->switchToTenant();

    $configuration = ReportCardConfiguration::with(['class', 'examConfigs.exam'])
        ->findOrFail($configId);

    $student = Student::findOrFail($studentId);

    // Generate preview data (don't save to database)
    $reportCard = $this->generatePreviewReportCard($configuration, $student);

    $comments = ReportCardComment::getCommentsForStudent($configId, $studentId);
    $schoolInfo = $this->getSchoolInfo();
    $parentLetter = ParentLetter::where('report_card_config_id', $configId)->first();

    return view('content.dashboard.academic.report_cards.preview', compact(
        'reportCard',
        'student',
        'configuration',
        'comments',
        'schoolInfo',
        'parentLetter'
    ));
}

/**
 * Generate preview report card (temporary, not saved)
 *
 * @param ReportCardConfiguration $configuration
 * @param Student $student
 * @return array
 */
private function generatePreviewReportCard($configuration, $student)
{
    // Get all exam marks for this student from configured exams
    $examIds = $configuration->examConfigs->pluck('exam_id');
    $examConfigs = $configuration->examConfigs->keyBy('exam_id');

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

    return (object) [
        'total_marks' => round($totalWeightedMarks, 2),
        'average_marks' => round($averageMarks, 2),
        'overall_grade' => $overallGrade,
        'subject_performance' => $subjectPerformance,
        'class_position' => 'N/A (Preview)',
        'total_students_in_class' => Student::where('class_id', $configuration->class_id)->count(),
    ];
}

/**
 * ROUTES TO ADD TO routes/owner_routes.php or routes/academic_routes.php:
 *
 * // Enhanced PDF exports
 * Route::get('/report-cards/{id}/export-enhanced', [ReportCardController::class, 'exportPDFEnhanced'])->name('report-cards.export-enhanced');
 * Route::get('/report-cards/{id}/bulk-export-enhanced', [ReportCardController::class, 'bulkExportPDFEnhanced'])->name('report-cards.bulk-export-enhanced');
 *
 * // Preview
 * Route::get('/report-cards/{configId}/preview/{studentId}', [ReportCardController::class, 'preview'])->name('report-cards.preview');
 *
 * // Statistics
 * Route::get('/report-cards/{id}/statistics', [ReportCardController::class, 'getStatistics'])->name('report-cards.statistics');
 *
 * // Public verification (no auth required)
 * Route::get('/verify-report/{id}', [ReportCardController::class, 'verify'])->name('report-cards.verify');
 */
