@extends('layouts.modernLayout', [
    'pageTitle' => 'Attendance'
])

@section('title', 'My Attendance History')

@section('content')
<div class="container">
  <h4 class="fw-bold py-3 mb-4">My Attendance History</h4>

  @if(session('error'))
    <div class="alert alert-danger">{{ session('error') }}</div>
  @endif

  @if($attendanceEntries->isEmpty())
    <div class="alert alert-info">No attendance records found.</div>
    @else
    <div class="mb-3">
      <h5>Attendance Summary</h5>
      <p>
        Total Days: <strong>{{ $attendanceEntries->total() }}</strong> <br>
        Present Days: <strong>{{ $presentCount }}</strong> <br>
        Absent Days: <strong>{{ $absentCount }}</strong> <br>
        Attendance Percentage:
        <span class="fw-bold text-{{ $presentPercentage >= 75 ? 'success' : 'danger' }}">
          {{ $presentPercentage }}%
        </span>
      </p>
    </div>

    <div class="card">
      <div class="table-responsive">
        <table class="table table-bordered mb-0">
          <thead>
            <tr>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            @foreach($attendanceEntries as $entry)
              <tr>
                <td>{{ \Carbon\Carbon::parse($entry->date)->format('d M Y') }}</td>
                <td>
                  <span class="badge bg-{{ $entry->status == 'present' ? 'success' : ($entry->status == 'late' ? 'warning' : 'danger') }}">
                    {{ ucfirst($entry->status) }}
                  </span>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    {{-- Pagination links --}}
    <div class="mt-3">
      {{ $attendanceEntries->links() }}
    </div>
  @endif

</div>
@endsection
