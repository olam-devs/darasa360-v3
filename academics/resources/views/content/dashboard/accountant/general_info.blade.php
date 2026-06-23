@extends('layouts.modernLayout', [
    'pageTitle' => 'Report Detail'
])

@section('title', 'General Info')

@section('content')
<div class="container">
    <div class="row"> <!-- Remove justify-content-center -->
        <div class="col-md-8 col-lg-6"> <!-- Same width, but positioned at start -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Report Detail</h5>
                </div>

                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('accountant.report.save') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Phone *</label>
                            <input type="text" class="form-control" name="phone" value="{{ $report->phone ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email *</label>
                            <input type="email" class="form-control" name="email" value="{{ $report->email ?? '' }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <input type="text" class="form-control" name="address" value="{{ $report->address ?? '' }}">
                        </div>

                        <button type="submit" class="btn btn-primary">Save</button>
                        @if($report)
                        <a href="{{ route('accountant.report.delete') }}" class="btn btn-outline-danger" onclick="return confirm('Delete report detail?')">Delete</a>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection