<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Card - {{ $student->first_name ?? 'Student' }} {{ $student->last_name ?? '' }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #1a1a1a;
            padding: 15px;
        }

        /* Modern Header - DomPDF Compatible */
        .header {
            background: #667eea;
            color: white;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
            border-radius: 10px;
        }

        .school-name {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .school-motto {
            font-size: 10px;
            margin-bottom: 15px;
            font-style: italic;
        }

        .report-title {
            font-size: 18px;
            font-weight: bold;
            background: #764ba2;
            padding: 10px;
            border-radius: 20px;
            margin: 10px auto;
            display: inline-block;
        }

        .term-info {
            background: white;
            color: #667eea;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: bold;
            display: inline-block;
            margin-top: 10px;
        }

        /* Student Information Card */
        .student-card {
            background: #f8f9fa;
            border: 2px solid #667eea;
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .student-name {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }

        .info-table {
            width: 100%;
            margin-top: 10px;
        }

        .info-table td {
            padding: 5px;
            font-size: 10px;
        }

        .info-label {
            font-weight: bold;
            color: #666;
            width: 30%;
        }

        .info-value {
            color: #1a1a1a;
            font-weight: 600;
        }

        /* Performance Summary Cards */
        .summary-cards {
            width: 100%;
            margin-bottom: 15px;
        }

        .summary-cards td {
            padding: 15px;
            text-align: center;
            border-radius: 10px;
            color: white;
        }

        .card-grade {
            background: #10b981;
        }

        .card-average {
            background: #3b82f6;
        }

        .card-position {
            background: #f59e0b;
        }

        .card-label {
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .card-value {
            font-size: 32px;
            font-weight: bold;
        }

        .card-suffix {
            font-size: 11px;
            margin-top: 3px;
        }

        /* Section Headers */
        .section-header {
            background: #667eea;
            color: white;
            padding: 10px 15px;
            font-size: 12px;
            font-weight: bold;
            margin: 20px 0 10px 0;
            border-radius: 5px;
        }

        /* Performance Table */
        .performance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .performance-table thead {
            background: #1e293b;
            color: white;
        }

        .performance-table th {
            padding: 10px 5px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #333;
        }

        .performance-table td {
            padding: 8px 5px;
            font-size: 10px;
            text-align: center;
            border: 1px solid #ddd;
        }

        .performance-table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .performance-table td:first-child {
            text-align: left;
            font-weight: bold;
            color: #1a1a1a;
        }

        /* Grade Badges */
        .grade-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 9px;
            color: white;
        }

        .grade-A { background: #10b981; }
        .grade-B { background: #3b82f6; }
        .grade-C { background: #f59e0b; }
        .grade-D { background: #f97316; }
        .grade-E { background: #ef4444; }
        .grade-F { background: #991b1b; }

        /* Comments Section */
        .comment-box {
            background: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }

        .comment-header {
            font-size: 9px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .comment-text {
            font-size: 9px;
            color: #4b5563;
            line-height: 1.6;
        }

        /* Parent Letter */
        .parent-letter {
            background: #fffbeb;
            border: 2px solid #f59e0b;
            border-radius: 10px;
            padding: 15px;
            margin: 15px 0;
        }

        .letter-title {
            font-size: 14px;
            font-weight: bold;
            color: #92400e;
            text-align: center;
            margin-bottom: 10px;
        }

        .letter-section {
            margin-bottom: 10px;
        }

        .letter-heading {
            font-size: 10px;
            font-weight: bold;
            color: #78350f;
            margin-bottom: 5px;
        }

        .letter-content {
            font-size: 9px;
            color: #451a03;
            line-height: 1.6;
        }

        .highlight-box {
            padding: 10px;
            border-radius: 5px;
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

        /* Grading Scale */
        .grading-scale {
            background: #f9fafb;
            padding: 10px;
            border-radius: 5px;
            margin: 15px 0;
            border: 1px solid #ddd;
        }

        .scale-title {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 8px;
            text-align: center;
        }

        .scale-table {
            width: 100%;
            border-collapse: collapse;
        }

        .scale-table td {
            padding: 5px;
            text-align: center;
            font-size: 8px;
            border: 1px solid #ddd;
        }

        /* Signatures */
        .signatures {
            width: 100%;
            margin-top: 20px;
            border-top: 2px solid #ddd;
            padding-top: 15px;
        }

        .signatures td {
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
        }

        .signature-line {
            width: 100px;
            height: 1px;
            background: #1a1a1a;
            margin: 0 auto 5px;
        }

        .signature-label {
            font-size: 8px;
            font-weight: bold;
            color: #666;
        }

        /* Footer */
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 8px;
            color: #666;
        }

        .footer-official {
            font-weight: bold;
            color: #1a1a1a;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="school-name">{{ $schoolInfo['name'] ?? 'SCHOOL NAME' }}</div>
        <div class="school-motto">{{ $schoolInfo['motto'] ?? 'Excellence in Education' }}</div>
        <div class="report-title">ACADEMIC PERFORMANCE REPORT</div>
        <div class="term-info">{{ $configuration->term ?? 'Term' }} • {{ $configuration->academic_year ?? 'Year' }}</div>
    </div>

    <!-- Student Information -->
    <div class="student-card">
        <div class="student-name">{{ strtoupper(($student->first_name ?? '') . ' ' . ($student->last_name ?? '')) }}</div>

        <table class="info-table">
            <tr>
                <td class="info-label">Registration No:</td>
                <td class="info-value">{{ $student->registration_no ?? 'N/A' }}</td>
                <td class="info-label">Class:</td>
                <td class="info-value">{{ $configuration->class->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="info-label">Date of Birth:</td>
                <td class="info-value">{{ isset($student->dob) ? \Carbon\Carbon::parse($student->dob)->format('d M Y') : 'N/A' }}</td>
                <td class="info-label">Gender:</td>
                <td class="info-value">{{ $student->gender ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <!-- Performance Summary -->
    <table class="summary-cards" cellspacing="10">
        <tr>
            <td class="card-grade">
                <div class="card-label">Overall Grade</div>
                <div class="card-value">{{ $reportCard->overall_grade ?? 'N/A' }}</div>
            </td>
            <td class="card-average">
                <div class="card-label">Average Score</div>
                <div class="card-value">{{ isset($reportCard->average_marks) ? number_format($reportCard->average_marks, 1) : 'N/A' }}</div>
                <div class="card-suffix">out of 100</div>
            </td>
            <td class="card-position">
                <div class="card-label">Class Position</div>
                <div class="card-value">{{ $reportCard->class_position ?? 'N/A' }}</div>
                <div class="card-suffix">of {{ $reportCard->total_students_in_class ?? 'N/A' }}</div>
            </td>
        </tr>
    </table>

    <!-- Subject Performance Table -->
    <div class="section-header">SUBJECT PERFORMANCE BREAKDOWN</div>

    <table class="performance-table">
        <thead>
            <tr>
                <th>Subject</th>
                @if(isset($configuration->examConfigs))
                    @foreach($configuration->examConfigs as $examConfig)
                    <th>{{ $examConfig->exam->name ?? 'Exam' }}<br>({{ $examConfig->percentage_weight ?? 0 }}%)</th>
                    @endforeach
                @endif
                <th>Total</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($reportCard->subject_performance) && is_array($reportCard->subject_performance))
                @foreach($reportCard->subject_performance as $subject)
                <tr>
                    <td>{{ $subject['subject_name'] ?? 'Unknown' }}</td>
                    @if(isset($subject['exams']) && is_array($subject['exams']))
                        @foreach($subject['exams'] as $exam)
                        <td>{{ isset($exam['marks']) ? number_format($exam['marks'], 0) : '-' }}</td>
                        @endforeach
                    @endif
                    <td><strong>{{ isset($subject['total_weighted_marks']) ? number_format($subject['total_weighted_marks'], 1) : '-' }}</strong></td>
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
                <td><strong>A:</strong> 80-100%</td>
                <td><strong>B:</strong> 70-79%</td>
                <td><strong>C:</strong> 60-69%</td>
                <td><strong>D:</strong> 50-59%</td>
                <td><strong>E:</strong> 40-49%</td>
                <td><strong>F:</strong> 0-39%</td>
            </tr>
        </table>
    </div>

    <!-- Comments Section -->
    @if(isset($comments) && is_array($comments) && count($comments) > 0)
    <div class="section-header">TEACHER COMMENTS & REMARKS</div>

    @foreach($comments as $commentType => $commentData)
        @if(is_array($commentData) && isset($commentData['comment']) && !empty($commentData['comment']))
        <div class="comment-box">
            <div class="comment-header">
                @if($commentType === 'class_teacher')
                    Class Teacher's Remark
                @elseif($commentType === 'academic')
                    Academic Officer's Comment
                @elseif($commentType === 'discipline')
                    Discipline Master's Comment
                @elseif($commentType === 'accountant')
                    Accountant's Remark
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
        <div class="letter-title">{{ $parentLetter->letter_title ?? 'MESSAGE TO PARENTS' }}</div>

        @if(!empty($parentLetter->closing_semester_message))
        <div class="letter-section">
            <div class="letter-heading">Closing Semester Review</div>
            <div class="letter-content">{{ $parentLetter->closing_semester_message }}</div>
        </div>
        @endif

        @if(!empty($parentLetter->upcoming_semester_message))
        <div class="letter-section">
            <div class="letter-heading">Upcoming Semester</div>
            <div class="letter-content">{{ $parentLetter->upcoming_semester_message }}</div>
        </div>
        @endif

        @if(!empty($parentLetter->fee_payment_notice))
        <div class="highlight-box fee">
            <div class="letter-heading">Fee Payment Notice</div>
            <div class="letter-content">{{ $parentLetter->fee_payment_notice }}</div>
        </div>
        @endif

        @if(!empty($parentLetter->safari_trips))
        <div class="highlight-box trip">
            <div class="letter-heading">Field Trip Information</div>
            <div class="letter-content">{{ $parentLetter->safari_trips }}</div>
        </div>
        @endif

        @if(!empty($parentLetter->special_announcements))
        <div class="highlight-box announcement">
            <div class="letter-heading">Special Announcements</div>
            <div class="letter-content">{{ $parentLetter->special_announcements }}</div>
        </div>
        @endif
    </div>
    @endif

    <!-- Signatures -->
    <table class="signatures" cellspacing="20">
        <tr>
            <td>
                <div class="signature-line"></div>
                <div class="signature-label">CLASS TEACHER</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="signature-label">ACADEMIC OFFICER</div>
            </td>
            <td>
                <div class="signature-line"></div>
                <div class="signature-label">HEAD TEACHER</div>
            </td>
        </tr>
    </table>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-official">
            This is an official academic performance report issued by {{ $schoolInfo['name'] ?? 'School' }}
        </div>
        <div>
            Generated on {{ now()->format('d M Y, H:i') }} • Report ID: RC-{{ $reportCard->id ?? 'N/A' }}
        </div>
    </div>
</body>
</html>
