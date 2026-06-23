@extends('layouts.modernLayout', [
    'pageTitle' => 'Notes'
])

@section('title', 'Notes')

@section('content')
<div class="container mt-4">
    <h4 class="fw-bold mb-4">Class Notes</h4>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($notes->isEmpty())
        <div class="alert alert-info">No notes available for your class.</div>
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
                    @foreach($notes as $index => $note)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $note->title }}</td>
                            <td>{{ $note->subject->name ?? 'N/A' }}</td>
                            <td>{{ $note->created_at ? $note->created_at->format('d M Y') : 'N/A' }}</td>
                            <td>
                                @if($note->file_path)
                                    <a href="{{ route('notes.download', $note->id) }}" class="btn btn-sm btn-outline-primary" target="_blank">
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
