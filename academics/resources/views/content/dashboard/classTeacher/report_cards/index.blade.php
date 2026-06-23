@extends('layouts.modernLayout', [
    'pageTitle' => 'My Class Report Cards'
])

@section('title', 'My Class Report Cards')

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
    <div class="card-header">
      <h4 class="mb-0"><i class="bx bx-file me-2"></i>My Class Report Cards</h4>
      <p class="text-muted mb-0 mt-2">View report cards for students in your assigned class</p>
    </div>

    <div class="card-body">
      @if(isset($configurations) && count($configurations) > 0)
      <div class="table-responsive">
        <table class="table table-striped table-hover">
          <thead class="table-light">
            <tr>
              <th>Name</th>
              <th>Term</th>
              <th>Academic Year</th>
              <th>Students</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($configurations as $config)
            <tr>
              <td><strong>{{ $config->name }}</strong></td>
              <td>{{ $config->term }}</td>
              <td>{{ $config->academic_year }}</td>
              <td>
                <span class="badge bg-primary">{{ $config->class->name }}</span>
              </td>
              <td>
                <a href="{{ route('classTeacher.reportCards.show', $config->id) }}"
                   class="btn btn-sm btn-info">
                  <i class="bx bx-show me-1"></i>View Reports
                </a>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      @else
      <div class="alert alert-info">
        <i class="bx bx-info-circle me-2"></i>
        No report cards have been published for your class yet.
      </div>
      @endif
    </div>
  </div>
</div>
@endsection
