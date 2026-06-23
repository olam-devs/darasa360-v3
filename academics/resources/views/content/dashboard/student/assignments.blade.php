@extends('layouts.modernLayout', [
    'pageTitle' => 'Assignments'
])

@section('title', 'Assignments')

@section('content')
<div class="container mt-4">
    <h4 class="fw-bold mb-4">Class Assignments</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($assignments->isEmpty())
        <div class="alert alert-info">No assignments available for your class.</div>
    @else
        <div class="table-responsive">
            <table class="table table-bordered table-hover">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Subject</th>
                        <th>Date Created</th>
                        <th>Download</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignments as $index => $assignment)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $assignment->title }}</td>
                            <td>{{ $assignment->subject->name ?? 'N/A' }}</td>
                            <td>{{ $assignment->created_at ? $assignment->created_at->format('d M Y') : 'N/A' }}</td>
                            <td>
                                @if($assignment->file_path)
                                <a href="{{ route('assignments.download', $assignment->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                  <i class="bx bx-download"></i> Download
                                </a>
                                @else
                                    <span class="text-muted">No file</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
