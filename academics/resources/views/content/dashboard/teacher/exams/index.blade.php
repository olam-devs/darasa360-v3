@extends('layouts.modernLayout', [
    'pageTitle' => 'Exams'
])

@section('title', 'Manage Exams')

@section('content')
<div class="container">
  <h4 class="fw-bold py-3">Manage Exams</h4>

  {{-- Success message --}}
  @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      {{ session('success') }}
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  @endif

  {{-- Add Exam Button --}}
  <div class="mb-3">
    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createExamModal">
      <i class="bx bx-plus"></i> Add Exam
    </a>
  </div>

  {{-- Exam Table --}}
  <div class="card">
    <div class="table-responsive text-nowrap">
      <table class="table table-bordered">
        <thead class="table-light">
          <tr>
            <th>Name</th>
            <th>Term</th>
            <th>Start Date</th>
            <th>End Date</th>
            <th>Classes</th>
            <th>Created</th>
            <th>Actions</th> <!-- Added -->
          </tr>
        </thead>
        <tbody>
          @forelse($exams as $exam)
            <tr>
              <td>{{ $exam->name }}</td>
              <td>{{ $exam->term }}</td>
              <td>{{ $exam->start_date }}</td>
              <td>{{ $exam->end_date }}</td>
              <td>
                @foreach($exam->classes as $class)
                  <span class="badge bg-info">{{ $class->name }}</span>
                @endforeach
              </td>
              <td>{{ $exam->created_at->format('d M Y') }}</td>
              <td>
                {{-- View Icon --}}
                {{-- <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#viewExamModal{{ $exam->id }}">
                  <i class="bx bx-show"></i>
                </button> --}}

                {{-- Delete Form --}}
                <form action="{{ route('exams.destroy', $exam->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure? This will delete all associated data.')">
                  @csrf
                  @method('DELETE')
                  <button class="btn btn-sm btn-danger">
                    <i class="bx bx-trash"></i>
                  </button>
                </form>
              </td>
            </tr>

            {{-- View Modal --}}
            {{-- <div class="modal fade" id="viewExamModal{{ $exam->id }}" tabindex="-1" aria-labelledby="viewExamModalLabel{{ $exam->id }}" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <form method="GET" action="{{ route('exams.show', $exam->id) }}">
                    <div class="modal-header">
                      <h5 class="modal-title">Assign/View Marks - {{ $exam->name }}</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                      <div class="mb-3">
                        <label class="form-label">Select Class</label>
                        <select name="class_id" class="form-select" required>
                          <option value="">Choose a class</option>
                          @foreach($exam->classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button class="btn btn-primary" type="submit">Continue</button>
                    </div>
                  </form>
                </div>
              </div>
            </div> --}}

          @empty
            <tr>
              <td colspan="7" class="text-center">No exams found.</td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

{{-- Exam Creation Modal --}}
<div class="modal fade @if ($errors->any()) show d-block @endif" id="createExamModal" tabindex="-1" aria-labelledby="createExamModalLabel" aria-hidden="true" style="@if ($errors->any()) display:block; background-color: rgba(0,0,0,0.5); @endif">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form action="{{ route('exams.store') }}" method="POST">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="createExamModalLabel">Add Exam</h5>
          <button type="button" class="btn-close" onclick="window.location='{{ route('exams.index') }}'"></button>
        </div>
        <div class="modal-body">
          {{-- Validation Errors --}}
          @if($errors->any())
            <div class="alert alert-danger">
              <ul class="mb-0">
                @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
                @endforeach
              </ul>
            </div>
          @endif

          <div class="mb-3">
            <label for="name" class="form-label">Exam Name</label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
          </div>

          <div class="mb-3">
            <label for="term" class="form-label">Term</label>
            <select name="term" class="form-select" required>
              <option value="">Select Term</option>
              <option value="Term 1" {{ old('term') == 'Term 1' ? 'selected' : '' }}>Term 1</option>
              <option value="Term 2" {{ old('term') == 'Term 2' ? 'selected' : '' }}>Term 2</option>
              <option value="Term 3" {{ old('term') == 'Term 3' ? 'selected' : '' }}>Term 3</option>
            </select>
          </div>

          <div class="mb-3">
            <label for="start_date" class="form-label">Start Date</label>
            <input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required>
          </div>

          <div class="mb-3">
            <label for="end_date" class="form-label">End Date</label>
            <input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Assign to Classes</label>
            <div class="row">
              @foreach($classes as $class)
                <div class="col-md-4">
                  <div class="form-check">
                    <input type="checkbox" name="classes[]" value="{{ $class->id }}" class="form-check-input" id="class_{{ $class->id }}"
                      {{ in_array($class->id, old('classes', [])) ? 'checked' : '' }}>
                    <label for="class_{{ $class->id }}" class="form-check-label">{{ $class->name }}</label>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="window.location='{{ route('exams.index') }}'">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Exam</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
