@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Teacher Assignments'
])
@section('title', 'Manage Teacher Assignments')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage Teacher Assignments</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignModal">
        <i class="bx bx-plus"></i> Assign Class & Subjects
      </button>
    </div>

    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show">{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger alert-dismissible fade show">
        <ul class="mb-0">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    @endif

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-bordered">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Teacher</th>
              <th>Registration No</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Subjects Grouped by Class</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($teachers as $teacher)
              <tr>
                <td>{{ $loop->iteration + ($teachers->currentPage() - 1) * $teachers->perPage() }}</td>
                <td>{{ $teacher->name }}</td>
                <td>{{ $teacher->user->registration_no }}</td>
                <td>{{ $teacher->user->email ?? 'N/A' }}</td>
                <td>{{ $teacher->phone ?? 'N/A' }}</td>
                <td>
                  @php
                    $grouped = [];
                    foreach ($teacher->subjects as $subject) {
                        $class = $classes->firstWhere('id', $subject->pivot->class_id);
                        if ($class) $grouped[$class->name][] = $subject->name;
                    }
                  @endphp
                  @if(count($grouped))
                    <ul class="list-unstyled">
                      @foreach($grouped as $class => $subs)
                        <li><strong>{{ $class }}:</strong> {{ implode(', ', array_unique($subs)) }}</li>
                      @endforeach
                    </ul>
                  @else
                    <em>No subjects assigned</em>
                  @endif
                </td>
                <td>
                  <button type="button" class="btn btn-sm btn-outline-primary"
                    data-bs-toggle="modal" data-bs-target="#assignModal"
                    onclick="populateEdit({{ $teacher->id }})">Edit</button>
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center">No teachers found</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="mt-3 mx-3">{{ $teachers->links('pagination::bootstrap-5') }}</div>
    </div>
  </div>
</div>

<!-- Assignment Modal -->
<div class="modal fade" id="assignModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('teachers.assign.store') }}">
      @csrf
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Assign Class & Subjects to Teacher</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <!-- Teacher select (only when adding, not editing) -->
          <div class="mb-3">
            <label for="modal_teacher_id" class="form-label">Select Teacher</label>
            <select name="teacher_id" id="modal_teacher_id" class="form-select" required>
              <option value="">-- Select Teacher --</option>
              @foreach($teachers as $teacher)
                <option value="{{ $teacher->id }}">{{ $teacher->name }} ({{ $teacher->user->registration_no }})</option>
              @endforeach
            </select>
          </div>

          <div id="class-subjects-wrapper"></div>
          <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addClassBtn">
            <i class="bx bx-plus"></i> Add Class
          </button>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Assignment</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const classesData = @json($classes);
  const wrapper = document.getElementById('class-subjects-wrapper');
  const addBtn = document.getElementById('addClassBtn');
  const modalTeacherSelect = document.getElementById('modal_teacher_id');

  function createBlock(selectedClassId = null, selectedSubjects = []) {
    const block = document.createElement('div');
    block.classList.add('mb-3', 'class-subject-block', 'border', 'p-3', 'rounded', 'position-relative');

    const classSelect = document.createElement('select');
    classSelect.name = 'class_ids[]';
    classSelect.classList.add('form-select', 'mb-2');
    classSelect.required = true;

    const defaultOption = document.createElement('option');
    defaultOption.value = '';
    defaultOption.text = '-- Select Class --';
    classSelect.appendChild(defaultOption);

    classesData.forEach(cls => {
      const opt = document.createElement('option');
      opt.value = cls.id;
      opt.text = cls.name;
      if(cls.id == selectedClassId) opt.selected = true;
      classSelect.appendChild(opt);
    });

    const subjectsContainer = document.createElement('div');
    subjectsContainer.classList.add('subjects-checkboxes');

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.classList.add('btn-close', 'position-absolute');
    removeBtn.style.top = '0.5rem';
    removeBtn.style.right = '0.5rem';
    removeBtn.addEventListener('click', () => block.remove());

    block.appendChild(removeBtn);
    block.appendChild(classSelect);
    block.appendChild(subjectsContainer);
    wrapper.appendChild(block);

    function loadSubjects(classId, preSelected = []) {
      subjectsContainer.innerHTML = '';
      if(!classId) return;

      fetch(`/teachers/class/${classId}/subjects`)
        .then(res => res.json())
        .then(data => {
          data.forEach(sub => {
            const id = `subject_${classId}_${sub.id}_${Math.floor(Math.random()*10000)}`;
            const div = document.createElement('div');
            div.classList.add('form-check', 'form-check-inline');

            const cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.name = `subject_ids[${classId}][]`;
            cb.value = sub.id;
            cb.id = id;
            cb.classList.add('form-check-input');
            if(preSelected.includes(sub.id)) cb.checked = true;

            const label = document.createElement('label');
            label.classList.add('form-check-label');
            label.htmlFor = id;
            label.innerText = sub.name;

            div.appendChild(cb);
            div.appendChild(label);
            subjectsContainer.appendChild(div);
          });
        });
    }

    classSelect.addEventListener('change', function () {
      loadSubjects(this.value);
    });

    if(selectedClassId) loadSubjects(selectedClassId, selectedSubjects);
  }

  addBtn.addEventListener('click', () => createBlock());

  window.populateEdit = function(teacherId) {
    modalTeacherSelect.value = teacherId;
    wrapper.innerHTML = '';

    fetch(`/teachers/edit/${teacherId}/json`)
      .then(res => res.json())
      .then(data => {
        Object.keys(data).forEach(classId => {
          createBlock(classId, data[classId]);
        });
      });
  };
});
</script>
@endsection
