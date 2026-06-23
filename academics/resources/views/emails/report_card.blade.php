<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Card</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .greeting {
            font-size: 18px;
            margin-bottom: 20px;
            color: #2c3e50;
        }
        .info-box {
            background: white;
            border-left: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .info-box h2 {
            margin-top: 0;
            color: #667eea;
            font-size: 18px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #ecf0f1;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: bold;
            color: #7f8c8d;
        }
        .info-value {
            color: #2c3e50;
            font-weight: 600;
        }
        .performance-summary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        .performance-summary .grade {
            font-size: 48px;
            font-weight: bold;
            margin: 10px 0;
        }
        .attachment-notice {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .attachment-notice strong {
            display: block;
            margin-bottom: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #ecf0f1;
            color: #7f8c8d;
            font-size: 14px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $schoolName }}</h1>
        <p style="margin: 10px 0 0 0;">Student Report Card</p>
    </div>

    <div class="content">
        <p class="greeting">Dear Parent/Guardian,</p>

        <p>We are pleased to share <strong>{{ $studentName }}'s</strong> academic report card for <strong>{{ $term }}</strong> of the <strong>{{ $academicYear }}</strong> academic year.</p>

        <div class="info-box">
            <h2>Student Information</h2>
            <div class="info-row">
                <span class="info-label">Student Name:</span>
                <span class="info-value">{{ $studentName }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Class:</span>
                <span class="info-value">{{ $className }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Term:</span>
                <span class="info-value">{{ $term }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Academic Year:</span>
                <span class="info-value">{{ $academicYear }}</span>
            </div>
        </div>

        <div class="performance-summary">
            <h2 style="margin-top: 0;">Performance Summary</h2>
            <div class="grade">{{ $grade }}</div>
            <p style="margin: 5px 0;">Average: <strong>{{ number_format($averageMarks, 2) }}%</strong></p>
            <p style="margin: 5px 0;">Position: <strong>{{ $position }} of {{ $totalStudents }}</strong></p>
        </div>

        <div class="attachment-notice">
            <strong>📎 Report Card Attached</strong>
            <p style="margin: 5px 0 0 0;">Please find the detailed report card attached to this email as a PDF document. The report includes:</p>
            <ul style="margin: 10px 0 0 20px;">
                <li>Detailed subject performance breakdown</li>
                <li>Teacher and stakeholder comments</li>
                <li>Grading scale and academic standards</li>
                <li>Important notices and announcements</li>
            </ul>
        </div>

        <p>We encourage you to review the report card carefully with your child. If you have any questions or concerns about the report, please do not hesitate to contact us.</p>

        <p>Thank you for your continued partnership in your child's education.</p>

        <div class="footer">
            <p><strong>{{ $schoolName }}</strong></p>
            <p>This is an automated email. Please do not reply directly to this message.</p>
            <p style="margin-top: 10px; font-size: 12px;">© {{ date('Y') }} {{ $schoolName }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
