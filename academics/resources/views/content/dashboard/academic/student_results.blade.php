@extends('layouts.modernLayout', [
    'pageTitle' => 'Student Results'
])

@section('title', 'Student Results')

@section('content')

<div class="container-xxl flex-grow-1 container-p-y">
  @if(isset($successMessage))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ $successMessage }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Check Student Results</h4>
    </div>

    {{-- Selection Form --}}
    <div class="card-body">
      <form method="POST" action="{{ route('academic.studentResults.process') }}" id="resultsForm">
        @csrf
        <div class="row mb-3">
          <div class="col-md-6 mb-3">
            <label for="class_id" class="form-label">Class</label>
            <select name="class_id" id="class_id" class="form-select" required>
              <option value="">Select Class</option>
              @php
                $uniqueClasses = collect($availableClassExamPairs)->pluck('class')->unique('id');
              @endphp
              @foreach($uniqueClasses as $cls)
                <option value="{{ $cls->id }}" {{ isset($class) && $class->id == $cls->id ? 'selected' : '' }}>
                  {{ $cls->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="col-md-6 mb-3">
            <label for="exam_id" class="form-label">Exam</label>
            <select name="exam_id" id="exam_id" class="form-select" required>
              <option value="">Select Exam</option>
              @php
                $uniqueExams = collect($availableClassExamPairs)->pluck('exam')->unique('id');
              @endphp
              @foreach($uniqueExams as $ex)
                <option value="{{ $ex->id }}" {{ isset($exam) && $exam->id == $ex->id ? 'selected' : '' }}>
                  {{ $ex->name }}
                </option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary">
            <i class="bx bx-show-alt me-1"></i> View Results
          </button>
        </div>
      </form>
    </div>
  </div>

  @isset($results)
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">Results for {{ $class->name }} - {{ $exam->name }}</h5>
        @if(session('role') !== 'Student')
        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#performanceFilters">
          <i class="bx bx-filter-alt me-1"></i> Performance Analysis
        </button>
        @endif
      </div>

      {{-- Performance Analysis Filters --}}
      @if(session('role') !== 'Student')
      <div class="collapse" id="performanceFilters">
        <div class="card-body border-bottom bg-light">
          <div class="row g-3">
            <div class="col-md-3">
              <label class="form-label fw-semibold">Filter by Gender</label>
              <select class="form-select form-select-sm" id="genderFilter">
                <option value="">All Genders</option>
                <option value="Male">Male</option>
                <option value="Female">Female</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label fw-semibold">Filter by Stream</label>
              <select class="form-select form-select-sm" id="streamFilter">
                <option value="">All Streams</option>
                <option value="Science">Science</option>
                <option value="Arts">Arts</option>
                <option value="Commerce">Commerce</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label fw-semibold">Filter by Performance</label>
              <select class="form-select form-select-sm" id="performanceFilter">
                <option value="">All Performance Levels</option>
                <option value="excellent">Excellent (80-100%)</option>
                <option value="good">Good (60-79%)</option>
                <option value="average">Average (40-59%)</option>
                <option value="poor">Poor (Below 40%)</option>
              </select>
            </div>

            <div class="col-md-3">
              <label class="form-label fw-semibold">Search Student</label>
              <input type="text" class="form-control form-control-sm" id="studentSearch" placeholder="Search by name...">
            </div>
          </div>

          <div class="row mt-3">
            <div class="col-12">
              <button type="button" class="btn btn-sm btn-primary me-2" id="applyFilters">
                <i class="bx bx-check me-1"></i> Apply Filters
              </button>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="resetFilters">
                <i class="bx bx-reset me-1"></i> Reset
              </button>
            </div>
          </div>

          {{-- Statistics Summary --}}
          <div class="row mt-4">
            <div class="col-md-3">
              <div class="d-flex align-items-center">
                <div class="modern-badge modern-badge-green me-2">
                  <i class="bx bx-trending-up"></i>
                </div>
                <div>
                  <small class="text-muted d-block">Excellent</small>
                  <strong id="excellentCount">0</strong>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="d-flex align-items-center">
                <div class="modern-badge modern-badge-blue me-2">
                  <i class="bx bx-line-chart"></i>
                </div>
                <div>
                  <small class="text-muted d-block">Good</small>
                  <strong id="goodCount">0</strong>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="d-flex align-items-center">
                <div class="modern-badge modern-badge-yellow me-2">
                  <i class="bx bx-trending-down"></i>
                </div>
                <div>
                  <small class="text-muted d-block">Average</small>
                  <strong id="averageCount">0</strong>
                </div>
              </div>
            </div>
            <div class="col-md-3">
              <div class="d-flex align-items-center">
                <div class="modern-badge modern-badge-red me-2">
                  <i class="bx bx-down-arrow-alt"></i>
                </div>
                <div>
                  <small class="text-muted d-block">Poor</small>
                  <strong id="poorCount">0</strong>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      @endif

      <div class="card-body">


        @php
      if (!function_exists('ordinal')) {
        function ordinal($number) {
            $ends = ['th','st','nd','rd','th','th','th','th','th','th'];
            if (($number % 100) >= 11 && ($number % 100) <= 13) {
                return $number. 'th';
            }
            return $number. $ends[$number % 10];
        }
    }
      @endphp

{{-- Show total students if not student --}}
@if(session('role') !== 'Student')
    <h6 class="mb-3 text-muted">
      Showing: <span id="displayedCount">{{ $totalStudents }}</span> of {{ $totalStudents }} students
    </h6>
@endif

        <div class="table-responsive">
          <table class="table table-striped table-hover" id="resultsTable">
            <thead class="table-light">
              <tr>
                <th>Student</th>
                @if(count($results) > 0)
                  @foreach($results[0]->subjects as $subject)
                    <th class="text-center">{{ $subject['subject_name'] }}</th>
                  @endforeach
                @endif
                <th class="text-center">Total</th>
                <th class="text-center">Average</th>
                <th class="text-center">Grade</th>
                <th class="text-center">Position</th> {{-- New column --}}
                  {{-- Extra column only if role is student --}}
                @if(session('role') === 'Student')
                    <th>Out of</th>
                @endif
              </tr>
            </thead>
            <tbody>
              @foreach($results as $student)
                <tr class="student-row"
                    data-student-name="{{ strtolower($student->student_name) }}"
                    data-average="{{ $student->average_marks ?? 0 }}"
                    data-gender="{{ $student->gender ?? '' }}"
                    data-stream="{{ $student->stream ?? '' }}">
                  <td>
                    <div class="d-flex align-items-center">
                      <div class="avatar avatar-sm me-3">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                          {{ substr($student->student_name, 0, 1) }}
                        </span>
                      </div>
                      <div>{{ $student->student_name }}</div>
                    </div>
                  </td>

                  @foreach($student->subjects as $sub)
                    <td class="text-center">
                      {{ $sub['marks'] }}
                      <span class="badge {{ $sub['grade'] === 'F' ? 'bg-danger' : 'bg-primary' }} ms-1">
                        ({{ $sub['grade'] }})
                      </span>
                    </td>
                  @endforeach

                  <td class="text-center fw-semibold">{{ $student->total_marks }}</td>
                  <td class="text-center">{{ $student->average_marks ?? '-' }}</td>
                  <td class="text-center">
                    <span class="badge {{ $student->final_grade === 'Fail' ? 'bg-label-danger' : 'bg-label-success' }}">
                      {{ $student->final_grade }}
                    </span>
                  </td>
                  <td class="text-center fw-bold">
                    {{ $student->position }}
                  </td>
            {{-- Out of column for student --}}
                    @if(session('role') === 'Student')
                      <td class="fw-bold">{{ $student->position_out_of }}</td>
                    @endif
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>

      <div class="card-footer text-end">
        <div class="d-flex flex-wrap justify-content-end gap-2">
          <button type="button" class="btn btn-primary me-2" id="exportPdfBtn"
            onclick="document.getElementById('downloadForm').submit()">
            <i class="bx bx-download me-1"></i> Export as PDF
          </button>

          <form id="downloadForm" method="POST" action="{{ route('academic.download.student_results') }}" style="display: none;">
              @csrf
              <input type="hidden" name="class_id" value="{{ $class->id ?? '' }}">
              <input type="hidden" name="exam_id" value="{{ $exam->id ?? '' }}">
          </form>
        </div>
      </div>
    </div>
  @endisset
</div>

<style>
  @media (max-width: 768px) {
    table {
      display: block;
      overflow-x: auto;
      white-space: nowrap;
    }
  }
  .badge {
    font-size: 0.75em;
    padding: 0.25em 0.4em;
  }
  .gap-2 {
    gap: 0.5rem;
  }
  .modern-badge {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 20px;
  }
  .modern-badge-green {
    background: rgba(40, 199, 111, 0.15);
    color: #28c76f;
  }
  .modern-badge-blue {
    background: rgba(0, 123, 255, 0.15);
    color: #007bff;
  }
  .modern-badge-yellow {
    background: rgba(255, 159, 67, 0.15);
    color: #ff9f43;
  }
  .modern-badge-red {
    background: rgba(234, 84, 85, 0.15);
    color: #ea5455;
  }
</style>

@isset($results)
@section('page-script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const genderFilter = document.getElementById('genderFilter');
    const streamFilter = document.getElementById('streamFilter');
    const performanceFilter = document.getElementById('performanceFilter');
    const studentSearch = document.getElementById('studentSearch');
    const applyFiltersBtn = document.getElementById('applyFilters');
    const resetFiltersBtn = document.getElementById('resetFilters');
    const rows = document.querySelectorAll('.student-row');

    // Calculate initial statistics
    updateStatistics();

    // Apply filters
    function applyFilters() {
        const genderValue = genderFilter.value.toLowerCase();
        const streamValue = streamFilter.value.toLowerCase();
        const performanceValue = performanceFilter.value;
        const searchValue = studentSearch.value.toLowerCase().trim();

        let visibleCount = 0;

        rows.forEach(row => {
            const studentName = row.dataset.studentName || '';
            const gender = (row.dataset.gender || '').toLowerCase();
            const stream = (row.dataset.stream || '').toLowerCase();
            const average = parseFloat(row.dataset.average) || 0;

            let showRow = true;

            // Gender filter
            if (genderValue && gender !== genderValue) {
                showRow = false;
            }

            // Stream filter
            if (streamValue && stream !== streamValue) {
                showRow = false;
            }

            // Performance filter
            if (performanceValue) {
                if (performanceValue === 'excellent' && (average < 80 || average > 100)) {
                    showRow = false;
                } else if (performanceValue === 'good' && (average < 60 || average >= 80)) {
                    showRow = false;
                } else if (performanceValue === 'average' && (average < 40 || average >= 60)) {
                    showRow = false;
                } else if (performanceValue === 'poor' && average >= 40) {
                    showRow = false;
                }
            }

            // Search filter
            if (searchValue && !studentName.includes(searchValue)) {
                showRow = false;
            }

            // Show/hide row
            if (showRow) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Update displayed count
        document.getElementById('displayedCount').textContent = visibleCount;

        // Update statistics based on visible rows
        updateStatistics();
    }

    // Update statistics
    function updateStatistics() {
        let excellentCount = 0;
        let goodCount = 0;
        let averageCount = 0;
        let poorCount = 0;

        rows.forEach(row => {
            if (row.style.display !== 'none') {
                const average = parseFloat(row.dataset.average) || 0;

                if (average >= 80) {
                    excellentCount++;
                } else if (average >= 60) {
                    goodCount++;
                } else if (average >= 40) {
                    averageCount++;
                } else {
                    poorCount++;
                }
            }
        });

        document.getElementById('excellentCount').textContent = excellentCount;
        document.getElementById('goodCount').textContent = goodCount;
        document.getElementById('averageCount').textContent = averageCount;
        document.getElementById('poorCount').textContent = poorCount;
    }

    // Reset filters
    function resetFilters() {
        genderFilter.value = '';
        streamFilter.value = '';
        performanceFilter.value = '';
        studentSearch.value = '';
        applyFilters();
    }

    // Event listeners
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', applyFilters);
    }

    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener('click', resetFilters);
    }

    // Live search
    if (studentSearch) {
        studentSearch.addEventListener('input', function() {
            applyFilters();
        });
    }

    // Auto-apply on filter change
    [genderFilter, streamFilter, performanceFilter].forEach(filter => {
        if (filter) {
            filter.addEventListener('change', applyFilters);
        }
    });
});
</script>
@endsection
@endisset

@endsection
