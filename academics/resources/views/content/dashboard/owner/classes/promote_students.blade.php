@extends('layouts.modernLayout', [
    'pageTitle' => 'Promote Students'
])

@section('title', 'Promote Students')

@section('content')

@if (session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if (session('error'))
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    {{ session('error') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

@if ($errors->any())
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <ul class="mb-0">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
@endif

<div class="card">
  <h5 class="card-header">Promote Students</h5>
  <div class="card-body">
    <form method="GET" action="{{ route('owner.promotions.index') }}">
      <div class="row g-3 align-items-center">
        <div class="col-md-5">
          <label for="from_class" class="form-label">From Class</label>
          <select name="from_class_id" id="from_class" class="form-select" required>
            <option value="">-- Select Class --</option>
            @foreach ($classes as $class)
              <option value="{{ $class->id }}" {{ request('from_class_id') == $class->id ? 'selected' : '' }}>
                {{ $class->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3 align-self-end">
          <button type="submit" class="btn btn-primary">Fetch Students</button>
        </div>
      </div>
    </form>
  </div>
</div>

@if (isset($students) && count($students) > 0)
  <div class="card mt-4">
    <h5 class="card-header">Promote Students from "{{ $selectedClass->name }}"</h5>
    <div class="card-body">
      <form method="POST" action="{{ route('owner.promotions.promote') }}">
        @csrf

        <input type="hidden" name="from_class_id" value="{{ $selectedClass->id }}">

        <div class="mb-3">
          <label for="to_class" class="form-label">To Class</label>
          <select name="to_class_id" id="to_class" class="form-select" required>
            <option value="">-- Select Destination Class --</option>
            @foreach ($classes as $class)
              @if ($class->id != $selectedClass->id)
                <option value="{{ $class->id }}" {{ old('to_class_id') == $class->id ? 'selected' : '' }}>
                  {{ $class->name }}
                </option>
              @endif
            @endforeach
          </select>
          @error('to_class_id')
            <small class="text-danger">{{ $message }}</small>
          @enderror
        </div>

        <table class="table table-striped mt-3">
          <thead>
            <tr>
              <th><input type="checkbox" id="select-all"></th>
              <th>Reg No</th>
              <th>Name</th>
              <th>Stream</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($students as $student)
              <tr>
                <td>
                  <input type="checkbox" name="student_ids[]" value="{{ $student->id }}">
                </td>
                <td>{{ $student->registration_no }}</td>
                <td>{{ $student->first_name }}</td>
                <td>{{ $student->stream_name ?? '—' }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>

        <div class="mt-3">
          <button type="submit" class="btn btn-success">Promote Selected Students</button>
        </div>
      </form>
    </div>
  </div>
@elseif(request('from_class_id'))
  <div class="alert alert-warning mt-3">No students found in the selected class.</div>
@endif
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const selectAllCheckbox = document.getElementById('select-all');

    const studentCheckboxes = document.querySelectorAll('input[name="student_ids[]"]');

    selectAllCheckbox?.addEventListener('change', () => {
      const studentCheckboxes = document.querySelectorAll('input[name="student_ids[]"]');
      studentCheckboxes.forEach(cb => {
        cb.checked = selectAllCheckbox.checked;
      });
    });

    function updateSelectAllState() {
      const studentCheckboxes = document.querySelectorAll('input[name="student_ids[]"]');
      const allChecked = Array.from(studentCheckboxes).length > 0 && Array.from(studentCheckboxes).every(chk => chk.checked);
      const someChecked = Array.from(studentCheckboxes).some(chk => chk.checked);

      selectAllCheckbox.checked = allChecked;
      selectAllCheckbox.indeterminate = someChecked && !allChecked;
    }

    function attachStudentCheckboxListeners() {
      const studentCheckboxes = document.querySelectorAll('input[name="student_ids[]"]');
      studentCheckboxes.forEach(cb => {
        cb.addEventListener('change', () => {
          console.log('Checkbox', cb.value, 'changed. Checked:', cb.checked);
          updateSelectAllState();
        });
      });
    }

    attachStudentCheckboxListeners();
    updateSelectAllState();
  });



  </script>
@endsection
