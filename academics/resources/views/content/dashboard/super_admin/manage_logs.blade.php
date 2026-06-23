@extends('layouts.modernLayout', [
    'pageTitle' => 'System Logs'
])

@section('title', 'System Logs')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">System Logs</h4>
    </div>

    {{-- Success message --}}
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    {{-- Error message --}}
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

    <form method="GET" action="{{ route('admin.manageLogs') }}" class="mb-4 row g-3">
      {{-- School Filter --}}
      <div class="col-md-4">
        <select name="school_id" class="form-select">
          <option value="">-- Filter by School --</option>
          @foreach ($schools as $schoolOption)
            <option value="{{ $schoolOption->id }}" {{ request('school_id') == $schoolOption->id ? 'selected' : '' }}>
              {{ $schoolOption->name }}
            </option>
          @endforeach
        </select>
      </div>

      <div class="col-md-4 d-flex">
        <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
        <a href="{{ route('admin.manageLogs') }}" class="btn btn-secondary">Reset</a>
      </div>
    </form>

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>User</th>
              {{-- <th>Name</th> --}}
              <th>Registration No</th>
              <th>role</th>
              <th>School code</th>
              <th>Method</th>
              <th>URL</th>
              <th>IP</th>
              <th>Message</th>
              <th>Created At</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse ($logs as $index => $log)
              <tr>
                <td>{{ $logs->firstItem() + $index }}</td>
                {{-- <td>{{ $log->user->name ?? $log->user->username ?? 'System' }}</td> --}}
                <td>{{ $log->user_name }}</td>
                <td>{{ $log->registration_no }}</td>
                <td>{{ $log->role }}</td>
                <td>{{ $log->school_code }}</td>
                <td><span class="badge bg-label-info">{{ $log->method }}</span></td>
                <td>{{ $log->url }}</td>
                <td>{{ $log->ip }}</td>
                <td>{{ $log->message ?? '-' }}</td>
                <td>{{ $log->created_at?->format('d M Y H:i') ?? 'N/A' }}</td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center text-muted">No logs found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      {{-- Pagination with Bootstrap 5 style --}}
      <div class="mt-3 mx-3">
        {{ $logs->appends(request()->query())->links('pagination::bootstrap-5') }}
      </div>
    </div>
  </div>
</div>
@endsection
