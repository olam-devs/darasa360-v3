@extends('layouts.modernLayout', [
    'pageTitle' => 'Accountant Dashboard'
])

@section('title', 'Dashboard - Accountant')

@section('content')
<div class="row">
  <!-- Welcome Banner -->
  <div class="col-xxl-8 mb-4 order-0">
    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
      <div class="d-flex align-items-start row">
        <div class="col-sm-7">
          <div class="card-body">
            <h5 class="text-white mb-3">Welcome Back, {{ $dashboardData['username'] ?? 'Accountant' }}!</h5>
            <p class="text-white mb-0">Managing school finances with precision and transparency.</p>
          </div>
        </div>
        <div class="col-sm-5 text-center text-sm-left">
          <div class="card-body pb-0 px-0 px-md-6">
            <img src="{{asset('assets/img/illustrations/' . (strtolower(session('gender')) === 'female' ? 'woman-with-laptop.png' : 'man-with-laptop.png')) }}"
                 height="200" class="scaleX-n1-rtl" alt="Accountant">
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Overview -->
  <div class="col-xxl-4 order-1 order-xxl-1 mb-4">
    <div class="card h-100">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h5 class="card-title mb-0">Financial Overview</h5>
        <i class="bx bx-dollar-circle text-purple" style="font-size: 24px;"></i>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-borderless align-middle mb-0">
            <tbody>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-purple me-3">
                      <i class="bx bx-user"></i>
                    </div>
                    <span>Total Students</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $dashboardData['studentsCount'] ?? 0 }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-blue me-3">
                      <i class="bx bx-receipt"></i>
                    </div>
                    <span>Total Invoices</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $dashboardData['invoicesCount'] ?? 0 }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-green me-3">
                      <i class="bx bx-check-circle"></i>
                    </div>
                    <span>Paid</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $dashboardData['paidCount'] ?? 0 }}</h5>
                </td>
              </tr>
              <tr>
                <td>
                  <div class="d-flex align-items-center">
                    <div class="modern-badge modern-badge-yellow me-3">
                      <i class="bx bx-time-five"></i>
                    </div>
                    <span>Pending</span>
                  </div>
                </td>
                <td class="text-end">
                  <h5 class="mb-0">{{ $dashboardData['pendingCount'] ?? 0 }}</h5>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistics Cards Grid -->
  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-purple h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(124, 58, 237, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-dollar-circle" style="font-size: 30px; color: #7C3AED;"></i>
        </div>
        <h3 class="mb-1">TSh {{ number_format($dashboardData['totalRevenue'] ?? 0) }}</h3>
        <p class="text-muted mb-0">Total Revenue</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-blue h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(59, 130, 246, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-receipt" style="font-size: 30px; color: #3B82F6;"></i>
        </div>
        <h3 class="mb-1">{{ $dashboardData['invoicesCount'] ?? 0 }}</h3>
        <p class="text-muted mb-0">Total Invoices</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-green h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(16, 185, 129, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-check-circle" style="font-size: 30px; color: #10B981;"></i>
        </div>
        <h3 class="mb-1">TSh {{ number_format($dashboardData['paidAmount'] ?? 0) }}</h3>
        <p class="text-muted mb-0">Paid Amount</p>
      </div>
    </div>
  </div>

  <div class="col-md-3 col-sm-6 mb-4">
    <div class="modern-card modern-card-yellow h-100">
      <div class="modern-card-body text-center">
        <div class="modern-stat-icon mb-3" style="background: rgba(245, 158, 11, 0.1); width: 60px; height: 60px; border-radius: 12px; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
          <i class="bx bx-time-five" style="font-size: 30px; color: #F59E0B;"></i>
        </div>
        <h3 class="mb-1">TSh {{ number_format($dashboardData['pendingAmount'] ?? 0) }}</h3>
        <p class="text-muted mb-0">Pending Amount</p>
      </div>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="col-12 mb-4">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-rocket me-2 text-purple"></i>Quick Actions
        </h5>
      </div>
      <div class="modern-card-body">
        <div class="row g-3">
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('accountant.students.finance') }}" class="btn btn-outline-purple w-100 py-3">
              <i class="bx bx-user d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Student Finance</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('accountant.fees') }}" class="btn btn-outline-blue w-100 py-3">
              <i class="bx bx-money d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Manage Fees</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('accountant.payment.methods') }}" class="btn btn-outline-green w-100 py-3">
              <i class="bx bx-credit-card d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Payment Methods</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('accountant.receipts') }}" class="btn btn-outline-yellow w-100 py-3">
              <i class="bx bx-receipt d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Receipts</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('invoices.bulk.generate') }}" class="btn btn-outline-coral w-100 py-3">
              <i class="bx bx-file d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Bulk Invoices</span>
            </a>
          </div>
          <div class="col-lg-2 col-md-4 col-sm-6">
            <a href="{{ route('calendar.index') }}" class="btn btn-outline-pink w-100 py-3">
              <i class="bx bx-calendar d-block mb-2" style="font-size: 28px;"></i>
              <span class="d-block">Calendar</span>
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Financial Summary -->
  <div class="col-md-12">
    <div class="modern-card">
      <div class="modern-card-header">
        <h5 class="modern-card-title">
          <i class="bx bx-bar-chart-alt-2 me-2 text-blue"></i>Financial Summary
        </h5>
      </div>
      <div class="modern-card-body">
        <div class="alert alert-info mb-0">
          <i class="bx bx-info-circle me-2"></i>
          <strong>Accountant Dashboard:</strong> Manage student finances, track payments, generate invoices, and maintain financial records.
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
