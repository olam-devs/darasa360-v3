@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Attendance'
])
@section('title', 'Manage Attendance')

@section('content')
<div class="row">
  <div class="col-12">
    <h4 class="fw-bold mb-4">Manage Attendance</h4>

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

    <form method="GET" action="{{ route('owner.daily-attendance') }}" class="row g-3 mb-4">
      <div class="col-md-4">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search by title or description">
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
              <th>Description</th>
              <th>From Date</th>
              <th>To Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($attendanceTypes as $attendance)
              <tr>
                <td>{{ $loop->iteration + ($attendanceTypes->currentPage() - 1) * $attendanceTypes->perPage() }}</td>
                <td>{{ $attendance->title }}</td>
                <td>{{ $attendance->description }}</td>
                <td>{{ $attendance->from_date }}</td>
                <td>{{ $attendance->to_date }}</td>
                <td>
                  <a href="{{ route('owner.attendance.download', $attendance->id) }}" class="btn btn-sm btn-outline-success me-1" title="Download Excel Template">
                    <i class="bx bx-download"></i>
                  </a>
                  <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#attendanceModal_{{ $attendance->id }}">
                    <i class="bx bx-list-check"></i> Take Attendance
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center">No attendance types found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      @if($attendanceTypes instanceof \Illuminate\Pagination\LengthAwarePaginator)
      <div class="mt-3 mx-3">
        {{ $attendanceTypes->links('pagination::bootstrap-5') }}
      </div>
    @endif

    </div>

    {{-- MODALS --}}
    @foreach($attendanceTypes as $attendance)
      <div class="modal fade" id="attendanceModal_{{ $attendance->id }}" tabindex="-1" aria-labelledby="attendanceModalLabel_{{ $attendance->id }}" aria-hidden="true">
        <div class="modal-dialog modal-xl">
          <form method="POST" action="{{ route('teacher.submitAttendance') }}">
            @csrf
            <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="attendanceModalLabel_{{ $attendance->id }}">Take Attendance: {{ $attendance->title }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              </div>
              <div class="modal-body">

  <p><em>Taking attendance for today: {{ \Carbon\Carbon::now()->format('Y-m-d') }}</em></p>


                @if($students->isEmpty())
                  <p>No students found for this class.</p>
                @else
                  <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                      <thead class="table-light">
                        <tr>
                          <th>#</th>
                           <th>Present</th>
                          <th>First Name</th>
                          <th>Middle Name</th>
                          <th>Last Name</th>
                          <th>Registration No</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($students as $index => $student)
                          <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="text-center">
                              <input class="form-check-input" type="checkbox" name="present_students[]" value="{{ $student->id }}" id="student_{{ $attendance->id }}_{{ $student->id }}" checked>
                            </td>
                            <td>{{ $student->first_name ?? '-' }}</td>
                            <td>{{ $student->middle_name ?? '-' }}</td>
                            <td>{{ $student->last_name ?? '-' }}</td>
                            <td>{{ $student->registration_no }}</td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                @endif
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Submit Attendance</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    @endforeach

  </div>
</div>
@endsection
