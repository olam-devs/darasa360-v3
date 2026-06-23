<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Card - {{ $student->first_name }} {{ $student->last_name }}</title>
    <style>
        @page {
            margin: 10mm;
            size: A4 portrait;
        }

        * {
            font-family: 'DejaVu Sans', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-size: 10px;
            line-height: 1.5;
            color: #1a202c;
            background: #ffffff;
        }

        /* Ultra Modern Header with Geometric Shapes */
        .header {
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            padding: 25px 20px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50px;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -30px;
            width: 150px;
            height: 150px;
            background: rgba(255, 255, 255, 0.08);
            border-radius: 50%;
        }

        .header-content {
            position: relative;
            z-index: 2;
            text-align: center;
            color: white;
        }

        .school-logo {
            width: 60px;
            height: 60px;
            margin: 0 auto 10px;
            background: white;
            border-radius: 12px;
            padding: 5px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .school-name {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 5px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .school-motto {
            font-size: 9px;
            opacity: 0.95;
            font-style: italic;
            margin-bottom: 15px;
        }

        .report-title {
            font-size: 16px;
            font-weight: 700;
            background: rgba(255,255,255,0.2);
            padding: 8px 20px;
            border-radius: 20px;
            display: inline-block;
            margin-top: 10px;
        }

        .term-badge {
            display: inline-block;
            background: rgba(255,255,255,0.95);
            color: #667eea;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 9px;
            font-weight: 700;
            margin-top: 8px;
        }

        /* Student Info Section - Modern Card Design */
        .student-section {
            background: linear-gradient(135deg, #f6f8fb 0%, #e9ecef 100%);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
            border: 2px solid #e2e8f0;
            position: relative;
        }

        .student-header {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .student-photo-wrap {
            display: table-cell;
            width: 100px;
            vertical-align: top;
        }

        .student-photo {
            width: 85px;
            height: 105px;
            border-radius: 12px;
            border: 4px solid white;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            object-fit: cover;
        }

        .student-photo-placeholder {
            width: 85px;
            height: 105px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            color: white;
            font-weight: 800;
            border: 4px solid white;
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }

        .student-details {
            display: table-cell;
            vertical-align: top;
            padding-left: 15px;
        }

        .student-name {
            font-size: 16px;
            font-weight: 800;
            color: #2d3748;
            margin-bottom: 8px;
            letter-spacing: 0.3px;
        }

        .info-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
        }

        .info-row {
            display: table-row;
        }

        .info-label {
            display: table-cell;
            padding: 4px 8px;
            font-size: 8px;
            font-weight: 700;
            color: #718096;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 35%;
        }

        .info-value {
            display: table-cell;
            padding: 4px 8px;
            font-size: 10px;
            font-weight: 600;
            color: #2d3748;
        }

        /* Performance Summary - Modern Cards */
        .performance-summary {
            display: table;
            width: 100%;
            margin-bottom: 15px;
            border-spacing: 8px 0;
        }

        .stat-card {
            display: table-cell;
            padding: 15px;
            border-radius: 12px;
            text-align: center;
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .stat-card.grade {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .stat-card.average {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .stat-card.position {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .stat-label {
            font-size: 8px;
            opacity: 0.9;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 800;
            line-height: 1;
        }

        .stat-suffix {
            font-size: 12px;
            opacity: 0.95;
            margin-top: 4px;
        }

        /* Section Headers */
        .section-header {
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 15px;
            border-radius: 8px;
            margin: 20px 0 10px 0;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        /* Modern Performance Table */
        .performance-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 15px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }

        .performance-table thead {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
            color: white;
        }

        .performance-table th {
            padding: 10px 8px;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: center;
        }

        .performance-table th:first-child {
            text-align: left;
        }

        .performance-table td {
            padding: 10px 8px;
            font-size: 9px;
            text-align: center;
            border-bottom: 1px solid #e5e7eb;
        }

        .performance-table td:first-child {
            text-align: left;
            font-weight: 700;
            color: #2d3748;
        }

        .performance-table tbody tr:nth-child(even) {
            background: #f8fafc;
        }

        .performance-table tbody tr:hover {
            background: #f1f5f9;
        }

        /* Ultra Modern Grade Badges */
        .grade-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 9px;
            color: white;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
        }

        .grade-A { background: linear-gradient(135deg, #10b981, #059669); }
        .grade-B { background: linear-gradient(135deg, #3b82f6, #2563eb); }
        .grade-C { background: linear-gradient(135deg, #f59e0b, #d97706); }
        .grade-D { background: linear-gradient(135deg, #f97316, #ea580c); }
        .grade-E { background: linear-gradient(135deg, #ef4444, #dc2626); }
        .grade-F { background: linear-gradient(135deg, #991b1b, #7f1d1d); }

        /* Modern Comments Section */
        .comment-box {
            background: linear-gradient(135deg, #f8fafc 0%, #e8eaf0 100%);
            border-left: 4px solid #667eea;
            padding: 12px;
            margin-bottom: 10px;
            border-radius: 0 8px 8px 0;
            box-shadow: 0 2px 6px rgba(0,0,0,0.05);
        }

        .comment-header {
            font-size: 9px;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .comment-text {
            font-size: 9px;
            color: #4b5563;
            line-height: 1.6;
            text-align: justify;
        }

        /* Parent Letter - Premium Design */
        .parent-letter {
            background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
            border: 2px solid #f59e0b;
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
        }

        .letter-title {
            font-size: 12px;
            font-weight: 800;
            color: #92400e;
            text-align: center;
            margin-bottom: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .letter-section {
            margin-bottom: 12px;
        }

        .letter-heading {
            font-size: 9px;
            font-weight: 700;
            color: #78350f;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .letter-content {
            font-size: 9px;
            color: #451a03;
            line-height: 1.6;
            text-align: justify;
        }

        .highlight-box {
            padding: 10px;
            border-radius: 8px;
            margin: 10px 0;
            border-left: 4px solid;
        }

        .highlight-box.fee {
            background: #fef3c7;
            border-left-color: #f59e0b;
        }

        .highlight-box.trip {
            background: #dbeafe;
            border-left-color: #3b82f6;
        }

        .highlight-box.announcement {
            background: #d1fae5;
            border-left-color: #10b981;
        }

        /* Signature Section */
        .signatures {
            display: table;
            width: 100%;
            margin-top: 25px;
            border-top: 2px solid #e5e7eb;
            padding-top: 15px;
        }

        .signature-box {
            display: table-cell;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-line {
            width: 120px;
            height: 1px;
            background: #2d3748;
            margin: 0 auto 5px;
        }

        .signature-label {
            font-size: 8px;
            font-weight: 700;
            color: #4b5563;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 2px solid #e5e7eb;
            text-align: center;
            font-size: 8px;
            color: #6b7280;
        }

        .footer-official {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 5px;
        }

        .qr-code {
            width: 60px;
            height: 60px;
            margin: 10px auto;
        }

        /* Grading Scale */
        .grading-scale {
            background: #f9fafb;
            padding: 10px;
            border-radius: 8px;
            margin: 15px 0;
            border: 1px solid #e5e7eb;
        }

        .scale-title {
            font-size: 9px;
            font-weight: 700;
            margin-bottom: 8px;
            text-align: center;
            color: #374151;
        }

        .scale-table {
            width: 100%;
            border-collapse: collapse;
        }

        .scale-table td {
            padding: 5px;
            text-align: center;
            font-size: 8px;
            border: 1px solid #d1d5db;
        }

        .scale-table td:first-child {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <!-- Ultra Modern Header -->
    <div class="header">
        <div class="header-content">
            @if(isset($schoolInfo['logo']) && !empty($schoolInfo['logo']))
            <div class="school-logo">
                <img src="{{ public_path('storage/schools/logos/' . $schoolInfo['logo']) }}" alt="Logo" style="width: 100%; height: 100%; object-fit: contain;">
            </div>
            @endif

            <div class="school-name">{{ $schoolInfo['name'] ?? 'SCHOOL NAME' }}</div>
            <div class="school-motto">{{ $schoolInfo['motto'] ?? 'Excellence in Education' }}</div>

            <div class="report-title">ACADEMIC PERFORMANCE REPORT</div>
            <div class="term-badge">{{ $configuration->term }} • {{ $configuration->academic_year }}</div>
        </div>
    </div>

    <!-- Student Information Section -->
    <div class="student-section">
        <div class="student-header">
            <div class="student-photo-wrap">
                @if(isset($student->profile_picture) && !empty($student->profile_picture))
                <img src="{{ public_path('storage/students/' . $student->profile_picture) }}" class="student-photo" alt="Student Photo">
                @else
                <div class="student-photo-placeholder">
                    {{ strtoupper(substr($student->first_name, 0, 1)) }}
                </div>
                @endif
            </div>

            <div class="student-details">
                <div class="student-name">{{ strtoupper($student->first_name . ' ' . $student->last_name) }}</div>

                <div class="info-grid">
                    <div class="info-row">
                        <div class="info-label">Registration No:</div>
                        <div class="info-value">{{ $student->registration_no ?? 'N/A' }}</div>
                        <div class="info-label">Class:</div>
                        <div class="info-value">{{ $configuration->class->name ?? 'N/A' }}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Date of Birth:</div>
                        <div class="info-value">{{ $student->dob ? \Carbon\Carbon::parse($student->dob)->format('d M Y') : 'N/A' }}</div>
                        <div class="info-label">Gender:</div>
                        <div class="info-value">{{ $student->gender ?? 'N/A' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Performance Summary Cards -->
    <div class="performance-summary">
        <div class="stat-card grade">
            <div class="stat-label">Overall Grade</div>
            <div class="stat-value">{{ $reportCard->overall_grade }}</div>
        </div>
        <div class="stat-card average">
            <div class="stat-label">Average Score</div>
            <div class="stat-value">{{ number_format($reportCard->average_marks, 1) }}</div>
            <div class="stat-suffix">out of 100</div>
        </div>
        <div class="stat-card position">
            <div class="stat-label">Class Position</div>
            <div class="stat-value">{{ $reportCard->class_position ?? 'N/A' }}</div>
            <div class="stat-suffix">of {{ $reportCard->total_students_in_class ?? 'N/A' }}</div>
        </div>
    </div>

    <!-- Subject Performance Table -->
    <div class="section-header">📊 SUBJECT PERFORMANCE BREAKDOWN</div>

    <table class="performance-table">
        <thead>
            <tr>
                <th>Subject</th>
                @foreach($configuration->examConfigs as $examConfig)
                <th>{{ $examConfig->exam->name }}<br>({{ $examConfig->percentage_weight }}%)</th>
                @endforeach
                <th>Total</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            @if($reportCard->subject_performance)
                @foreach($reportCard->subject_performance as $subject)
                <tr>
                    <td>{{ $subject['subject_name'] ?? 'Unknown' }}</td>
                    @foreach($subject['exams'] ?? [] as $exam)
                    <td>{{ number_format($exam['marks'], 0) }}</td>
                    @endforeach
                    <td><strong>{{ number_format($subject['total_weighted_marks'] ?? 0, 1) }}</strong></td>
                    <td>
                        <span class="grade-badge grade-{{ $subject['grade'] ?? 'F' }}">
                            {{ $subject['grade'] ?? 'F' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <!-- Grading Scale -->
    <div class="grading-scale">
        <div class="scale-title">GRADING SCALE</div>
        <table class="scale-table">
            <tr>
                <td>A: 80-100%</td>
                <td>B: 70-79%</td>
                <td>C: 60-69%</td>
                <td>D: 50-59%</td>
                <td>E: 40-49%</td>
                <td>F: 0-39%</td>
            </tr>
        </table>
    </div>

    <!-- Comments Section -->
    @if(isset($comments) && count($comments) > 0)
    <div class="section-header">💬 TEACHER COMMENTS & REMARKS</div>

    @foreach($comments as $commentType => $commentData)
        @if($commentData && !empty($commentData['comment']))
        <div class="comment-box">
            <div class="comment-header">
                @if($commentType === 'class_teacher')
                    👨‍🏫 Class Teacher's Remark
                @elseif($commentType === 'academic')
                    📚 Academic Officer's Comment
                @elseif($commentType === 'discipline')
                    ⚖️ Discipline Master's Comment
                @elseif($commentType === 'accountant')
                    💰 Accountant's Remark
                @else
                    {{ ucfirst(str_replace('_', ' ', $commentType)) }}
                @endif
            </div>
            <div class="comment-text">{{ $commentData['comment'] }}</div>
        </div>
        @endif
    @endforeach
    @endif

    <!-- Parent Letter -->
    @if(isset($parentLetter) && $parentLetter)
    <div style="page-break-before: always;"></div>

    <div class="parent-letter">
        <div class="letter-title">📧 {{ $parentLetter->letter_title ?? 'MESSAGE TO PARENTS' }}</div>

        @if($parentLetter->closing_semester_message)
        <div class="letter-section">
            <div class="letter-heading">Closing Semester Review</div>
            <div class="letter-content">{{ $parentLetter->closing_semester_message }}</div>
        </div>
        @endif

        @if($parentLetter->upcoming_semester_message)
        <div class="letter-section">
            <div class="letter-heading">Upcoming Semester</div>
            <div class="letter-content">{{ $parentLetter->upcoming_semester_message }}</div>
        </div>
        @endif

        @if($parentLetter->fee_payment_notice)
        <div class="highlight-box fee">
            <div class="letter-heading">💳 Fee Payment Notice</div>
            <div class="letter-content">{{ $parentLetter->fee_payment_notice }}</div>
        </div>
        @endif

        @if($parentLetter->safari_trips)
        <div class="highlight-box trip">
            <div class="letter-heading">🚌 Field Trip Information</div>
            <div class="letter-content">{{ $parentLetter->safari_trips }}</div>
        </div>
        @endif

        @if($parentLetter->special_announcements)
        <div class="highlight-box announcement">
            <div class="letter-heading">📢 Special Announcements</div>
            <div class="letter-content">{{ $parentLetter->special_announcements }}</div>
        </div>
        @endif

        @if($parentLetter->signature_name)
        <div style="margin-top: 20px; text-align: right;">
            <div class="signature-line" style="float: right;"></div>
            <div style="clear: both;"></div>
            <div style="font-size: 9px; font-weight: 700; color: #78350f;">{{ $parentLetter->signature_name }}</div>
            <div style="font-size: 8px; color: #92400e;">{{ $parentLetter->signature_title }}</div>
        </div>
        @endif
    </div>
    @endif

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">CLASS TEACHER</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">ACADEMIC OFFICER</div>
        </div>
        <div class="signature-box">
            <div class="signature-line"></div>
            <div class="signature-label">HEAD TEACHER</div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-official">
            This is an official academic performance report issued by {{ $schoolInfo['name'] ?? 'School' }}
        </div>
        <div>
            @if(isset($schoolInfo['report_phone']) && !empty($schoolInfo['report_phone']))
            📞 {{ $schoolInfo['report_phone'] }} •
            @endif
            @if(isset($schoolInfo['report_email']) && !empty($schoolInfo['report_email']))
            ✉️ {{ $schoolInfo['report_email'] }}
            @endif
        </div>
        <div style="margin-top: 5px; font-size: 7px;">
            Generated on {{ now()->format('d M Y, H:i') }} • Report ID: RC-{{ $reportCard->id }}
        </div>
    </div>
</body>
</html>
