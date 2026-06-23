@extends('layouts.modernLayout', [
    'pageTitle' => 'Announcements For Your Class(es)'
])

@section('title', 'My Announcements')

@section('content')
<div class="row">
  <div class="col-12">
    <h4 class="fw-bold mb-4">Announcements For Your Class(es)</h4>

    @if($announcements->isEmpty())
      <div class="alert alert-info">No announcements available for your class at the moment.</div>
    @else
      <div class="card">
        <div class="table-responsive text-nowrap">
          <table class="table table-hover">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Title</th>
                <th>Content Preview</th>
                <th>View</th>
                <th>Document</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody>
              @foreach($announcements as $announcement)
                <tr>
                  <td>{{ ($announcements->currentPage() - 1) * $announcements->perPage() + $loop->iteration }}</td>
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
                      <a href="{{ route('announcements.download', $announcement->id) }}"
                         title="Download Document">
                        <i class="bx bx-download fs-4 text-primary"></i>
                      </a>
                    @else
                      <span class="text-muted">—</span>
                    @endif
                  </td>
                  <td>{{ $announcement->created_at->format('Y-m-d') }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
          <div>
            Showing {{ $announcements->firstItem() }} to {{ $announcements->lastItem() }} of {{ $announcements->total() }} announcements
          </div>
          <div>
            {{ $announcements->links('pagination::bootstrap-5') }}
          </div>
        </div>
      </div>
    @endif
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="contentModal" tabindex="-1" aria-labelledby="contentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Announcement Content</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body" id="modalContentBody">
        <!-- Full content goes here -->
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.view-more').forEach(link => {
      link.addEventListener('click', function () {
        const fullContent = this.getAttribute('data-content');
        document.getElementById('modalContentBody').innerHTML = fullContent;
      });
    });
  });
</script>
@endsection
