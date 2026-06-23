<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Exam Results - {{ $exam->title ?? '' }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; }
        h1, h2, h3, h4, h5, h6 { margin: 0; padding: 0; }
        .header { text-align: center; margin-bottom: 1rem; }
        .footer { margin-top: 2rem; font-size: 12px; text-align: center; color: #555; }
    </style>
</head>
<body>

  <div class="header">
    <h2>Exam Results</h2>
    <h3>{{ $exam->title ?? '' }}</h3>
    <p>Date: {{ \Carbon\Carbon::parse($exam->from_date)->format('d M Y') }}</p>
  </div>

  <table>
    <thead>
      <tr>
        <th>Subject</th>
        <th>Marks Obtained</th>
        <th>Grade</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($results as $result)
        <tr>
          <td>{{ $result->subject->name ?? 'N/A' }}</td>
          <td>{{ $result->marks }}</td>
          <td>{{ $result->grade }}</td>
        </tr>
      @endforeach
      <tr>
        <td><strong>Total</strong></td>
        <td><strong>{{ $totalMarks }}</strong></td>
        <td></td>
      </tr>
    </tbody>
  </table>

  <div class="mt-3">
    <h4>Overall Grade</h4>
    @if ($overallGrade)
      <p><strong>{{ $overallGrade }}</strong> ({{ ucfirst($gradingType) }} based)</p>
    @else
      <p><em>No grade found for total marks using {{ $gradingType }} grading.</em></p>
    @endif
  </div>

  <div class="footer">
    <p>Generated on {{ now()->format('d M Y H:i') }}</p>
  </div>

</body>
</html>
