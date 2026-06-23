@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Attendance Types'
])

@section('title', 'Manage Attendance')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage Attendance Types</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAttendanceModal">
        <i class="bx bx-plus"></i> Add New Attendance
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    <form method="GET" action="{{ url()->current() }}" class="row g-3 mb-4">
      <div class="col-md-4">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by title">
      </div>
      <div class="col-md-2">
        <button class="btn btn-primary w-100" type="submit">
          <i class="bx bx-search"></i> Search
        </button>
      </div>
    </form>

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>From Date</th>
              <th>Due Date</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($attendanceTypes as $attendance)
              <tr>
                <td>{{ $loop->iteration + ($attendanceTypes->currentPage() - 1) * $attendanceTypes->perPage() }}</td>
                <td><strong>{{ $attendance->title }}</strong></td>
                <td>{{ \Carbon\Carbon::parse($attendance->from_date)->format('d M Y') }}</td>
                <td>{{ \Carbon\Carbon::parse($attendance->to_date)->format('d M Y') }}</td>
                <td>
                  @if(now()->between($attendance->from_date, $attendance->to_date))
                    <span class="badge bg-success">Ongoing</span>
                  @elseif(now()->lt($attendance->from_date))
                    <span class="badge bg-warning">Upcoming</span>
                  @else
                    <span class="badge bg-secondary">Closed</span>
                  @endif
                </td>
                <td>
                  <!-- Download Excel Template Button -->
                  <a
                    href="{{ route('owner.attendance.download', $attendance->id) }}"
                    class="btn btn-sm btn-outline-success me-1"
                    title="Download Excel Template"
                  >
                    <i class="bx bx-download"></i>
                  </a>

                  <!-- Edit Attendance Button -->
                  <button
                    class="btn btn-sm btn-outline-warning me-1"
                    data-bs-toggle="modal"
                    data-bs-target="#editAttendanceModal_{{ $attendance->id }}"
                  >
                    <i class="bx bx-edit"></i>
                  </button>

                  <!-- Delete Attendance Form -->
                  <form action="{{ route('owner.attendance.delete', $attendance->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this attendance entry?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn btn-sm btn-outline-danger" title="Delete">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>

              </tr>
            @empty
              <tr>
                <td colspan="7" class="text-center">No attendance entries found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3 mx-3">
        {{ $attendanceTypes->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>

<!-- Add Attendance Modal -->
<div class="modal fade" id="addAttendanceModal" tabindex="-1" aria-labelledby="addAttendanceModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('owner.attendance.create') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="addAttendanceModalLabel">Add New Attendance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">

          <div class="mb-3">
            <label for="title" class="form-label">Title</label>
            <input
              type="text"
              id="title"
              name="title"
              class="form-control"
              required
            >
          </div>


          <div class="mb-3">
            <label for="class_id" class="form-label">Class</label>
            <select
              id="class_id"
              name="class_id"
              class="form-select"
              required
            >
              <option value="">Select Class</option>
              @foreach($classes as $class)
                <option value="{{ $class->id }}">{{ $class->name }}</option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label for="from_date" class="form-label">From Date</label>
            <input
              type="date"
              id="from_date"
              name="from_date"
              class="form-control"
              required
            >
          </div>

          <div class="mb-3">
            <label for="due_date" class="form-label">Due Date</label>
            <input
              type="date"
              id="due_date"
              name="due_date"
              class="form-control"
              required
            >
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Attendance</button>
        </div>
      </div>
    </form>
  </div>
</div>

@foreach($attendanceTypes as $attendance)
<div class="modal fade" id="editAttendanceModal_{{ $attendance->id }}" tabindex="-1" aria-labelledby="editAttendanceModalLabel_{{ $attendance->id }}" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('owner.attendance.update', $attendance->id) }}">
      @csrf
      @method('PUT')
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Edit Attendance</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3">
            <label for="title_{{ $attendance->id }}" class="form-label">Title</label>
            <input type="text" name="title" id="title_{{ $attendance->id }}" class="form-control" value="{{ $attendance->title }}" required>
          </div>
          <div class="mb-3">
            <label for="class_id" class="form-label">Class</label>
            <select name="class_id" class="form-select" required>
              @foreach($classes as $class)
                <option value="{{ $class->id }}" {{ $class->id == $attendance->class_id ? 'selected' : '' }}>
                  {{ $class->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div class="mb-3">
            <label for="from_date_{{ $attendance->id }}" class="form-label">From Date</label>
            <input type="date" name="from_date" id="from_date_{{ $attendance->id }}" class="form-control" value="{{ $attendance->from_date }}" required>
          </div>
          <div class="mb-3">
            <label for="due_date_{{ $attendance->id }}" class="form-label">Due Date</label>
            <input type="date" name="due_date" id="due_date_{{ $attendance->id }}" class="form-control" value="{{ $attendance->to_date }}" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </div>
    </form>
  </div>
</div>
@endforeach

@endsection
