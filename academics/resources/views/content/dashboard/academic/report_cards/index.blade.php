@extends('layouts.modernLayout', [
    'pageTitle' => 'Report Card Configurations'
])

@section('title', 'Report Card Configurations')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
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

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4 class="mb-0"><i class="bx bx-file me-2"></i>Report Card Configurations</h4>
      <a href="{{ route('report-cards.create') }}" class="btn btn-primary">
        <i class="bx bx-plus me-1"></i>Create New Configuration
      </a>
    </div>

    <div class="card-body">
      @if(count($configurations) > 0)
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Class</th>
              <th>Term</th>
              <th>Academic Year</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($configurations as $config)
            <tr>
              <td>
                <strong>{{ $config->name }}</strong>
                @if($config->description)
                <br><small class="text-muted">{{ Str::limit($config->description, 50) }}</small>
                @endif
              </td>
              <td>{{ $config->class->name ?? 'N/A' }}</td>
              <td>{{ $config->term }}</td>
              <td>{{ $config->academic_year }}</td>
              <td>
                @if($config->status === 'active')
                <span class="badge bg-success">Active</span>
                @elseif($config->status === 'published')
                <span class="badge bg-info">Published</span>
                @else
                <span class="badge bg-secondary">Draft</span>
                @endif
              </td>
              <td>
                <div class="d-flex gap-2">
                  <a href="{{ route('report-cards.show', $config->id) }}" class="btn btn-sm btn-info" title="View Details">
                    <i class="bx bx-show"></i>
                  </a>
                  <form action="{{ route('report-cards.destroy', $config->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this configuration?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <div class="text-center py-5">
        <i class="bx bx-file" style="font-size: 4rem; color: #ccc;"></i>
        <p class="text-muted mt-3">No report card configurations yet.</p>
        <a href="{{ route('report-cards.create') }}" class="btn btn-primary">
          <i class="bx bx-plus me-1"></i>Create Your First Configuration
        </a>
      </div>
      @endif
    </div>
  </div>
</div>

<style>
@endsection

@push('page-style')
<style>
  @media (max-width: 768px) {
    .table {
      font-size: 0.875rem;
    }
    .btn-sm {
      padding: 0.25rem 0.5rem;
      font-size: 0.75rem;
    }
  }

  /* Dark Theme Support */
  [data-bs-theme="dark"] .card {
    background-color: #2b2c40 !important;
    border-color: #3b3b55 !important;
  }

  [data-bs-theme="dark"] .card-header {
    background-color: #373851 !important;
    border-bottom-color: #3b3b55 !important;
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .card-body {
    background-color: #2b2c40 !important;
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .table {
    color: #d4d4e8 !important;
    background-color: transparent !important;
  }

  [data-bs-theme="dark"] .table-light {
    background-color: #373851 !important;
    color: #d4d4e8 !important;
  }

  [data-bs-theme="dark"] .table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(255, 255, 255, 0.02) !important;
  }

  [data-bs-theme="dark"] .table-hover tbody tr:hover {
    background-color: rgba(255, 255, 255, 0.05) !important;
  }

  [data-bs-theme="dark"] .table td,
  [data-bs-theme="dark"] .table th {
    border-color: #3b3b55 !important;
  }

  [data-bs-theme="dark"] .text-muted {
    color: #a1a3b7 !important;
  }

  [data-bs-theme="dark"] strong {
    color: #e7e7f0 !important;
  }

  [data-bs-theme="dark"] .avatar-initial {
    background-color: rgba(115, 103, 240, 0.2) !important;
    color: #a8aaff !important;
  }

  [data-bs-theme="dark"] .alert-info {
    background-color: rgba(0, 207, 232, 0.15) !important;
    border-color: #00cfe8 !important;
    color: #60dfed !important;
  }

  [data-bs-theme="dark"] .btn-outline-primary {
    border-color: #7367f0 !important;
    color: #7367f0 !important;
  }

  [data-bs-theme="dark"] .btn-outline-primary:hover {
    background-color: #7367f0 !important;
    color: white !important;
  }

  [data-bs-theme="dark"] .btn-secondary {
    background-color: #3b3b55 !important;
    border-color: #3b3b55 !important;
  }
</style>
@endpush
