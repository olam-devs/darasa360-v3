@extends('layouts.modernLayout', [
    'pageTitle' => 'Manage Announcements'
])

@section('title', 'Manage Announcements')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h4 class="fw-bold">Manage Announcements</h4>
      <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
        <i class="bx bx-plus"></i> Add Announcement
      </button>
    </div>

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

    <div class="card">
      <div class="table-responsive text-nowrap">
        <table class="table table-hover">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>Title</th>
              <th>Content</th>
              <th>View</th>
              <th>Document</th>
              <th>Classes</th>
              <th>Created At</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody class="table-border-bottom-0">
            @forelse($announcements as $announcement)
              <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $announcement->title }}</td>
                <td>
                  @php
                    $words = explode(' ', strip_tags($announcement->content));
                    $preview = implode(' ', array_slice($words, 0, 4)) . (count($words) > 4 ? '...' : '');
                  @endphp
                  {{ $preview }}
                </td>
                <td>
                  <a href="#" class="view-more" data-bs-toggle="modal"
                     data-bs-target="#contentModal"
                     data-content="{{ e($announcement->content) }}">
                    <i class="bx bx-show fs-4"></i>
                  </a>
                </td>
                <td>
                  @if(!empty($announcement->filepath))
                    <a href="{{ route('announcements.download', $announcement->id) }}" target="_blank" title="Download Document">
                      <i class="bx bx-download fs-5 text-primary"></i>
                    </a>
                  @else
                    <span class="text-muted">—</span>
                  @endif
                </td>
                <td>
                  @foreach($announcement->classes as $class)
                    <span class="badge bg-info">{{ $class->name }}</span>
                  @endforeach
                </td>
                <td>{{ $announcement->created_at->format('Y-m-d') }}</td>
                <td>
                  <button type="button" class="btn btn-sm btn-warning edit-announcement-btn"
                          data-id="{{ $announcement->id }}"
                          data-title="{{ $announcement->title }}"
                          data-content="{{ e($announcement->content) }}"
                          data-classes="{{ $announcement->classes->pluck('id')->join(',') }}">
                    <i class="bx bx-edit"></i>
                  </button>

                  <form action="{{ route('announcements.destroy', $announcement->id) }}" method="POST"
                        onsubmit="return confirm('Are you sure you want to delete this announcement?');"
                        class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" title="Delete">
                      <i class="bx bx-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="8" class="text-center">No announcements available.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- View Content Modal -->
<div class="modal fade" id="contentModal" tabindex="-1" aria-labelledby="contentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Announcement Content</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalContentBody"></div>
    </div>
  </div>
</div>

<!-- Add Announcement Modal -->
<div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" action="{{ route('announcements.store') }}" enctype="multipart/form-data">
      @csrf
      <div class="modal-content">
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="addAnnouncementModalLabel">Add New Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ old('title') }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" class="form-control" rows="5" required>{{ old('content') }}</textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Attach Document (PDF, max 10MB)</label>
            <input type="file" name="announcement_file" class="form-control" accept="application/pdf">
            <small class="text-muted">Optional. Only PDF files are allowed.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Select Classes</label>
            <div class="row">
              @foreach($classes as $class)
                <div class="col-md-4">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="classes[]" value="{{ $class->id }}" id="class_{{ $class->id }}">
                    <label class="form-check-label" for="class_{{ $class->id }}">{{ $class->name }}</label>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Create Announcement</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Single Edit Modal -->
<div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form method="POST" id="editAnnouncementForm" enctype="multipart/form-data">
      @csrf
      @method('POST')
      <div class="modal-content">
        <div class="modal-header position-relative">
          <h5 class="modal-title" id="editAnnouncementModalLabel">Edit Announcement</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 1rem; right: 1rem;"></button>
        </div>

        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" id="editTitle" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Content</label>
            <textarea name="content" id="editContent" class="form-control" rows="5" required></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Attach Document (PDF, max 10MB)</label>
            <input type="file" name="announcement_file" class="form-control" accept="application/pdf">
            <small class="text-muted">Optional. Only PDF files are allowed.</small>
          </div>

          <div class="mb-3">
            <label class="form-label">Select Classes</label>
            <div class="row">
              @foreach($classes as $class)
                <div class="col-md-4">
                  <div class="form-check">
                    <input class="form-check-input edit-class-checkbox" type="checkbox" name="classes[]" value="{{ $class->id }}" id="edit_class_{{ $class->id }}">
                    <label class="form-check-label" for="edit_class_{{ $class->id }}">{{ $class->name }}</label>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary me-auto" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Announcement</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // View modal
  document.querySelectorAll('.view-more').forEach(link => {
    link.addEventListener('click', function () {
      const fullContent = this.getAttribute('data-content');
      document.getElementById('modalContentBody').innerHTML = fullContent;
    });
  });

  // Edit modal
  const editModal = new bootstrap.Modal(document.getElementById('editAnnouncementModal'));
  document.querySelectorAll('.edit-announcement-btn').forEach(button => {
    button.addEventListener('click', function () {
      const id = this.getAttribute('data-id');
      const title = this.getAttribute('data-title');
      const content = this.getAttribute('data-content');
      const classes = this.getAttribute('data-classes').split(',');

      // Fill form fields
      document.getElementById('editTitle').value = title;
      document.getElementById('editContent').value = content;

      // Uncheck all
      document.querySelectorAll('.edit-class-checkbox').forEach(cb => cb.checked = false);
      // Check selected
      classes.forEach(cid => {
        const cb = document.getElementById('edit_class_' + cid);
        if(cb) cb.checked = true;
      });

      // Update form action
      const form = document.getElementById('editAnnouncementForm');
      form.action = `{{ url('students/announcements') }}/${id}/update`;

      editModal.show();
    });
  });
});
</script>

@endsection
