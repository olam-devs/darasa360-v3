<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Result Sheet - All Subjects</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }
    table, th, td {
      border: 1px solid #333;
    }
    th, td {
      padding: 6px;
      text-align: center;
    }
    th {
      background-color: #f0f0f0;
    }
    .header {
      text-align: center;
      margin-bottom: 20px;
    }
    .header h2, .header p {
      margin: 0;
      padding: 0;
    }
  </style>
</head>
<body>
  <div class="header">
    <h2>Result Sheet</h2>
    <p><strong>Exam:</strong> {{ $exam->name }}</p>
    <p><strong>Class:</strong> {{ $class->name }}</p>
  </div>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Student Name</th>
        @foreach($subjects as $subject)
          <th>{{ $subject->name }}</th>
        @endforeach
      </tr>
    </thead>
    <tbody>
      @foreach($results as $studentId => $marks)
        @php
          $student = $marks->first()->student;
        @endphp
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td>{{ $student->first_name }} {{ $student->middle_name }} {{ $student->last_name }}</td>
          @foreach($subjects as $subject)
            @php
              $mark = $marks->firstWhere('subject_id', $subject->id);
            @endphp
            <td>{{ $mark ? $mark->marks : '-' }}</td>
          @endforeach
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
