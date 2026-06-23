@extends('layouts.modernLayout', [
    'pageTitle' => 'Assign Subjects to Class: {{ $classroom->name }}'
])
@section('title', 'Assign Subjects to Class')

@section('content')
<div class="row">
  <div class="col-12">
    <h4 class="fw-bold mb-4">Assign Subjects to Class: {{ $classroom->name }}</h4>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
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

    <form action="{{ route('classes.subjects.save', $classroom->id) }}" method="POST">
      @csrf

      <div class="mb-3">
        <label for="subjects" class="form-label">Select Subjects</label>
        <select name="subjects[]" id="subjects" class="form-select" multiple size="10" required>
          @foreach($allSubjects as $subject)
            <option value="{{ $subject->id }}"
              {{ $classroom->subjects->contains($subject->id) ? 'selected' : '' }}>
              {{ $subject->name }} ({{ $subject->code ?? '-' }})
            </option>
          @endforeach
        </select>
        <small class="form-text text-muted">Hold Ctrl (Cmd on Mac) to select multiple subjects.</small>
      </div>

      <button type="submit" class="btn btn-primary">Save Assigned Subjects</button>
    </form>
  </div>
</div>
@endsection
