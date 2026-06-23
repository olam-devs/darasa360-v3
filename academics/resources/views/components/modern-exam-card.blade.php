{{--
  Modern Exam Card Component

  Usage:
  @include('components.modern-exam-card', [
    'exam' => $exam,
    'color' => 'purple', // purple, blue, yellow, coral, green, pink
  ])
--}}

@php
  $cardColor = $color ?? 'purple';
  $examTitle = $exam->name ?? 'Exam Name';
  $examClass = $exam->class->name ?? 'Class Name';
  $examDate = isset($exam->date) ? \Carbon\Carbon::parse($exam->date)->format('M d, Y') : 'Date TBA';
  $examTime = isset($exam->time) ? \Carbon\Carbon::parse($exam->time)->format('g:i A') : '9:00 AM';
  $studentCount = $exam->students_count ?? 0;
  $examId = $exam->id ?? 0;
@endphp

<div class="exam-card {{ $cardColor }}" onclick="viewExam({{ $examId }})">
  <div class="exam-card-header">
    <span class="exam-card-date">{{ $examDate }}</span>
    <button class="exam-card-menu" onclick="event.stopPropagation(); openExamMenu({{ $examId }})">
      <i class="bx bx-dots-vertical-rounded"></i>
    </button>
  </div>

  <h3 class="exam-card-title">{{ $examTitle }}</h3>
  <p class="exam-card-class">{{ $examClass }}</p>

  <div class="exam-card-meta">
    <div class="exam-card-meta-item">
      <i class="bx bx-time exam-card-meta-icon"></i>
      <span>{{ $examTime }}</span>
    </div>
    <div class="exam-card-meta-item">
      <i class="bx bx-group exam-card-meta-icon"></i>
      <span>{{ $studentCount }} Students</span>
    </div>
  </div>

  @if (isset($exam->status))
    <div class="mt-2">
      @if ($exam->status === 'completed')
        <span class="modern-badge modern-badge-green">Completed</span>
      @elseif ($exam->status === 'ongoing')
        <span class="modern-badge modern-badge-blue">Ongoing</span>
      @elseif ($exam->status === 'upcoming')
        <span class="modern-badge modern-badge-gray">Upcoming</span>
      @endif
    </div>
  @endif
</div>

<script>
function viewExam(examId) {
  window.location.href = `/exams/${examId}`;
}

function openExamMenu(examId) {
  // Implement exam menu dropdown
  console.log('Opening menu for exam:', examId);
}
</script>
