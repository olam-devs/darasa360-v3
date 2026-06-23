@extends('layouts/contentNavbarLayout')

@section('title', 'SMS History')

@section('content')
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">SMS History</h5>
        <a href="{{ route('super_admin.sms.send') }}" class="btn btn-primary btn-sm">
          <i class='bx bx-send'></i> Send New SMS
        </a>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Date</th>
                <th>Recipients</th>
                <th>Message</th>
                <th>Status</th>
                <th>Delivered</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td colspan="5" class="text-center">No SMS history available</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection
