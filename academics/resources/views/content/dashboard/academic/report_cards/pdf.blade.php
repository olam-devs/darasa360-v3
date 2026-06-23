<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Card - {{ $student->first_name }} {{ $student->last_name }}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            font-style: normal;
            font-weight: normal;
            src: url({{ storage_path('fonts/dejavu-sans/DejaVuSans.ttf') }}) format('truetype');
        }

        * {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-size: 11px;
            line-height: 1.6;
            color: #1f2937;
            padding: 15px;
            background: #ffffff;
        }

        /* Modern Color Palette */
        :root {
            --primary-blue: #2563eb;
            --primary-indigo: #4f46e5;
            --primary-purple: #7c3aed;
            --grade-a: #10b981;
            --grade-b: #3b82f6;
            --grade-c: #f59e0b;
            --grade-d: #f97316;
            --grade-e: #ef4444;
            --grade-f: #991b1b;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-700: #374151;
            --gray-900: #111827;
        }

        /* Header Section */
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding: 20px 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            position: relative;
        }

        .header-top {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .logo-container {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
        }

        .logo-container img {
            height: 70px;
            width: auto;
            max-width: 80px;
        }

        .header-content {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        .qr-container {
            display: table-cell;
            width: 80px;
            vertical-align: middle;
            text-align: right;
        }

        .qr-container img {
            height: 70px;
            width: 70px;
            background: white;
            padding: 3px;
            border-radius: 5px;
        }

        .header h1 {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 5px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.2);
        }

        .header p {
            font-size: 10px;
            margin: 2px 0;
            opacity: 0.95;
        }

        .header h2 {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 2px solid rgba(255,255,255,0.3);
        }

        .header .term-info {
            font-size: 12px;
            margin-top: 5px;
            font-weight: 600;
        }

        /* Watermark */
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(0, 0, 0, 0.03);
            z-index: -1;
            font-weight: bold;
            letter-spacing: 10px;
        }

        /* Student Information Card */
        .student-card {
            background: linear-gradient(135deg, #667eea15 0%, #764ba215 100%);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 2px solid #e5e7eb;
        }

        .student-card-inner {
            display: table;
            width: 100%;
        }

        .student-photo {
            display: table-cell;
            width: 90px;
            vertical-align: top;
            padding-right: 15px;
        }

        .student-photo img {
            width: 80px;
            height: 100px;
            object-fit: cover;
            border-radius: 8px;
            border: 3px solid white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .student-photo-placeholder {
            width: 80px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
            border: 3px solid white;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .student-info-content {
            display: table-cell;
            vertical-align: top;
        }

        .student-info-grid {
            display: table;
            width: 100%;
        }

        .info-row {
            display: table-row;
        }

        .info-cell {
            display: table-cell;
            padding: 5px 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .info-label {
            font-weight: bold;
            color: #6b7280;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            color: #111827;
            font-weight: 600;
            font-size: 11px;
        }

        /* Performance Summary Cards */
        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-spacing: 10px 0;
        }

        .summary-card {
            display: table-cell;
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #e5e7eb;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .summary-card.grade-card {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
        }

        .summary-card.average-card {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
            color: white;
            border: none;
        }

        .summary-card.position-card {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            border: none;
        }

        .summary-card-value {
            font-size: 32px;
            font-weight: bold;
            margin: 8px 0;
            line-height: 1;
        }

        .summary-card-label {
            font-size: 10px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Section Headers */
        .section-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 15px;
            font-size: 13px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            border-radius: 6px;
            display: flex;
            align-items: center;
        }

        .section-icon {
            margin-right: 8px;
            font-size: 16px;
        }

        /* Performance Table */
        .performance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        .performance-table thead {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
        }

        .performance-table th {
            padding: 10px 8px;
            text-align: center;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .performance-table td {
            padding: 10px 8px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
            font-size: 11px;
        }

        .performance-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .performance-table tbody tr:hover {
            background-color: #f3f4f6;
        }

        .performance-table td.subject-name {
            text-align: left;
            font-weight: 600;
            color: #111827;
        }

        /* Grade Badges */
        .grade-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-weight: bold;
            color: white;
            font-size: 11px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .grade-A { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .grade-B { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .grade-C { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .grade-D { background: linear-gradient(135deg, #f97316 0%, #ea580c 100%); }
        .grade-E { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .grade-F { background: linear-gradient(135deg, #991b1b 0%, #7f1d1d 100%); }

        /* Progress Bars */
        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: #e5e7eb;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 5px;
        }

        .progress-bar {
            height: 100%;
            border-radius: 10px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            transition: width 0.3s ease;
        }

        .subject-row-with-bar td {
            padding-top: 8px;
            padding-bottom: 12px;
        }

        /* Comments Section */
        .comments-grid {
            display: table;
            width: 100%;
            border-spacing: 0 10px;
        }

        .comment-box {
            border-left: 4px solid #667eea;
            background: #f9fafb;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 0 6px 6px 0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }

        .comment-header {
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 6px;
            font-size: 11px;
            display: flex;
            align-items: center;
        }

        .comment-icon {
            margin-right: 6px;
            color: #667eea;
        }

        .comment-text {
            color: #4b5563;
            line-height: 1.7;
            font-size: 10px;
            text-align: justify;
        }

        /* Grading Scale */
        .grading-scale {
            background: #f9fafb;
            padding: 12px;
            border-radius: 8px;
            margin-top: 20px;
            border: 1px solid #e5e7eb;
        }

        .grading-scale-title {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 8px;
            color: #111827;
        }

        .grading-scale table {
            width: 100%;
            border-collapse: collapse;
        }

        .grading-scale th,
        .grading-scale td {
            padding: 6px;
            border: 1px solid #d1d5db;
            text-align: center;
            font-size: 10px;
        }

        .grading-scale th {
            background: #e5e7eb;
            font-weight: bold;
            color: #374151;
        }

        .grading-scale .grade-cell {
            font-weight: bold;
        }

        .grading-scale .grade-cell-A { color: #059669; }
        .grading-scale .grade-cell-B { color: #2563eb; }
        .grading-scale .grade-cell-C { color: #d97706; }
        .grading-scale .grade-cell-D { color: #ea580c; }
        .grading-scale .grade-cell-E { color: #dc2626; }
        .grading-scale .grade-cell-F { color: #7f1d1d; }

        /* Circular Progress */
        .circular-progress {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: conic-gradient(
                #667eea 0% var(--progress),
                #e5e7eb var(--progress) 100%
            );
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 10px auto;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .circular-progress-inner {
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }

        /* Parent Letter Styling */
        .parent-letter {
            margin-top: 30px;
            padding: 20px;
            background: white;
            border-radius: 8px;
        }

        .parent-letter-section {
            margin-bottom: 20px;
        }

        .parent-letter-heading {
            color: #1f2937;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .parent-letter-content {
            text-align: justify;
            line-height: 1.8;
            color: #4b5563;
        }

        .highlight-box {
            padding: 12px;
            border-radius: 6px;
            margin: 15px 0;
            border-left: 4px solid;
        }

        .highlight-box.fee-notice {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }

        .highlight-box.safari-info {
            background: #dbeafe;
            border-left-color: #3b82f6;
        }

        .highlight-box.announcement {
            background: #d1fae5;
            border-left-color: #10b981;
        }

        /* Signatures */
        .signature-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
        }

        .signature-grid {
            display: table;
            width: 100%;
            border-spacing: 20px 0;
        }

        .signature-box {
            display: table-cell;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            width: 100%;
            height: 1px;
            background: #1f2937;
            margin-bottom: 5px;
        }

        .signature-label {
            font-size: 10px;
            font-weight: bold;
            color: #6b7280;
        }

        .signature-date {
            font-size: 9px;
            color: #9ca3af;
            margin-top: 3px;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            color: #6b7280;
            font-size: 9px;
        }

        .footer-official {
            font-weight: bold;
            color: #374151;
            margin-bottom: 5px;
        }

        .footer-verification {
            margin-top: 5px;
            color: #10b981;
            font-weight: 600;
        }

        /* Page Break */
        .page-break {
            page-break-after: always;
        }

        /* Verification Badge */
        .verification-badge {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 8px;
            font-weight: bold;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <!-- Watermark -->
    <div class="watermark">OFFICIAL</div>

    <!-- Header -->
    <div class="header">
        <div class="header-top">
            <div class="logo-container">
                @if(isset($schoolInfo['logo_path']) && file_exists($schoolInfo['logo_path']))
                    <img src="{{ $schoolInfo['logo_path'] }}" alt="School Logo">
                @endif
            </div>
            <div class="header-content">
                <h1>{{ $schoolInfo['name'] ?? 'School Name' }}</h1>
                <p>{{ $schoolInfo['report_address'] ?? $schoolInfo['address'] ?? '' }}</p>
                <p>Tel: {{ $schoolInfo['report_phone'] ?? $schoolInfo['phone'] ?? '' }} | Email: {{ $schoolInfo['report_email'] ?? $schoolInfo['email'] ?? '' }}</p>
                <h2>STUDENT ACADEMIC REPORT CARD</h2>
                <p class="term-info">{{ $configuration->term }} • {{ $configuration->academic_year }}</p>
            </div>
            <div class="qr-container">
                @if(isset($qrCode))
                    {!! $qrCode !!}
                @endif
            </div>
        </div>
    </div>

    <!-- Student Information Card -->
    <div class="student-card">
        <div class="student-card-inner">
            <div class="student-photo">
                @if($student->photo && file_exists(storage_path('app/public/students/' . $student->photo)))
                    <img src="{{ storage_path('app/public/students/' . $student->photo) }}" alt="{{ $student->first_name }}">
                @else
                    <div class="student-photo-placeholder">
                        {{ strtoupper(substr($student->first_name, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div class="student-info-content">
                <div class="student-info-grid">
                    <div class="info-row">
                        <div class="info-cell" style="width: 50%;">
                            <div class="info-label">Student Name</div>
                            <div class="info-value">{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</div>
                        </div>
                        <div class="info-cell" style="width: 50%;">
                            <div class="info-label">Registration Number</div>
                            <div class="info-value">{{ $student->registration_no }}</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-cell">
                            <div class="info-label">Class</div>
                            <div class="info-value">{{ $configuration->class->name }}</div>
                        </div>
                        <div class="info-cell">
                            <div class="info-label">Date of Issue</div>
                            <div class="info-value">{{ now()->format('d F Y') }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Summary Cards -->
    <div class="summary-cards">
        <div class="summary-card grade-card">
            <div class="summary-card-label">Overall Grade</div>
            <div class="summary-card-value">{{ $reportCard->overall_grade }}</div>
        </div>
        <div class="summary-card average-card">
            <div class="summary-card-label">Average Marks</div>
            <div class="summary-card-value">{{ number_format($reportCard->average_marks, 1) }}%</div>
        </div>
        <div class="summary-card position-card">
            <div class="summary-card-label">Position in Class</div>
            <div class="summary-card-value">{{ $reportCard->class_position }}/{{ $reportCard->total_students_in_class }}</div>
        </div>
    </div>

    <!-- Academic Performance Section -->
    <div class="section-header">
        <span class="section-icon">📚</span>
        SUBJECT PERFORMANCE
    </div>

    <table class="performance-table">
        <thead>
            <tr>
                <th style="width: 25%; text-align: left;">Subject</th>
                @foreach($configuration->examConfigs as $examConfig)
                    <th style="width: {{ 60 / count($configuration->examConfigs) }}%;">
                        {{ $examConfig->exam->name }}<br>
                        <span style="font-size: 9px;">({{ $examConfig->percentage_weight }}%)</span>
                    </th>
                @endforeach
                <th style="width: 10%;">Total</th>
                <th style="width: 10%;">Grade</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($reportCard->subject_performance) && is_array($reportCard->subject_performance))
                @foreach($reportCard->subject_performance as $subject)
                    <tr class="subject-row-with-bar">
                        <td class="subject-name">
                            {{ $subject['subject_name'] }}
                            <div class="progress-bar-container">
                                <div class="progress-bar" style="width: {{ $subject['total_weighted_marks'] }}%;"></div>
                            </div>
                        </td>
                        @foreach($subject['exams'] as $exam)
                            <td><strong>{{ $exam['marks'] }}</strong></td>
                        @endforeach
                        <td><strong style="color: #667eea;">{{ number_format($subject['total_weighted_marks'], 1) }}</strong></td>
                        <td>
                            <span class="grade-badge grade-{{ $subject['grade'] }}">{{ $subject['grade'] }}</span>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <!-- Comments Section -->
    @if($comments->isNotEmpty())
        <div class="section-header">
            <span class="section-icon">💬</span>
            TEACHER COMMENTS & FEEDBACK
        </div>

        <div class="comments-grid">
            @foreach($comments as $stakeholderType => $stakeholderComments)
                @foreach($stakeholderComments as $comment)
                    <div class="comment-box">
                        <div class="comment-header">
                            <span class="comment-icon">✍️</span>
                            {{ ucwords(str_replace('_', ' ', $stakeholderType)) }}
                            @if($comment->subject)
                                - {{ $comment->subject->name }}
                            @endif
                        </div>
                        <div class="comment-text">{{ $comment->comment }}</div>
                    </div>
                @endforeach
            @endforeach
        </div>
    @endif

    <!-- Grading Scale -->
    <div class="grading-scale">
        <div class="grading-scale-title">📊 GRADING SCALE & LEGEND</div>
        <table>
            <tr>
                <th>Grade</th>
                <th class="grade-cell-A">A</th>
                <th class="grade-cell-B">B</th>
                <th class="grade-cell-C">C</th>
                <th class="grade-cell-D">D</th>
                <th class="grade-cell-E">E</th>
                <th class="grade-cell-F">F</th>
            </tr>
            <tr>
                <td><strong>Marks (%)</strong></td>
                <td class="grade-cell grade-cell-A">80-100</td>
                <td class="grade-cell grade-cell-B">70-79</td>
                <td class="grade-cell grade-cell-C">60-69</td>
                <td class="grade-cell grade-cell-D">50-59</td>
                <td class="grade-cell grade-cell-E">40-49</td>
                <td class="grade-cell grade-cell-F">0-39</td>
            </tr>
            <tr>
                <td><strong>Meaning</strong></td>
                <td style="color: #059669;">Excellent</td>
                <td style="color: #2563eb;">Very Good</td>
                <td style="color: #d97706;">Good</td>
                <td style="color: #ea580c;">Fair</td>
                <td style="color: #dc2626;">Pass</td>
                <td style="color: #7f1d1d;">Fail</td>
            </tr>
        </table>
    </div>

    <!-- Parent Letter (if exists) -->
    @if(isset($parentLetter) && $parentLetter)
        <div class="page-break"></div>

        <!-- Header on new page -->
        <div class="header">
            <div class="header-top">
                <div class="logo-container">
                    @if(isset($schoolInfo['logo_path']) && file_exists($schoolInfo['logo_path']))
                        <img src="{{ $schoolInfo['logo_path'] }}" alt="School Logo">
                    @endif
                </div>
                <div class="header-content">
                    <h1>{{ $schoolInfo['name'] ?? 'School Name' }}</h1>
                    <p>{{ $schoolInfo['report_address'] ?? $schoolInfo['address'] ?? '' }}</p>
                    <p>Tel: {{ $schoolInfo['report_phone'] ?? $schoolInfo['phone'] ?? '' }} | Email: {{ $schoolInfo['report_email'] ?? $schoolInfo['email'] ?? '' }}</p>
                </div>
                <div class="qr-container"></div>
            </div>
        </div>

        <div class="section-header">
            <span class="section-icon">✉️</span>
            {{ strtoupper($parentLetter->letter_title) }}
        </div>

        <div class="parent-letter">
            <p style="font-weight: bold; margin-bottom: 15px;">Dear Parents/Guardians,</p>

            @if($parentLetter->closing_semester_message)
                <div class="parent-letter-section">
                    <div class="parent-letter-heading">🎓 Closing Semester Reflection</div>
                    <div class="parent-letter-content">{{ $parentLetter->closing_semester_message }}</div>
                </div>
            @endif

            @if($parentLetter->upcoming_semester_message)
                <div class="parent-letter-section">
                    <div class="parent-letter-heading">🚀 Upcoming Semester Plans</div>
                    <div class="parent-letter-content">{{ $parentLetter->upcoming_semester_message }}</div>
                </div>
            @endif

            @if($parentLetter->fee_payment_notice)
                <div class="highlight-box fee-notice">
                    <div style="font-weight: bold; margin-bottom: 5px;">💰 Fee Payment Information</div>
                    <div style="font-size: 10px; line-height: 1.7;">{{ $parentLetter->fee_payment_notice }}</div>
                </div>
            @endif

            @if($parentLetter->safari_trips)
                <div class="highlight-box safari-info">
                    <div style="font-weight: bold; margin-bottom: 5px;">🏕️ Safari Trips & Excursions</div>
                    <div style="font-size: 10px; line-height: 1.7;">{{ $parentLetter->safari_trips }}</div>
                </div>
            @endif

            @if($parentLetter->special_announcements)
                <div class="highlight-box announcement">
                    <div style="font-weight: bold; margin-bottom: 5px;">📢 Special Announcements</div>
                    <div style="font-size: 10px; line-height: 1.7;">{{ $parentLetter->special_announcements }}</div>
                </div>
            @endif

            @if($parentLetter->other_information)
                <div class="parent-letter-section">
                    <div class="parent-letter-heading">ℹ️ Additional Information</div>
                    <div class="parent-letter-content">{{ $parentLetter->other_information }}</div>
                </div>
            @endif

            <div style="margin-top: 30px;">
                <p>Thank you for your continued support and partnership in your child's education.</p>
                <br>
                <p style="font-weight: bold;">{{ $parentLetter->signature_name }}</p>
                <p style="font-style: italic;">{{ $parentLetter->signature_title }}</p>
                <p style="color: #6b7280; font-size: 10px;">{{ now()->format('d F Y') }}</p>
            </div>
        </div>
    @endif

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-grid">
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Class Teacher</div>
                <div class="signature-date">Date: _________</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Academic Director</div>
                <div class="signature-date">Date: _________</div>
            </div>
            <div class="signature-box">
                <div class="signature-line"></div>
                <div class="signature-label">Parent/Guardian</div>
                <div class="signature-date">Date: _________</div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-official">
            ✓ OFFICIAL DOCUMENT - {{ $schoolInfo['name'] ?? 'School Name' }}
        </div>
        <p>Report Card issued on {{ now()->format('d F Y') }}</p>
        @if(isset($qrCode))
            <div class="footer-verification">
                <span class="verification-badge">✓ VERIFIED</span> Scan QR code to verify authenticity
            </div>
        @endif
        <p style="margin-top: 10px;">© {{ date('Y') }} {{ $schoolInfo['name'] ?? 'School' }}. All rights reserved.</p>
    </div>
</body>
</html>
