@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Grades'
])
@section('title', 'Manage Grades')

@section('content')
<div class="row">
  <div class="col-12">
    <h4 class="fw-bold mb-4">Manage Grades</h4>

    {{-- Success Message --}}
    @if(session('success'))
      <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    {{-- Error Message --}}
    @if(session('error'))
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    @endif

    {{-- Grade Types Table --}}
    <div class="card mb-4">
      <div class="card-header fw-bold">Grade Types</div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover" id="gradeTypesTable">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Grade Type</th>
              <th>Number of Grades</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @forelse($gradeTypes as $index => $type)
              <tr data-type="{{ $type->type }}" class="grade-type-row" style="cursor:pointer;">
                <td>{{ $index + 1 }}</td>
                <td class="text-capitalize">{{ $type->type }}</td>
                <td>{{ $type->count }}</td>
                <td>
                  <button class="btn btn-sm btn-primary btn-view-grades" data-type="{{ $type->type }}">
                    View Grades
                  </button>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="4" class="text-center">No grade types found.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    {{-- Grades Table (hidden initially) --}}
    <div class="card mb-4" id="gradesSection" style="display:none;">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span id="gradesSectionTitle" class="fw-bold">Grades for: </span>
        <button type="button" class="btn btn-primary btn-sm" id="btnAddGrade">
          <i class="bx bx-plus"></i> Add New Grade
        </button>
      </div>
      <div class="table-responsive text-nowrap">
        <table class="table table-hover" id="gradesTable">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Grade</th>
              <th>Min Mark</th>
              <th>Max Mark</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="gradesTableBody">
            {{-- Grades loaded dynamically via JS --}}
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Add Grade Modal -->
<div class="modal fade" id="addGradeModal" tabindex="-1" aria-labelledby="addGradeModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <form method="POST" action="{{ route('owner.grades.store') }}" id="addGradeForm">
      @csrf
      <div class="modal-content">
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="addGradeModalLabel">Add New Grade</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="type" id="gradeTypeInput" required>

          <div class="mb-3">
            <label for="name" class="form-label">Grade</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="e.g., A or 1" required>
          </div>

          <div class="mb-3">
            <label for="min_mark" class="form-label">Min Mark</label>
            <input type="number" name="min_mark" id="min_mark" class="form-control" required min="0" max="100">
          </div>

          <div class="mb-3">
            <label for="max_mark" class="form-label">Max Mark</label>
            <input type="number" name="max_mark" id="max_mark" class="form-control" required min="0" max="100">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Grade</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const gradeTypesTable = document.getElementById('gradeTypesTable');
  const gradesSection = document.getElementById('gradesSection');
  const gradesTableBody = document.getElementById('gradesTableBody');
  const gradesSectionTitle = document.getElementById('gradesSectionTitle');
  const btnAddGrade = document.getElementById('btnAddGrade');
  const gradeTypeInput = document.getElementById('gradeTypeInput');
  const addGradeModal = new bootstrap.Modal(document.getElementById('addGradeModal'));

  // Click handler for "View Grades" buttons
  document.querySelectorAll('.btn-view-grades').forEach(button => {
    button.addEventListener('click', function (e) {
      e.stopPropagation();
      const type = this.dataset.type;

      gradesSection.style.display = 'block';
      gradesSectionTitle.textContent = `Grades for: ${type.charAt(0).toUpperCase() + type.slice(1)}`;
      gradeTypeInput.value = type;

      // Fetch grades via AJAX (you'll create a route returning JSON grades by type)
      fetchGradesByType(type);
    });
  });

  // Add Grade button click: show modal with current type
  btnAddGrade.addEventListener('click', () => {
    addGradeModal.show();
  });

  // Fetch grades of a specific type (AJAX)
  function fetchGradesByType(type) {
    fetch(`/owner/grades/type/${type}`)
      .then(response => response.json())
      .then(data => {
        populateGradesTable(data.grades);
      })
      .catch(() => {
        gradesTableBody.innerHTML = `<tr><td colspan="5" class="text-center text-danger">Failed to load grades.</td></tr>`;
      });
  }

  // Fill the grades table body
  function populateGradesTable(grades) {
    if (grades.length === 0) {
      gradesTableBody.innerHTML = `<tr><td colspan="5" class="text-center">No grades found for this type.</td></tr>`;
      return;
    }

    gradesTableBody.innerHTML = '';

    grades.forEach((grade, idx) => {
      const row = document.createElement('tr');

      row.innerHTML = `
        <td>${idx + 1}</td>
        <td><strong>${grade.name}</strong></td>
        <td>${grade.min_mark}</td>
        <td>${grade.max_mark}</td>
        <td>
          <form method="POST" action="/owner/grades/${grade.id}/delete" onsubmit="return confirm('Are you sure you want to delete this grade?');" class="d-inline">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">
            <input type="hidden" name="_method" value="DELETE">
            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
              <i class="bx bx-trash"></i>
            </button>
          </form>
        </td>
      `;

      gradesTableBody.appendChild(row);
    });
  }

  // Optionally, handle old errors & open modal if validation fails after submission
  @if($errors->any())
    addGradeModal.show();
  @endif
});
</script>
@endsection
