<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Result Sheet</title>
  <style>
    body { font-family: sans-serif; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    th, td { border: 1px solid #000; padding: 6px; text-align: left; }
    h2 { margin-bottom: 0; }
  </style>
</head>
<body>
  <h2>Result Sheet</h2>
  <p><strong>Exam:</strong> {{ $exam->name }}</p>
  <p><strong>Class:</strong> {{ $class->name }}</p>
  <p><strong>Subject:</strong> {{ $subject->name }}</p>

  <table>
    <thead>
      <tr>
        <th>#</th>
        <th>Student Name</th>
        <th>Registration No</th>
        <th>Marks</th>
      </tr>
    </thead>
    <tbody>
      @foreach($results as $index => $mark)
        <tr>
          <td>{{ $index + 1 }}</td>
          <td>{{ trim($mark->student->first_name . ' ' . $mark->student->middle_name . ' ' . $mark->student->last_name) }}</td>
          <td>{{ $mark->student->registration_no }}</td>
          <td>{{ $mark->marks }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
</body>
</html>
