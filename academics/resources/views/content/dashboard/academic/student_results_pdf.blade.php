<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Exam Results - {{ mb_convert_encoding($class->name ?? '', 'UTF-8', 'UTF-8') }} - {{ mb_convert_encoding($exam->name ?? '', 'UTF-8', 'UTF-8') }}</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            font-style: normal;
            font-weight: normal;
            src: url({{ storage_path('fonts/dejavu-sans/DejaVuSans.ttf') }}) format('truetype');
        }
        * {
            font-family: 'DejaVu Sans', sans-serif;
        }
        body {
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 18px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }
        .subject-grade {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .badge {
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
            color: white;
        }
        .badge-primary { background-color: #007bff; }
        .badge-danger { background-color: #dc3545; }
        .badge-success { background-color: #28a745; }
        .text-center { text-align: center; }
        .footer {
            margin-top: 20px;
            font-size: 10px;
            text-align: center;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
         <h1>{{ $School ?? 'School' }}</h1>
        <h2>{{ mb_convert_encoding($class->name ?? 'Class', 'UTF-8', 'UTF-8') }} - {{ mb_convert_encoding($exam->name ?? 'Exam', 'UTF-8', 'UTF-8') }} Results</h2>
        <p>Grading System: {{ mb_convert_encoding(ucfirst($gradingType ?? ''), 'UTF-8', 'UTF-8') }}</p>
    </div>

    @if(!empty($results) && count($results) > 0 && !empty($allSubjects))
        <table>
            <thead>
                <tr>
                    <th width="30">#</th>
                    <th>Student</th>
                    @foreach($allSubjects as $subject)
                        <th class="text-center">{{ mb_convert_encoding($subject, 'UTF-8', 'UTF-8') }}</th>
                    @endforeach
                    <th class="text-center">Total</th>
                    <th class="text-center">Average</th>
                    <th class="text-center">Grade</th>
                </tr>
            </thead>
            <tbody>
                @foreach($results as $index => $student)
                    @php
                        // Clean student name
                        $studentName = mb_convert_encoding($student['student']['name'] ?? '', 'UTF-8', 'UTF-8');

                        // Create subject map for easy lookup
                        $subjectMap = [];
                        foreach ($student['raw_subjects'] as $subject) {
                            $subjectMap[mb_convert_encoding($subject['subject_name'], 'UTF-8', 'UTF-8')] = [
                                'marks' => $subject['marks'],
                                'grade' => mb_convert_encoding($subject['grade'], 'UTF-8', 'UTF-8')
                            ];
                        }
                    @endphp
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $studentName }}</td>

                        @foreach($allSubjects as $subjectName)
                            @php
                                $subjectNameUtf = mb_convert_encoding($subjectName, 'UTF-8', 'UTF-8');
                            @endphp
                            <td class="text-center">
                                @if(isset($subjectMap[$subjectNameUtf]))
                                    <div class="subject-grade">
                                        <span>{{ $subjectMap[$subjectNameUtf]['marks'] ?? 0 }}</span>
                                        <span class="badge {{ $subjectMap[$subjectNameUtf]['grade'] === 'F' ? 'badge-danger' : 'badge-primary' }}">
                                            {{ $subjectMap[$subjectNameUtf]['grade'] }}
                                        </span>
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                        @endforeach

                        <td class="text-center">{{ $student['total_marks'] ?? 0 }}</td>
                        <td class="text-center">{{ number_format($student['average_marks'] ?? 0, 2) }}</td>
                        <td class="text-center">
                            <span class="badge {{ $student['final_grade'] === 'Fail' ? 'badge-danger' : 'badge-success' }}">
                                {{ mb_convert_encoding($student['final_grade'] ?? 'N/A', 'UTF-8', 'UTF-8') }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="text-center">No results found for this exam.</p>
    @endif


</body>
</html>
