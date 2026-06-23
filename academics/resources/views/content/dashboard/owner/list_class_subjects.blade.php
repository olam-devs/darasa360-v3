@extends('layouts.modernLayout', [
    'pageTitle' => 'Subjects Assigned to Classes'
])

@section('title', 'Class Subjects List')

@section('content')
<div class="container">
    <h3 class="mb-4">Subjects Assigned to Classes</h3>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>Class Name</th>
                <th>Subjects</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($classes as $classroom)
                <tr>
                    <td>{{ $classroom->name }}</td>
                    <td>
                        @if($classroom->subjects->count() > 0)
                            <ul class="mb-0">
                                @foreach($classroom->subjects as $subject)
                                    <li>{{ $subject->name }}</li>
                                @endforeach
                            </ul>
                        @else
                            <em>No subjects assigned</em>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('classes.subjects.form', $classroom->id) }}" class="btn btn-sm btn-primary">
                            Edit Assignments
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" class="text-center">No classes found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
