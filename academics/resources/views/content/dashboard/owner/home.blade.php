@extends('layouts.modernLayout', [
    'pageTitle' => 'Welcome Back, {{ $dashboardData['username'] }}.'
])

@section('title', 'Dashboard - Analytics')

@section('vendor-style')
@vite('resources/assets/vendor/libs/apex-charts/apex-charts.scss')
@endsection

@section('vendor-script')
@vite('resources/assets/vendor/libs/apex-charts/apexcharts.js')
@endsection

@section('page-script')
@vite('resources/assets/js/dashboards-analytics.js')
@endsection

@section('content')
<div class="row">
  <div class="col-xxl-8 mb-6 order-0">
    <div class="card">
      <div class="d-flex align-items-start row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="card-title text-primary mb-3">Welcome Back, {{ $dashboardData['username'] }}.</h5>
            <p class="mb-6">Your leadership is driving success—keep inspiring excellence every day.</p>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-6">
            <img src="{{asset('assets/img/illustrations/' . (strtolower(session('gender')) === 'female' ? 'woman-with-laptop.png' : 'man-with-laptop.png')) }}" height="270" class="scaleX-n1-rtl" alt="View Badge User">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Replace cards with a responsive table -->
  <div class="col-xxl-4 order-1 order-xxl-1">
    <div class="card h-100">
      <div class="card-body">
        <h5 class="card-title mb-4">Overview</h5>
        <div class="table-responsive">
          <table class="table table-striped table-hover align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th>Metric</th>
                <th class="text-end">Count</th>
              </tr>
            </thead>
            <tbody>
              <tr>
                <td><i class="bx bx-chalkboard text-primary me-2" style="font-size: 1.25rem;"></i>Teachers</td>
                <td class="text-end">{{ $dashboardData['teachersCount'] }}</td>
              </tr>
              <tr>
                <td><i class="bx bx-user text-primary me-2" style="font-size: 1.25rem;"></i>Students</td>
                <td class="text-end">{{ $dashboardData['studentsCount'] }}</td>
              </tr>
              <tr>
                <td><i class="bx bx-message-dots text-primary me-2" style="font-size: 1.25rem;"></i>SMS Sent</td>
                <td class="text-end">{{ $dashboardData['smsCount'] }}</td>
              </tr>
              <tr>
                <td><i class="bx bx-building text-primary me-2" style="font-size: 1.25rem;"></i>Classes</td>
                <td class="text-end">{{ $dashboardData['classesCount'] }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
