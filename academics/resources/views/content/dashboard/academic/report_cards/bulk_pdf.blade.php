<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Report Cards - {{ $configuration->class->name }} - {{ $configuration->term }}</title>
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
        .page-break {
            page-break-after: always;
        }
        /* Reuse styles from single PDF */
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
    </style>
</head>
<body>
    @foreach($reportCards as $index => $reportCard)
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
                    <td style="width: 50%;"><strong>Student Name:</strong> {{ $reportCard->student->first_name }} {{ $reportCard->student->middle_name }} {{ $reportCard->student->last_name }}</td>
                    <td style="width: 50%;"><strong>Registration No:</strong> {{ $reportCard->student->registration_no }}</td>
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

        <!-- Footer with Signatures -->
        <div class="footer">
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

        @if($index < count($reportCards) - 1)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
</html>
