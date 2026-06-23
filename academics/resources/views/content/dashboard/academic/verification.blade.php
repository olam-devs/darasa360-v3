@extends('layouts.modernLayout', [
    'pageTitle' => 'Marks Verification'
])

@section('title', 'Academic Verification')

@section('content')
<div class="container">
    {{-- Alerts --}}
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <h4 class="fw-bold mb-4">Marks Verification</h4>

    {{-- Filter Form --}}
    <div class="card mb-4">
        <div class="card-body">
            <form id="verificationFilterForm" class="row g-3">
                @csrf
                <div class="col-md-4">
                    <label class="form-label">Class</label>
                    <select name="class_id" id="class_id" class="form-select" required>
                        <option value="">Select Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Exam</label>
                    <select name="exam_id" id="exam_id" class="form-select" required>
                        <option value="">Select Exam</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Subject</label>
                    <select name="subject_id" id="subject_id" class="form-select">
                        <option value="">All Subjects</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12 mt-3">
                    <button type="button" id="fetchDataBtn" class="btn btn-primary">Fetch Data</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Marks Table --}}
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('academic.batchVerification') }}">
                @csrf
                <input type="hidden" name="class_id" id="batch_class_id">
                <input type="hidden" name="exam_id" id="batch_exam_id">

                <table class="table table-bordered" id="marksTable">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>Student</th>
                            <th>Subject</th>
                            <th>Mark</th>
                            <th>Verified</th>
                            <th>Toggle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center text-muted">No data loaded</td>
                        </tr>
                    </tbody>
                </table>

                <button type="submit" class="btn btn-success mt-3">Batch Verify Selected</button>
            </form>
        </div>
    </div>
</div>


<script>
  document.addEventListener('DOMContentLoaded', function () {

      // Load exams when class changes
      document.getElementById('class_id').addEventListener('change', function () {
          let classId = this.value;
          let examSelect = document.getElementById('exam_id');
          examSelect.innerHTML = '<option value="">Loading...</option>';

          fetch(`/academic/verification/exams/${classId}`)
              .then(res => res.json())
              .then(data => {
                  examSelect.innerHTML = '<option value="">Select Exam</option>';
                  data.forEach(exam => {
                      examSelect.innerHTML += `<option value="${exam.id}">${exam.name}</option>`;
                  });
              });
      });

      // Fetch verification data
      document.getElementById('fetchDataBtn').addEventListener('click', function () {
          let classId = document.getElementById('class_id').value;
          let examId = document.getElementById('exam_id').value;
          let subjectId = document.getElementById('subject_id').value;

          if (!classId || !examId) {
              alert("Please select Class and Exam");
              return;
          }

          document.getElementById('batch_class_id').value = classId;
          document.getElementById('batch_exam_id').value = examId;

          fetch(`/academic/verification/fetch`, {
              method: 'POST',
              headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
              },
              body: JSON.stringify({ class_id: classId, exam_id: examId, subject_id: subjectId })
          })
          .then(res => res.json())
          .then(data => {
              let tbody = document.querySelector('#marksTable tbody');
              tbody.innerHTML = '';

              if (data.length === 0) {
                  tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No records found</td></tr>';
                  return;
              }

              data.forEach(row => {
                  tbody.innerHTML += `
                      <tr>
                          <td>
                              <input type="checkbox" name="verification_list[]" value="${row.student_id}_${row.subject_id}">
                          </td>
                          <td>${row.first_name} ${row.last_name}</td>
                          <td>${row.subject_name}</td>
                          <td>${row.marks}</td>
                          <td>
                              <span class="badge ${row.verified_by_academic ? 'bg-primary' : 'bg-secondary'}">
                                  ${row.verified_by_academic ? 'Verified' : 'Not Verified'}
                              </span>
                          </td>
                          <td>
                              <button type="button"
                                  class="btn btn-sm ${row.verified_by_academic ? 'btn-primary' : 'btn-secondary'} toggle-btn"
                                  data-student="${row.student_id}"
                                  data-subject="${row.subject_id}"
                                  data-class="${classId}"
                                  data-exam="${examId}">
                                  ${row.verified_by_academic ? 'Verified' : 'Not Verified'}
                              </button>
                          </td>
                      </tr>
                  `;
              });
          });
      });

      // Delegate toggle button click
      document.addEventListener('click', function(e) {
    if (e.target.classList.contains('toggle-btn')) {
        let btn = e.target;
        let studentId = btn.dataset.student;
        let subjectId = btn.dataset.subject;
        let classId = btn.dataset.class;
        let examId = btn.dataset.exam;

        // Determine new status (true if currently NOT verified)
        let isCurrentlyVerified = btn.classList.contains('btn-primary');
        let newStatus = isCurrentlyVerified ? 0 : 1;

        fetch(`/academic/verification/toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
                student_id: studentId,
                subject_id: subjectId,
                class_id: classId,
                exam_id: examId,
                verified: newStatus // ✅ send numeric boolean
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                // Update button style & text
                btn.classList.toggle('btn-primary', newStatus === 1);
                btn.classList.toggle('btn-secondary', newStatus === 0);
                btn.textContent = newStatus === 1 ? 'Verified' : 'Not Verified';

                // Update badge
                let badge = btn.closest('tr').querySelector('td:nth-child(5) span');
                if (badge) {
                    badge.className = newStatus === 1 ? 'badge bg-primary' : 'badge bg-secondary';
                    badge.textContent = newStatus === 1 ? 'Verified' : 'Not Verified';
                }
            }
        });
    }
});


      // Select all checkboxes
      document.getElementById('selectAll').addEventListener('change', function () {
          document.querySelectorAll('#marksTable tbody input[type="checkbox"]').forEach(cb => {
              cb.checked = this.checked;
          });
      });

  });
  </script>

@endsection

@section('scripts')

@endsection
