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
        }
        body {
            font-size: 12px;
            line-height: 1.6;
            color: #2c3e50;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 20px;
        }
        .header h1 {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        .header p {
            color: #7f8c8d;
            margin: 3px 0;
        }
        .header h2 {
            font-size: 20px;
            color: #2c3e50;
            margin-top: 15px;
        }
        .student-info {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
        }
        .student-info table {
            width: 100%;
            border-collapse: collapse;
        }
        .student-info td {
            padding: 5px;
            border: none;
        }
        .student-info strong {
            color: #2c3e50;
        }
        .section-title {
            background: #3498db;
            color: white;
            padding: 10px;
            font-size: 14px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 10px;
            border-radius: 3px;
        }
        .performance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .performance-table th,
        .performance-table td {
            border: 1px solid #bdc3c7;
            padding: 8px;
            text-align: left;
        }
        .performance-table th {
            background-color: #34495e;
            color: white;
            font-weight: bold;
            text-align: center;
        }
        .performance-table td {
            text-align: center;
        }
        .performance-table td.subject-name {
            text-align: left;
            font-weight: bold;
        }
        .grade-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-weight: bold;
            color: white;
        }
        .grade-A { background-color: #27ae60; }
        .grade-B { background-color: #2ecc71; }
        .grade-C { background-color: #f39c12; }
        .grade-D { background-color: #e67e22; }
        .grade-E { background-color: #d35400; }
        .grade-F { background-color: #c0392b; }
        .summary-box {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary-box table {
            width: 100%;
            border-collapse: collapse;
        }
        .summary-box td {
            padding: 8px;
            border: none;
        }
        .summary-box .label {
            font-weight: bold;
            color: #2c3e50;
        }
        .summary-box .value {
            text-align: right;
            color: #3498db;
            font-size: 16px;
            font-weight: bold;
        }
        .comments-section {
            margin-top: 20px;
        }
        .comment-box {
            border: 1px solid #bdc3c7;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
            background: #f8f9fa;
        }
        .comment-header {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
            font-size: 13px;
        }
        .comment-text {
            color: #34495e;
            line-height: 1.5;
        }
        .grading-scale {
            margin-top: 20px;
            font-size: 10px;
            color: #7f8c8d;
        }
        .grading-scale table {
            width: 100%;
            border-collapse: collapse;
        }
        .grading-scale th,
        .grading-scale td {
            padding: 5px;
            border: 1px solid #bdc3c7;
            text-align: center;
        }
        .grading-scale th {
            background: #ecf0f1;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #bdc3c7;
        }
        .signature-section {
            width: 100%;
            margin-top: 20px;
        }
        .signature-section table {
            width: 100%;
            border-collapse: collapse;
        }
        .signature-section td {
            border: none;
            padding: 10px;
            text-align: center;
            vertical-align: bottom;
        }
        .signature-line {
            border-bottom: 1px solid #2c3e50;
            width: 150px;
            margin: 20px auto 5px auto;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>{{ $schoolInfo['name'] ?? 'School Name' }}</h1>
        <p>{{ $schoolInfo['report_address'] ?? $schoolInfo['address'] ?? '' }}</p>
        <p>Tel: {{ $schoolInfo['report_phone'] ?? $schoolInfo['phone'] ?? '' }} | Email: {{ $schoolInfo['report_email'] ?? $schoolInfo['email'] ?? '' }}</p>
        <h2>STUDENT REPORT CARD</h2>
        <p style="font-size: 14px; margin-top: 10px;">{{ $configuration->term }} - {{ $configuration->academic_year }}</p>
    </div>

    <!-- Student Information -->
    <div class="student-info">
        <table>
            <tr>
                <td style="width: 50%;"><strong>Student Name:</strong> {{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</td>
                <td style="width: 50%;"><strong>Registration No:</strong> {{ $student->registration_no }}</td>
            </tr>
            <tr>
                <td><strong>Class:</strong> {{ $configuration->class->name }}</td>
                <td><strong>Date of Issue:</strong> {{ now()->format('d F Y') }}</td>
            </tr>
        </table>
    </div>

    <!-- Academic Performance -->
    <div class="section-title">ACADEMIC PERFORMANCE</div>

    <table class="performance-table">
        <thead>
            <tr>
                <th style="width: 25%;">Subject</th>
                @foreach($configuration->examConfigs as $examConfig)
                    <th>{{ $examConfig->exam->name }}<br>({{ $examConfig->percentage_weight }}%)</th>
                @endforeach
                <th>Total</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($reportCard->subject_performance) && is_array($reportCard->subject_performance))
                @foreach($reportCard->subject_performance as $subject)
                    <tr>
                        <td class="subject-name">{{ $subject['subject_name'] }}</td>
                        @foreach($subject['exams'] as $exam)
                            <td>{{ $exam['marks'] }}</td>
                        @endforeach
                        <td><strong>{{ number_format($subject['total_weighted_marks'], 2) }}</strong></td>
                        <td>
                            <span class="grade-badge grade-{{ $subject['grade'] }}">{{ $subject['grade'] }}</span>
                        </td>
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>

    <!-- Performance Summary -->
    <div class="summary-box">
        <table>
            <tr>
                <td class="label">Total Marks:</td>
                <td class="value">{{ number_format($reportCard->total_marks, 2) }}</td>
                <td class="label">Average:</td>
                <td class="value">{{ number_format($reportCard->average_marks, 2) }}%</td>
            </tr>
            <tr>
                <td class="label">Overall Grade:</td>
                <td class="value">
                    <span class="grade-badge grade-{{ $reportCard->overall_grade }}">{{ $reportCard->overall_grade }}</span>
                </td>
                <td class="label">Position in Class:</td>
                <td class="value">{{ $reportCard->class_position }} / {{ $reportCard->total_students_in_class }}</td>
            </tr>
        </table>
    </div>

    <!-- Comments Section -->
    @if($comments->isNotEmpty())
        <div class="section-title">STAKEHOLDER COMMENTS</div>
        <div class="comments-section">
            @foreach($comments as $stakeholderType => $stakeholderComments)
                @foreach($stakeholderComments as $comment)
                    <div class="comment-box">
                        <div class="comment-header">
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
        <strong>Grading Scale:</strong>
        <table>
            <tr>
                <th>Grade</th>
                <th>A</th>
                <th>B</th>
                <th>C</th>
                <th>D</th>
                <th>E</th>
                <th>F</th>
            </tr>
            <tr>
                <td><strong>Marks</strong></td>
                <td>80-100</td>
                <td>70-79</td>
                <td>60-69</td>
                <td>50-59</td>
                <td>40-49</td>
                <td>0-39</td>
            </tr>
        </table>
    </div>

    <!-- Parent Letter Section -->
    @if(isset($parentLetter) && $parentLetter)
        <div class="page-break"></div>

        <div class="header">
            <h1>{{ $schoolInfo['name'] ?? 'School Name' }}</h1>
            <p>{{ $schoolInfo['report_address'] ?? $schoolInfo['address'] ?? '' }}</p>
            <p>Tel: {{ $schoolInfo['report_phone'] ?? $schoolInfo['phone'] ?? '' }} | Email: {{ $schoolInfo['report_email'] ?? $schoolInfo['email'] ?? '' }}</p>
        </div>

        <div class="section-title">{{ strtoupper($parentLetter->letter_title) }}</div>

        <div style="margin: 20px 0; line-height: 1.8;">
            <p><strong>Dear Parents/Guardians,</strong></p>
            <br>

            @if($parentLetter->closing_semester_message)
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #2c3e50; margin-bottom: 10px;">Closing Semester Reflection</h4>
                    <p style="text-align: justify;">{{ $parentLetter->closing_semester_message }}</p>
                </div>
            @endif

            @if($parentLetter->upcoming_semester_message)
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #2c3e50; margin-bottom: 10px;">Upcoming Semester</h4>
                    <p style="text-align: justify;">{{ $parentLetter->upcoming_semester_message }}</p>
                </div>
            @endif

            @if($parentLetter->fee_payment_notice)
                <div style="margin-bottom: 20px; background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; border-radius: 3px;">
                    <h4 style="color: #856404; margin-bottom: 10px;">Fee Payment Information</h4>
                    <p style="text-align: justify; color: #856404;">{{ $parentLetter->fee_payment_notice }}</p>
                </div>
            @endif

            @if($parentLetter->safari_trips)
                <div style="margin-bottom: 20px; background: #d1ecf1; padding: 15px; border-left: 4px solid #17a2b8; border-radius: 3px;">
                    <h4 style="color: #0c5460; margin-bottom: 10px;">Safari Trips & Excursions</h4>
                    <p style="text-align: justify; color: #0c5460;">{{ $parentLetter->safari_trips }}</p>
                </div>
            @endif

            @if($parentLetter->special_announcements)
                <div style="margin-bottom: 20px; background: #d4edda; padding: 15px; border-left: 4px solid #28a745; border-radius: 3px;">
                    <h4 style="color: #155724; margin-bottom: 10px;">Special Announcements</h4>
                    <p style="text-align: justify; color: #155724;">{{ $parentLetter->special_announcements }}</p>
                </div>
            @endif

            @if($parentLetter->other_information)
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #2c3e50; margin-bottom: 10px;">Additional Information</h4>
                    <p style="text-align: justify;">{{ $parentLetter->other_information }}</p>
                </div>
            @endif

            <div style="margin-top: 40px;">
                <p>Thank you for your continued support and partnership in your child's education.</p>
                <br>
                <p><strong>{{ $parentLetter->signature_name }}</strong></p>
                <p>{{ $parentLetter->signature_title }}</p>
                <p>{{ now()->format('d F Y') }}</p>
            </div>
        </div>
    @endif

    <!-- Footer with Signatures -->
    <div class="footer" style="{{ isset($parentLetter) && $parentLetter ? '' : '' }}">
        <div class="signature-section">
            <table>
                <tr>
                    <td>
                        <div class="signature-line"></div>
                        <strong>Class Teacher</strong>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                        <strong>Head Teacher / Academic</strong>
                    </td>
                    <td>
                        <div class="signature-line"></div>
                        <strong>Parent / Guardian</strong>
                    </td>
                </tr>
            </table>
        </div>
        <p style="text-align: center; margin-top: 30px; font-size: 10px; color: #95a5a6;">
            This is an official report card issued on {{ now()->format('d F Y') }}
        </p>
    </div>
</body>
</html>
