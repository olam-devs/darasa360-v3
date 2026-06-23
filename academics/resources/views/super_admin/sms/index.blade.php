@extends('layouts/contentNavbarLayout')

@section('title', 'SMS Management')

@section('content')
<div class="row">
  <div class="col-md-6 col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="card-icon me-3">
            <span class="badge bg-label-primary rounded p-2">
              <i class='bx bx-message-square-dots bx-lg'></i>
            </span>
          </div>
          <div>
            <h5 class="card-title mb-1">Send Bulk SMS</h5>
            <p class="text-muted mb-0">Send SMS to multiple schools</p>
          </div>
        </div>
        <div class="mt-3">
          <a href="{{ route('super_admin.sms.send') }}" class="btn btn-primary">
            <i class='bx bx-send'></i> Send SMS
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="col-md-6 col-12 mb-4">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <div class="card-icon me-3">
            <span class="badge bg-label-info rounded p-2">
              <i class='bx bx-history bx-lg'></i>
            </span>
          </div>
          <div>
            <h5 class="card-title mb-1">SMS History</h5>
            <p class="text-muted mb-0">View sent messages</p>
          </div>
        </div>
        <div class="mt-3">
          <a href="{{ route('super_admin.sms.history') }}" class="btn btn-info">
            <i class='bx bx-list-ul'></i> View History
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="mb-0">SMS Overview</h5>
      </div>
      <div class="card-body">
        <p>Use the SMS management system to send bulk messages to schools, teachers, students, and parents.</p>
        <ul>
          <li>Send to all schools or specific schools</li>
          <li>Target specific user roles</li>
          <li>View message delivery status</li>
          <li>Track SMS history and usage</li>
        </ul>
      </div>
    </div>
  </div>
</div>

@endsection
